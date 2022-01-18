<?php

namespace DpnCronStatusEmail\Service;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

use Doctrine\DBAL\Connection;
use Shopware\Components\Logger;

class OrderStatusService
{
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ConfigService $configService
     * @param MailService $mailService
     * @param Connection $connection
     * @param Logger $logger
     */
    public function __construct(
        ConfigService $configService,
        MailService $mailService,
        Connection $connection,
        Logger $logger
    ) {
        $this->configService = $configService;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->mailService = $mailService;
    }

    public function process()
    {
        $config = $this->configService->getConfig();

        $selectedOrderStatusIds = $config['dpnOrderStatus'] ?: [];
        $selectedPaymentStatusIds = $config['dpnPaymentStatus'] ?: [];
        $maxEmailsPerRun = $config['dpnMaxEmailsPerRun'] ?: 0;
        $emailsDateFrom = $config['dpnEmailsDateFrom'];

        $ordersWithChangedOrderStatus = $this->getUpdatedOrders(
            'dpn_prev_status_order',
            'status',
            'dpn_history_status_order',
            $maxEmailsPerRun,
            $emailsDateFrom
        );

        $count = $this->updateStatus(
            'dpn_prev_status_order',
            'dpn_history_status_order',
            $ordersWithChangedOrderStatus,
            $selectedOrderStatusIds
        );

        $this->logger->info(sprintf('Order status updated on %d orders.', $count));

        $ordersWithChangedPaymentStatus = $this->getUpdatedOrders(
            'dpn_prev_status_payment',
            'cleared',
            'dpn_history_status_payment',
            $maxEmailsPerRun,
            $emailsDateFrom
        );

        $count = $this->updateStatus(
            'dpn_prev_status_payment',
            'dpn_history_status_payment',
            $ordersWithChangedPaymentStatus,
            $selectedPaymentStatusIds
        );

        $this->logger->info(sprintf('Payment status updated on %d orders.', $count));
    }

    /**
     * @param string $fieldPreviousStatus
     * @param string $fieldCurrentStatus
     * @param string $fieldHistory
     * @param int $maxCount
     * @param null $minDate
     * @return array
     */
    protected function getUpdatedOrders($fieldPreviousStatus, $fieldCurrentStatus, $fieldHistory, $maxCount = 0, $minDate = null)
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('o.id as id', 'o.' . $fieldCurrentStatus . ' as status', 'a.' . $fieldHistory . ' as history')
            ->from('s_order', 'o')
            ->innerJoin('o', 's_order_attributes', 'a', 'o.id = a.orderID')
            ->where(
                $qb->expr()->neq('a.' . $fieldPreviousStatus, 'o.' . $fieldCurrentStatus)
            );

        if ($maxCount > 0) {
            $qb->setMaxResults($maxCount);
        }

        if (false === empty($minDate)) {
            $qb->andWhere(
                $qb->expr()->gt('o.ordertime', $qb->createNamedParameter($minDate))
            );
        }

        return $qb
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $fieldStatus
     * @param string $fieldHistory
     * @param array $updatedOrders
     * @param array $selectedStatusIds
     * @return int
     */
    protected function updateStatus($fieldStatus, $fieldHistory, array $updatedOrders, array $selectedStatusIds)
    {
        $config = $this->configService->getConfig();

        $sendOneTimePerStatus = $config['dpnSendOneTimePerStatus'];

        $count = 0;

        foreach ($updatedOrders as $order) {
            $historyData = unserialize($order['history']);
            $history = is_array($historyData) ? $historyData : [];
            $newStatusInHistory = in_array($order['status'], (array)$history, false);
            $newStatusDoNotify = in_array($order['status'], (array)$selectedStatusIds, false);

            if (!$newStatusInHistory) {
                $history[] = $order['status'];
            }
            $historyData = serialize($history);

            $qb = $this->connection->createQueryBuilder();
            $qb
                ->update('s_order_attributes')
                ->set($fieldStatus, $qb->createNamedParameter($order['status']))
                ->set($fieldHistory, $qb->createNamedParameter($historyData))
                ->where($qb->expr()->eq('orderID', $order['id']))
                ->execute();

            if ($newStatusDoNotify && !($newStatusInHistory && $sendOneTimePerStatus)) {
                $this->mailService->sendMail($order['id'], $order['status']);
            }

            $count++;
        }

        return $count;
    }
}