<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAWS\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class SQSConfig
{
    private const XML_PATH_AWS_ACCESS_KEY = 'async_events_aws/sqs/access_key';
    private const XML_PATH_AWS_SECRET_ACCESS_KEY = 'async_events_aws/sqs/secret_access_key';
    private const XML_PATH_AWS_REGION = 'async_events_aws/sqs/region';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
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
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_ACCESS_KEY);
    }

    /**
     * Get Secret Access Key
     *
     * @return string|null
     */
    public function getSecretAccessKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_SECRET_ACCESS_KEY);
    }

    /**
     * Get AWS region
     *
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_REGION);
    }
}
