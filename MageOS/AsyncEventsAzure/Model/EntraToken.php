<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAzure\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use GuzzleHttp\Client as HttpClient;

class EntraToken
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly HttpClient $httpClient,
        private readonly EncryptorInterface $encryptor,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer
    ) {}

    /**
     * @return string
     */
    public function get(string $scope): string
    {
        $tenantId = $this->scopeConfig->getValue("async_events_azure/service_principal/tenant_id");
        $clientId = $this->scopeConfig->getValue("async_events_azure/service_principal/client_id");
        $clientSecret = $this->scopeConfig->getValue("async_events_azure/service_principal/client_secret");
        $scope = "https://eventgrid.azure.net/.default";

        $cacheKey = "azure_api_token";

        $accessToken = $this->cache->load($cacheKey) ? $this->cache->load($cacheKey) : null;

        if (!$accessToken) {
            $response = $this->httpClient->post("https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token", [
                "form_params" => [
                    "client_id" => $clientId,
                    "client_secret" => $this->encryptor->decrypt($clientSecret),
                    "scope" => $scope,
                    "grant_type" => "client_credentials"
                ]
            ]);

            $accessToken = $this->serializer->unserialize($response->getBody()->getContents())["access_token"];

            $this->cache->save($accessToken, $cacheKey, ['azure_api'], 3300);
        }

        return $accessToken;
    }
}
