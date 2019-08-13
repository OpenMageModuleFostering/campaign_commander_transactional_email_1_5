<?php
/**
 * SmartFocus emt cron model
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Cron
{
    /**
     * Xml path for log cleanning mechanism
     */
    const XML_PATH_LOG_CLEAN_ENABLED               = 'emvemt/log_cleanning/enabled';
    const XML_PATH_LOG_CLEAN_NUMBER_LAST_DAY       = 'emvemt/log_cleanning/keeping_days';

    /**
     * Is log cleanning mechanism activated ?
     *
     * @return boolean
     */
    public function isLogCleanningEnabled()
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_LOG_CLEAN_ENABLED);
    }

    /**
     * Get number of days to keep
     *
     * @return int
     */
    public function getNumberDaysToKeep()
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_LOG_CLEAN_NUMBER_LAST_DAY);
    }

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

        // !!! it's very important to set SmartFocus custom error handler in order to remove lock file in case of fatal error
        Mage::helper('emvcore')->setSmartFocusErrorHandler();

        // create lock file, in order to prevent from launching several process at the same time
        $rule->createLock();

        try {
            $resendingQueue->sendPendingMessages();
            $resendingQueue->rescheduleErrorSending();
        } catch(Exception $e) {
            Mage::logException($e);
        }

        // reset error handler to Magento one
        Mage::helper('emvcore')->resetErrorHandler();
        // remove lock file
        $rule->removeLock();
    }

    /**
     * Clean sending logs
     */
    public function cleanLogs()
    {
        if (!$this->isLogCleanningEnabled()) {
            return false;
        }

        $timeLimit = $this->getNumberDaysToKeep() * 60 * 60 * 24;
        Mage::getModel('emvemt/log')->cleanLogs($timeLimit);
    }
}