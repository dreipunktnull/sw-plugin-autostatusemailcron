<?php

namespace DpnCronStatusEmail\Subscriber;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

use DpnCronStatusEmail\Service\OrderStatusService;
use Enlight\Event\SubscriberInterface;

class CronJobSubscriber implements SubscriberInterface
{
    /**
     * @var OrderStatusService
     */
    protected $orderStatusService;

    /**
     * @param OrderStatusService $orderStatusService
     */
    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
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
        $this->orderStatusService->process();

        return true;
    }
}