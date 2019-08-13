<?php
/**
 * EmailVision emt cron model
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Cron
{
    /**
     * Process all error sending emails
     *  - convert all error sending emails into rescheduled queue messages
     *  - send the last rescheduled queue messages to SmartFocus platform
     */
    public function processErrorSendingEmails()
    {
        $resendingQueue = Mage::getModel('emvemt/resending_queue');
        $rule = $resendingQueue->getRule();

        if ($rule->lockExists()) {
            return;
        }

        // create lock file, in order to prevent from launching several process at the same time
        $rule->createLock();

        try {
            $resendingQueue->sendPendingMessages();
            $resendingQueue->rescheduleErrorSending();
        } catch(Exception $e) {
            Mage::logException($e);
        }

        // remove lock file
        $rule->removeLock();
    }
}