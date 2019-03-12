<?php

namespace DpnCronStatusEmail\Setup;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache;
use Shopware\Bundle\AttributeBundle\Service\CrudService;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

class Installer
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var CrudService
     */
    protected $crudService;

    /**
     * @param Connection $connection
     * @param CrudService $crudService
     */
    public function __construct(Connection $connection, CrudService $crudService)
    {
        $this->connection = $connection;
        $this->crudService = $crudService;
    }

    public function installAttributes()
    {
        try {
            $this->crudService->update(
                's_order_attributes',
                'dpn_prev_status_order',
                'integer',
                [
                    'displayInBackend' => false,
                    'position' => 300,
                    'custom' => false,
                    'translatable' => false,
                    'defaultValue' => 0,
                ],
                null,
                false,
                0
            );
            $this->crudService->update(
                's_order_attributes',
                'dpn_prev_status_payment',
                'integer',
                [
                    'displayInBackend' => false,
                    'position' => 310,
                    'custom' => false,
                    'translatable' => false,
                    'defaultValue' => 0,
                ],
                null,
                false,
                0
            );
            $this->crudService->update(
                's_order_attributes',
                'dpn_history_status_order',
                'string',
                [
                    'displayInBackend' => false,
                    'position' => 320,
                    'custom' => false,
                    'translatable' => false,
                ]
            );
            $this->crudService->update(
                's_order_attributes',
                'dpn_history_status_payment',
                'string',
                [
                    'displayInBackend' => false,
                    'position' => 330,
                    'custom' => false,
                    'translatable' => false,
                ]
            );

            $this->updateMetadataCacheAndModels();
        }
        catch (\Exception $e) {
        }
    }

    public function uninstallAttributes()
    {
        try {
            $this->crudService->delete('s_order_attributes', 'dpn_prev_status_order');
            $this->crudService->delete('s_order_attributes', 'dpn_prev_status_payment');
            $this->crudService->delete('s_order_attributes', 'dpn_history_status_order');
            $this->crudService->delete('s_order_attributes', 'dpn_history_status_payment');
            $this->updateMetadataCacheAndModels();
        }
        catch (\Exception $e) {
        }
    }

    public function updateCurrentOrderData()
    {
        $qb = $this->connection->createQueryBuilder();
        $orderData = $qb
            ->select('id', 'status', 'cleared')
            ->from('s_order')
            ->execute()
            ->fetchAll();

        foreach ($orderData as $row) {
            $qb = $this->connection->createQueryBuilder();
            $qb
                ->update('s_order_attributes')
                ->set('dpn_prev_status_order', $qb->createNamedParameter($row['status']))
                ->set('dpn_prev_status_payment', $qb->createNamedParameter($row['cleared']))
                ->set('dpn_history_status_order', $qb->createNamedParameter(serialize([])))
                ->set('dpn_history_status_payment', $qb->createNamedParameter(serialize([])))
                ->where($qb->expr()->eq('orderID', $qb->createNamedParameter($row['id'])))
                ->execute();
        }
    }

    protected function updateMetadataCacheAndModels()
    {
        /** @var Cache $metaDataCache */
        $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
        Shopware()->Models()->generateAttributeModels(['s_order_attributes']);
    }
}