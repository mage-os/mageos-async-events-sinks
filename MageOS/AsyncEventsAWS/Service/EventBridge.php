<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAWS\Service;

use Aws\EventBridge\EventBridgeClient;
use CloudEvents\Serializers\JsonSerializer;
use CloudEvents\Serializers\Normalizers\V1\Normalizer;
use CloudEvents\V1\CloudEventImmutable;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use MageOS\AsyncEvents\Api\Data\AsyncEventInterface;
use MageOS\AsyncEvents\Api\Data\ResultInterface;
use MageOS\AsyncEvents\Helper\NotifierResult;
use MageOS\AsyncEvents\Service\AsyncEvent\NotifierInterface;
use MageOS\AsyncEventsAWS\Model\Config as EventBridgeConfig;

class EventBridge implements NotifierInterface
{
    private ?EventBridgeClient $eventBridgeClient = null;

    /**
     * @param EventBridgeConfig $config
     * @param Normalizer $normalizer
     * @param EncryptorInterface $encryptor
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly EventBridgeConfig $config,
        private readonly Normalizer $normalizer,
        private readonly EncryptorInterface $encryptor,
        private readonly SerializerInterface $serializer,

    ) {
    }

    /**
     * @inheritDoc
     */
    public function notify(AsyncEventInterface $asyncEvent, CloudEventImmutable $event): ResultInterface
    {
        $result = new NotifierResult();
        $result->setSubscriptionId($asyncEvent->getSubscriptionId());
        $result->setAsyncEventData($this->normalizer->normalize($event, false));
        $result->setIsRetryable(false);
        $result->setIsSuccessful(false);

        try {
            $client = $this->getClient();

            if (!$client) {
                $result->setResponseData(
                    $this->serializer->serialize(__('EventBridge connection is not configured.'))
                );

                return $result;
            }

            $response = $client->putEvents([
                'Entries' => [
                    [
                        'Source' => $this->config->getSource(),
                        'Detail' => JsonSerializer::create()->serializeStructured($event),
                        'DetailType' => $asyncEvent->getEventName(),
                        'EventBusName' => $asyncEvent->getRecipientUrl()
                    ]
                ]
            ]);

            if (isset($response['FailedEntryCount']) && $response['FailedEntryCount'] > 0) {
                // As we are only ever submitting one event at a time, assume that only one result
                // can be returned.
                $entry = $result['Entries'][0];
                $result->setResponseData(
                    $this->serializer->serialize($entry)
                );

                // Retryable error codes taken from
                // https://docs.aws.amazon.com/eventbridge/latest/APIReference/API_PutEventsResultEntry.html#API_PutEventsResultEntry_Contents
                if ($entry['ErrorCode'] === 'InternalFailure' || $entry['ErrorCode'] === 'ThrottlingException') {
                    $result->setIsRetryable(true);
                }
            } else {
                $result->setIsSuccessful(true);

                $result->setResponseData(
                    $this->serializer->serialize($response)
                );
            }
        } catch (\Exception $exception) {
            $result->setResponseData(
                $exception->getMessage()
            );
        }

        return $result;
    }

    /**
     * Instantiate and return EventBridge client
     *
     * @return EventBridgeClient|null
     */
    private function getClient(): ?EventBridgeClient
    {
        if ($this->eventBridgeClient === null) {
            $region = $this->config->getRegion();
            $key = $this->config->getAccessKey();
            $secret = $this->config->getSecretAccessKey();

            if ($region === null || $key === null || $secret === null) {
                $this->eventBridgeClient = null;
            } else {
                $this->eventBridgeClient = new EventBridgeClient(
                    [
                        'version' => '2015-10-07',
                        'region' => $region,
                        'credentials' => [
                            'key' => $key,
                            'secret' => $this->encryptor->decrypt($secret)
                        ]
                    ]
                );
            }
        }

        return $this->eventBridgeClient;
    }
}
