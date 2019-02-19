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
     * @param \Enlight_Event_EventArgs $args
     */
    public function onProcessStatusEmailsCronJob(\Enlight_Event_EventArgs $args)
    {
        $config = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('DpnCronStatusEmail');

        $selectedOrderStatusIds = $config['dpnOrderStatus'];

        /** @var Connection $connection */
        $connection = $this->container->get('dbal_connection');

        $qb = $connection->createQueryBuilder();
        $updatedOrders = $qb
            ->select('o.id', 'o.status')
            ->from('s_order', 'o')
            ->innerJoin('o', 's_order_attributes', 'a', 'o.id = a.orderID')
            ->where($qb->expr()->in('o.status', $selectedOrderStatusIds, Connection::PARAM_INT_ARRAY))
            ->andWhere($qb->expr()->neq('a.dpn_prev_status_order', 'o.status'))
            ->execute()
            ->fetchAll();

        $count = 0;

        foreach ($updatedOrders as $order) {
            $mail = Shopware()->Modules()->Order()->createStatusMail($order['id'], $order['status']);
            if ($mail !== null) {
                Shopware()->Modules()->Order()->sendStatusMail($mail);
                $qb = $connection->createQueryBuilder();
                $qb
                    ->update('s_order_attributes')
                    ->set('dpn_prev_status_order', $order['status'])
                    ->where($qb->expr()->eq('orderID', $order['id']))
                    ->execute();

                $count++;
            }
        }

        echo $count;
    }
}