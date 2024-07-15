<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAWS\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const XML_PATH_AWS_ACCESS_KEY = 'async_events_aws/eventbridge/access_key';
    private const XML_PATH_AWS_SECRET_ACCESS_KEY = 'async_events_aws/eventbridge/secret_access_key';
    private const XML_PATH_AWS_REGION = 'async_events_aws/eventbridge/region';
    private const XML_PATH_EVENT_SOURCE = 'async_events_aws/eventbridge/source';

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get Access Key
     *
     * @return string|null
     */
    public function getAccessKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_ACCESS_KEY, ScopeInterface::SCOPE_STORES);
    }

    /**
     * Get Secret Access Key
     *
     * @return string|null
     */
    public function getSecretAccessKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_SECRET_ACCESS_KEY, ScopeInterface::SCOPE_STORES);
    }

    /**
     * Get AWS region
     *
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_REGION, ScopeInterface::SCOPE_STORES);
    }

    /**
     * Get source for EventBridge
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSource(): ?string
    {
        $source = $this->scopeConfig->getValue(self::XML_PATH_EVENT_SOURCE, ScopeInterface::SCOPE_STORES);
        if ($source == null) {
            $url = $this->storeManager->getStore()->getBaseUrl();
            if ($url !== null) {
                return parse_url($url, PHP_URL_HOST);
            }
        }

        return $source;
    }
}
