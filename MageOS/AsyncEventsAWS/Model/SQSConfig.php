<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsAWS\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class SQSConfig {

    const XML_PATH_AWS_ACCESS_KEY = 'async_events_aws/sqs/access_key';

    const XML_PATH_AWS_SECRET_ACCESS_KEY = 'async_events_aws/sqs/secret_access_key';

    const XML_PATH_AWS_REGION = 'async_events_aws/sqs/region';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getAccessKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_ACCESS_KEY);
    }

    public function getSecretAccessKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_SECRET_ACCESS_KEY);
    }

    public function getRegion(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_AWS_REGION);
    }
}
