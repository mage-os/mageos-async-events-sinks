<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAWS\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_PATH_AWS_ACCESS_KEY = 'async_events_aws/eventbridge/access_key';
    const XML_PATH_AWS_SECRET_ACCESS_KEY = 'async_events_aws/eventbridge/secret_access_key';
    const XML_PATH_AWS_REGION = 'async_events_aws/eventbridge/region';
    const XML_PATH_EVENT_SOURCE = 'async_events_aws/eventbridge/source';

    /**
     * Config constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getAccessKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_ACCESS_KEY, ScopeInterface::SCOPE_STORES);
    }

    public function getSecretAccessKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_SECRET_ACCESS_KEY, ScopeInterface::SCOPE_STORES);
    }

    public function getRegion()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_REGION, ScopeInterface::SCOPE_STORES);
    }

    public function getSource()
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
