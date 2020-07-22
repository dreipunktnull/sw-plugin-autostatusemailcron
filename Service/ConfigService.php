<?php

namespace DpnCronStatusEmail\Service;

use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Shop\Shop;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

class ConfigService
{
    /**
     * @var ConfigReader
     */
    protected $configReader;

    /**
     * @param ConfigReader $configReader
     */
    public function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $shop = Shopware()->Models()->getRepository(Shop::class)->getActiveDefault();

        return $this->configReader->getByPluginName('DpnCronStatusEmail', $shop);
    }
}