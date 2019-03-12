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
        $logger = $this->container
            ->get('pluginlogger');

        $config = $this->container
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('DpnCronStatusEmail');

        $selectedOrderStatusIds = $config['dpnOrderStatus'];
        $selectedPaymentStatusIds = $config['dpnPaymentStatus'];

        $ordersWithChangedOrderStatus = $this->getUpdatedOrders(
            'dpn_prev_status_order',
            'status'
        );

        $count = $this->updateStatus(
            'dpn_prev_status_order',
            $ordersWithChangedOrderStatus,
            $selectedOrderStatusIds
        );

        $logger->info(sprintf('Order status updated on %d orders.', $count));

        $ordersWithChangedPaymentStatus = $this->getUpdatedOrders(
            'dpn_prev_status_payment',
            'cleared'
        );

        $count = $this->updateStatus(
            'dpn_prev_status_payment',
            $ordersWithChangedPaymentStatus,
            $selectedPaymentStatusIds
        );

        $logger->info(sprintf('Payment status updated on %d orders.', $count));

        return true;
    }

    /**
     * @param string $fieldPreviousStatus
     * @param string $fieldCurrentStatus
     * @return array
     */
    protected function getUpdatedOrders($fieldPreviousStatus, $fieldCurrentStatus)
    {
        /** @var Connection $connection */
        $connection = $this->container
            ->get('dbal_connection');

        $qb = $connection->createQueryBuilder();
        return $qb
            ->select('o.id as id', 'o.' . $fieldCurrentStatus . ' as status ')
            ->from('s_order', 'o')
            ->innerJoin('o', 's_order_attributes', 'a', 'o.id = a.orderID')
            ->where(
                $qb->expr()->neq('a.' . $fieldPreviousStatus, 'o.' . $fieldCurrentStatus)
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * @param $field
     * @param array $updatedOrders
     * @param array $selectedStatusIds
     * @return int
     */
    protected function updateStatus($field, array $updatedOrders, array $selectedStatusIds)
    {
        /** @var Connection $connection */
        $connection = $this->container
            ->get('dbal_connection');

        $count = 0;

        foreach ($updatedOrders as $order) {
            $qb = $connection->createQueryBuilder();
            $qb
                ->update('s_order_attributes')
                ->set($field, $order['status'])
                ->where($qb->expr()->eq('orderID', $order['id']))
                ->execute();

            if (in_array($order['status'], $selectedStatusIds, true)) {
                $this->sendMail($order['id'], $order['status']);
            }

            $count++;
        }

        return $count;
    }

    /**
     * @param int $orderId
     * @param int $statusId
     */
    protected function sendMail($orderId, $statusId)
    {
        /** @var \sOrder $module */
        $module = Shopware()->Modules()->Order();

        $mail = $module->createStatusMail($orderId, $statusId);

        if ($mail !== null) {
            $module->sendStatusMail($mail);
        } else {
            $message = $this->container
                ->get('snippets')
                ->getNamespace('backend/dpn_cron_status_email/translations')
                ->get('auto_email_missing_template');
            $this->container
                ->get('pluginlogger')
                ->error(sprintf($message, $statusId));
        }
    }
}