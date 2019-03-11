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
        $logger = $this->container->get('pluginlogger');

        $selectedOrderStatusIds = $config['dpnOrderStatus'];
        $ordersWithChangedOrderStatus = $this->getUpdatedOrders(
            'dpn_prev_status_order',
            'status',
            $selectedOrderStatusIds
        );
        $count = $this->sendMailAndUpdateStatus('dpn_prev_status_order', $ordersWithChangedOrderStatus);

        $logger->info(sprintf('Order status updated on %d orders.', $count));

        $selectedPaymentStatusIds = $config['dpnPaymentStatus'];
        $ordersWithChangedPaymentStatus = $this->getUpdatedOrders(
            'dpn_prev_status_payment',
            'cleared',
            $selectedPaymentStatusIds
        );
        $count = $this->sendMailAndUpdateStatus('dpn_prev_status_payment', $ordersWithChangedPaymentStatus);

        $logger->info(sprintf('Payment status updated on %d orders.', $count));

        return true;
    }

    /**
     * @param string $fieldPreviousStatus
     * @param string $fieldCurrentStatus
     * @param array $selectedStatusIds
     * @return array
     */
    protected function getUpdatedOrders($fieldPreviousStatus, $fieldCurrentStatus, array $selectedStatusIds)
    {
        /** @var Connection $connection */
        $connection = $this->container->get('dbal_connection');

        $qb = $connection->createQueryBuilder();
        return $qb
            ->select('o.id', 'o.' . $fieldCurrentStatus . ' as status ')
            ->from('s_order', 'o')
            ->innerJoin('o', 's_order_attributes', 'a', 'o.id = a.orderID')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('o.' . $fieldCurrentStatus, $selectedStatusIds),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('a.' . $fieldPreviousStatus),
                        $qb->expr()->neq('a.' . $fieldPreviousStatus, 'o.' . $fieldCurrentStatus)
                    )
                )
            )
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
            if ($mail === null) {
                $message = $this->container
                    ->get('snippets')
                    ->getNamespace('backend/dpn_cron_status_email/translations')
                    ->get('auto_email_missing_template');
                $this->container->get('pluginlogger')
                    ->error(sprintf($message, $order['status']));
            } else {
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