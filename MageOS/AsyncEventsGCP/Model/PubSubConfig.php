<?php

declare(strict_types=1);

namespace MageOS\AsyncEventsGCP\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class PubSubConfig {

    private const XML_PATH_GCP_ADC_PATH = 'async_events_gcp/pubsub/adc_path';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get application default credential file's path
     *
     * @return string|null
     */
    public function getAdcPath(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GCP_ADC_PATH);
    }
}
