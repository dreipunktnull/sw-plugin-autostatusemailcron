<?php

namespace DpnCronStatusEmail;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

use DpnCronStatusEmail\Setup\Installer;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class DpnCronStatusEmail extends Plugin
{
    public function install(InstallContext $context)
    {
        $installer = $this->getInstaller();
        $installer->installAttributes();
        $installer->updateCurrentOrderData();
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $installer = $this->getInstaller();
        $installer->uninstallAttributes();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);
    }

    /**
     * @return Installer
     */
    protected function getInstaller()
    {
        $crudService = $this->container
            ->get('shopware_attribute.crud_service');
        $connection = $this->container
            ->get('dbal_connection');

        return new Installer($connection, $crudService);
    }
}

