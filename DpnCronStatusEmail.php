<?php

namespace DpnCronStatusEmail;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

use Doctrine\Common\Cache\Cache;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class DpnCronStatusEmail extends Plugin
{
    public function install(InstallContext $context)
    {
        /** @var CrudService $service */
        $crudService = $this->container->get('shopware_attribute.crud_service');

        try {
            $crudService->update('s_order_attributes', 'dpn_prev_status_order', 'integer', [
                'displayInBackend' => false,
                'position' => 300,
                'custom' => false,
                'translatable' => false,
                'defaultValue' => 0,
            ]);
            $crudService->update('s_order_attributes', 'dpn_prev_status_payment', 'integer', [
                'displayInBackend' => false,
                'position' => 310,
                'custom' => false,
                'translatable' => false,
                'defaultValue' => 0,
            ]);

            $this->updateMetadataCacheAndModels();
        }
        catch (\Exception $e) {
        }
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        /** @var CrudService $crudService */
        $crudService = $this->container->get('shopware_attribute.crud_service');

        try {
            $crudService->delete('s_order_attributes', 'dpn_prev_status_order');
            $crudService->delete('s_order_attributes', 'dpn_prev_status_payment');
            $this->updateMetadataCacheAndModels();
        }
        catch (\Exception $e) {
        }

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

    protected function updateMetadataCacheAndModels()
    {
        /** @var Cache $metaDataCache */
        $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
        Shopware()->Models()->generateAttributeModels(['s_order_attributes']);
    }
}

