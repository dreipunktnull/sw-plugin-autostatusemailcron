<?php

namespace DpnCronStatusEmail\Service;

/**
 * Copyright notice
 *
 * (c) Björn Fromme <fromme@dreipunktnull.com>, dreipunktnull
 *
 * All rights reserved
 */

use Shopware\Components\Logger;

class MailService
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    protected $snippetManager;

    /**
     * @param Logger $logger
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(Logger $logger, \Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->logger = $logger;
        $this->snippetManager = $snippetManager;
    }

    /**
     * @param int $orderId
     * @param int $statusId
     */
    public function sendMail($orderId, $statusId)
    {
        /** @var \sOrder $module */
        $module = Shopware()->Modules()->Order();

        $mail = $module->createStatusMail($orderId, $statusId);

        if ($mail !== null) {
            $module->sendStatusMail($mail);
        } else {
            $message = $this->snippetManager
                ->getNamespace('backend/dpn_cron_status_email/translations')
                ->get('auto_email_missing_template');

            $this->logger->error(sprintf($message, $statusId));
        }
    }
}