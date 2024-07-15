<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAWS\Service;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use CloudEvents\Serializers\JsonSerializer;
use CloudEvents\Serializers\Normalizers\V1\Normalizer;
use CloudEvents\V1\CloudEventImmutable;
use Exception;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use MageOS\AsyncEvents\Api\Data\AsyncEventInterface;
use MageOS\AsyncEvents\Api\Data\ResultInterface;
use MageOS\AsyncEvents\Helper\NotifierResult;
use MageOS\AsyncEvents\Helper\NotifierResultFactory;
use MageOS\AsyncEvents\Service\AsyncEvent\NotifierInterface;
use MageOS\AsyncEventsAWS\Model\SQSConfig;

class SQS implements NotifierInterface
{
    /** @var SqsClient|null */
    private ?SqsClient $sqsClient = null;

    /**
     * @param NotifierResultFactory $notifierResultFactory
     * @param EncryptorInterface $encryptor
     * @param SerializerInterface $serializer
     * @param Normalizer $normalizer
     * @param SQSConfig $config
     */
    public function __construct(
        private readonly NotifierResultFactory $notifierResultFactory,
        private readonly EncryptorInterface $encryptor,
        private readonly SerializerInterface $serializer,
        private readonly Normalizer $normalizer,
        private readonly SQSConfig $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function notify(AsyncEventInterface $asyncEvent, CloudEventImmutable $event): ResultInterface
    {
        /** @var NotifierResult $result */
        $result = $this->notifierResultFactory->create();
        $result->setSubscriptionId($asyncEvent->getSubscriptionId());
        $result->setAsyncEventData($this->normalizer->normalize($event, false));
        $result->setIsSuccessful(false);
        $result->setIsRetryable(false);

        $params = [
            'MessageBody' => JsonSerializer::create()->serializeStructured($event),
            'QueueUrl' => $asyncEvent->getRecipientUrl()
        ];

        try {
            $client = $this->getClient();

            if (!$client) {
                $result->setResponseData('SQS connection is not configured.');

                return $result;
            }

            $sqsResult = $client->sendMessage($params);
            $result->setResponseData(
                $this->serializer->serialize([
                    'MessageId' => $sqsResult->get('MessageId'),
                ])
            );
            $result->setIsSuccessful(true);

        } catch (AwsException $awsException) {
            $code = $awsException->getAwsErrorCode();

            // The PHP SDK doesn't throw named exceptions based on error codes, however the official go sdk maps the
            // service error codes to the internal SQS error codes.
            // https://github.com/aws/aws-sdk-go/blob/main/service/sqs/errors.go
            $retryable = match ($code) {
                'RequestThrottled', 'KmsDisabled', 'KmsThrottled' => true,
                default => false,
            };
            $result->setIsRetryable($retryable);

            $result->setResponseData($this->serializer->serialize([
                'code' => $awsException->getAwsErrorCode(),
                'message' => $awsException->getMessage()
            ]));

        } catch (Exception $exception) {
            $result->setResponseData(
                $exception->getMessage()
            );
        }

        return $result;
    }

    /**
     * Instantiate and return SqsClient
     *
     * @return SqsClient|null
     */
    private function getClient(): ?SqsClient
    {
        if ($this->sqsClient === null) {
            $region = $this->config->getRegion();
            $key = $this->config->getAccessKey();
            $secret = $this->config->getSecretAccessKey();

            if ($key !== null && $secret !== null) {

                $this->sqsClient = new SqsClient([
                    'region' => $region,
                    'credentials' => [
                        'key' => $key,
                        'secret' => $this->encryptor->decrypt($secret)
                    ]
                ]);
            }
        }

        return $this->sqsClient;
    }
}
