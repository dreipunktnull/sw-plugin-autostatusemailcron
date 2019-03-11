<?php

namespace DpnCronStatusEmail\Subscriber;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CronJobSubscriber implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_DpnCronJobProcessStatusEmails' => 'onProcessStatusEmailsCronJob',
        ];
    }

    /**
     * @param \Shopware_Components_Cron_CronJob $job
     * @return bool
     */
    public function onProcessStatusEmailsCronJob(\Shopware_Components_Cron_CronJob $job)
    {
        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('DpnCronStatusEmail');

        $selectedOrderStatusIds = $config['dpnOrderStatus'];
        $ordersWithChangedOrderStatus = $this->getUpdatedOrders('dpn_prev_status_order', $selectedOrderStatusIds);
        $count = $this->sendMailAndUpdateStatus('dpn_prev_status_order', $ordersWithChangedOrderStatus);

        $this->container->get('pluginlogger')->info(sprintf('Order status updated on %d orders.', $count));

        $selectedPaymentStatusIds = $config['dpnPaymentStatus'];
        $ordersWithChangedPaymentStatus = $this->getUpdatedOrders('dpn_prev_status_payment', $selectedPaymentStatusIds);
        $count = $this->sendMailAndUpdateStatus('dpn_prev_status_payment', $ordersWithChangedPaymentStatus);

        $this->container->get('pluginlogger')->info(sprintf('Payment status updated on %d orders.', $count));

        return true;
    }

    /**
     * @param string $table
     * @param array $selectedStatusIds
     * @return array
     */
    protected function getUpdatedOrders($table, array $selectedStatusIds)
    {
        /** @var Connection $connection */
        $connection = $this->container->get('dbal_connection');

        $qb = $connection->createQueryBuilder();
        return $qb
            ->select('o.id', 'o.status')
            ->from('s_order', 'o')
            ->innerJoin('o', 's_order_attributes', 'a', 'o.id = a.orderID')
            ->where($qb->expr()->in('o.status', $selectedStatusIds))
            ->andWhere($qb->expr()->neq('a.' . $table, 'o.status'))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param $field
     * @param array $updatedOrders
     * @return int
     */
    protected function sendMailAndUpdateStatus($field, array $updatedOrders)
    {
        /** @var Connection $connection */
        $connection = $this->container->get('dbal_connection');

        $count = 0;

        foreach ($updatedOrders as $order) {
            $mail = Shopware()->Modules()->Order()->createStatusMail($order['id'], $order['status']);
            if ($mail !== null) {
                Shopware()->Modules()->Order()->sendStatusMail($mail);
                $qb = $connection->createQueryBuilder();
                $qb
                    ->update('s_order_attributes')
                    ->set($field, $order['status'])
                    ->where($qb->expr()->eq('orderID', $order['id']))
                    ->execute();

                $count++;
            }
        }

        return $count;
    }
}