<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAzure\Service;

use CloudEvents\Serializers\JsonSerializer;
use CloudEvents\Serializers\Normalizers\V1\Normalizer;
use CloudEvents\V1\CloudEventImmutable;
use finfo;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use MageOS\AsyncEvents\Api\Data\AsyncEventInterface;
use MageOS\AsyncEvents\Api\Data\ResultInterface;
use MageOS\AsyncEvents\Helper\NotifierResult;
use MageOS\AsyncEvents\Service\AsyncEvent\NotifierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class EventGrid implements NotifierInterface
{
    /**
     * @param Normalizer $normalizer
     * @param EncryptorInterface $encryptor
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly Normalizer $normalizer,
        private readonly EncryptorInterface $encryptor,
        private readonly SerializerInterface $serializer,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly HttpClient $httpClient,
    ) {}

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

        $tenantId = $this->scopeConfig->getValue("async_events_azure/service_principal/tenant_id");
        $clientId = $this->scopeConfig->getValue("async_events_azure/service_principal/client_id");
        $clientSecret = $this->scopeConfig->getValue("async_events_azure/service_principal/client_secret");

        $response = $this->httpClient->post("https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token", [
            "form_params" => [
                "client_id" => $clientId,
                "client_secret" => $this->encryptor->decrypt($clientSecret),
                "scope" => "https://eventgrid.azure.net/.default",
                "grant_type" => "client_credentials"
            ]
        ]);

        // TODO: cache access token
        $accessToken = json_decode($response->getBody()->getContents(), true)["access_token"];

        try {
            $response = $this->httpClient->post($asyncEvent->getRecipientUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/cloudevents+json; charset=utf-8',
                ],
                'query' => [
                    'api-version' => '2018-01-01'
                ],
                'body' => JsonSerializer::create()->serializeStructured($event)
            ]);

            $result->setIsSuccessful(true);

        } catch (RequestException $exception) {

            $result->setIsSuccessful(false);

            if ($exception->hasResponse()) {
                $response = $exception->getResponse();
                $responseContent = $response->getBody()->getContents();
                $responseStatusCode = $response->getStatusCode();
                $exceptionMessage = !empty($responseContent) ? $responseContent : $response->getReasonPhrase();

                $result->setResponseData($exceptionMessage);
                $result->setIsRetryable($responseStatusCode >= 500 || $responseStatusCode === 429);

                if ($response->hasHeader('Retry-After')) {
                    $retryAfter = $response->getHeader('Retry-After')[0];
                    if (is_numeric($retryAfter)) {
                        $result->setRetryAfter((int) $retryAfter);
                    }
                }
            } else {
                $result->setResponseData(
                    $exception->getMessage()
                );
            }
        } catch (ConnectException $exception) {
            $result->setIsSuccessful(false);
            $result->setResponseData($exception->getMessage());
            $result->setIsRetryable(true);
        }

        return $result;
    }
}
