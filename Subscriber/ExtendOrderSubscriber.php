<?php

namespace DpnCronStatusEmail\Subscriber;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

use Enlight\Event\SubscriberInterface;

class ExtendOrderSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    protected $pluginDirectory;

    /**
     * @param string $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onBackendOrderPostDispatch'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onBackendOrderPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Article $controller */
        $controller = $args->getSubject();
        /** @var \Enlight_View_Default $view */
        $view = $controller->View();

        $view->addTemplateDir($this->pluginDirectory . '/Resources/views/');

        if ($controller->Request()->getActionName() === 'load') {
            $view->extendsTemplate('backend/dpn_cron_status_email/order/controller/list.js');
            $view->extendsTemplate('backend/dpn_cron_status_email/order/model/mail.js');
            $view->extendsTemplate('backend/dpn_cron_status_email/order/view/batch/form.js');
        }
    }

}