<?php
/**
 * EmailVision resending rule model
 *  - Indicate if some actions (reschedule, or resend emails) are available or not
 *  - Perform rescheduling error sending
 *  - handle the lock mechanism in order to avoid several processes running at the same time
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Resending_Rule extends Mage_Core_Model_Abstract
{
    /**
     * XML Path to configs for resending mechanism
     */
    const XML_PATH_RESENDING_ACTIVE = 'emvemt/resending_mechanism/enabled';
    const XML_PATH_FIRST_DELAY      = 'emvemt/resending_mechanism/first_delay';
    const XML_PATH_SECOND_DELAY     = 'emvemt/resending_mechanism/second_delay';
    const XML_PATH_THIRD_DELAY      = 'emvemt/resending_mechanism/third_delay';
    const XML_PATH_FOURTH_DELAY     = 'emvemt/resending_mechanism/fourth_delay';

    /**
     * Lock file name pattern
     */
    const LOCK_FILE_NAME_PATTERN    = 'resending_error_email_process';

    /**
     * Is resending mechanism activated ?
     *
     * @return boolean
     */
    public function isResendingMechanismActivated()
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_RESENDING_ACTIVE);
    }

    /**
     * Can send pending message ?
     *
     * @return boolean
     */
    public function canSendPendingMessage()
    {
        return $this->isResendingMechanismActivated();
    }

    /**
     * Can schedule error sending ?
     *
     * @return boolean
     */
    public function canScheduleErrorSending()
    {
        return $this->isResendingMechanismActivated();
    }

    /**
     * Reschedule all error sending
     *  - 1. get all error sending email list
     *  - 2. convert these into resending queue messages
     *  - 3. flag all transformed error sending emails to avoid converting in resending queue messages next time again
     *
     * @return Emv_Emt_Model_Resending_Rule
     */
    public function rescheduleErrorSending()
    {
        $sql = $this->prepareSelectToGetErrorSending();

        $dbConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $result = $dbConnection->query($sql)->fetchAll();

        // prepare error list
        $list = array();
        foreach ($result as $error) {
            if (isset($error['id'])) {
                $list[] = $error['id'];
            }
        }

        // if we have error sending
        if (count($list)) {
            // reschedule all error sending
            $select = $this->prepareQueryToRescheduleErrorSending($list);
            $result = $dbConnection->query($select);

            if ($result) {
                $select = $this->prepareQueryToFlagAllErrorSending($list);
                $result = $dbConnection->query($select);
            }
        }
        return $this;
    }

    /**
     * Prepare query to reschedule all error sending
     *  - Use replace into to update and create new resending queue messages from error sending emails
     *
     * @return string
     */
    public function prepareQueryToRescheduleErrorSending(array $listError)
    {
        $resource = Mage::getModel('core/resource');
        $logTable = $resource->getTableName('emvemt/log');
        $messageTable = $resource->getTableName('emvemt/resending_queue_message');
        $gmtDate = Mage::getModel('core/date')->gmtDate();
        $listError = implode(',', $listError);

        $select = "
            REPLACE INTO `{$messageTable}`
                (
                    `created_at`, `sent_sucess`, `number_attempts`, `sending_mode`, `email`, `store_id`, `account_id`, `magento_template_name`, `original_magento_template_name`,
                    `emv_name`, `emv_params`, `emv_content_variables`, `emv_dyn_variables`
                )
            SELECT
                '$gmtDate' AS `created_at`, 0 AS `sent_sucess`, 0 AS `number_attempts`, `sending_mode`, `email`, `store_id`, `account_id`, `magento_template_name`, `original_magento_template_name`,
                `emv_name`, `emv_params`, `emv_content_variables`, `emv_dyn_variables`

            FROM `{$logTable}`
            WHERE `id` IN ({$listError})
        ";

        return $select;
    }

    /**
     * Prepare select to get list of error sending
     *
     * @return string
     */
    public function prepareSelectToGetErrorSending()
    {
        $resource = Mage::getModel('core/resource');
        $logTable = $resource->getTableName('emvemt/log');
        $messageTable = $resource->getTableName('emvemt/resending_queue_message');
        $gmtDate = Mage::getModel('core/date')->gmtDate();
        $sendingType = Emv_Emt_Model_Log::SENDING_BASIC_WORKFLOW;
        $rescheduled = Emv_Emt_Model_Log::RESCHEDULED;

        $select = "
            SELECT `id`
            FROM `{$logTable}`
            WHERE `error` IS NOT NULL
                AND `emv_params` IS NOT NULL
                AND `sending_type` = '{$sendingType}'
                AND `rescheduled` != {$rescheduled}
        ";

        return $select;
    }

    /**
     * Prepare query to flag all error sending
     *
     * @param array $listError
     * @return string
     */
    public function prepareQueryToFlagAllErrorSending(array $listError)
    {
        $resource = Mage::getModel('core/resource');
        $listError = implode(',', $listError);
        $logTable = $resource->getTableName('emvemt/log');
        $rescheduled = Emv_Emt_Model_Log::RESCHEDULED;
        $select = "
            UPDATE `{$logTable}`
            SET rescheduled = {$rescheduled}
            WHERE `id` IN ({$listError})
        ";

        return $select;
    }

    /**
     * Check if lock file exists
     *
     * @throw Exception $e - if have some write problem permission
     * @return boolean
     */
    public function lockExists()
    {
        $lockExists = Mage::helper('emvcore')->checkLockFile(self::LOCK_FILE_NAME_PATTERN);
        return $lockExists;
    }

    /**
     * Remove lock file
     *
     * @throw Exception $e - if have some write problem permission
     * @return boolean
     */
    public function removeLock()
    {
        return Mage::helper('emvcore')->removeLockFile(self::LOCK_FILE_NAME_PATTERN);
    }

    /**
     * Create lock file
     *
     * @throw Exception $e - if have some write problem permission
     * @return boolean
     */
    public function createLock($content = '')
    {
        if (!$content) {
            $content = 'email_resending_process at ' . Mage::getModel('core/date')->date('Y-m-d H:i:s');
        }
        return Mage::helper('emvcore')->createLockFile(self::LOCK_FILE_NAME_PATTERN, $content);
    }

    /**
     * Check if message is ready to be sent according to the delay configurations (first delay, second delay...)
     *
     * @param Emv_Emt_Model_Emt_Resending_Queue_Message $message
     * @return boolean
     */
    public function isMessageReadyToBeSent(Emv_Emt_Model_Resending_Queue_Message $message)
    {
        $isAllowed = false;
        // all calculations need to be done in GMT
        if ($message->getLastAttempt()) {
            $lastAttempt = strtotime($message->getLastAttempt());
        } else {
            $lastAttempt = strtotime($message->getCreatedAt());
        }

        // gmt time stamp
        $now = Mage::getModel('core/date')->gmtTimestamp();
        $delta = abs($now - $lastAttempt);
        $deltaInMinutes = ceil($delta/60);

        $attempts = $message->getNumberAttempts();
        // the cron is planned every 5 minutes so we need to planify every thing with 5 minutes of difference
        switch (($attempts % Emv_Emt_Model_Resending_Queue_Message::MAX_ATTEMPT)) {
            case 0 :
                $firstDelay = Mage::getStoreConfig(self::XML_PATH_FIRST_DELAY);
                if ($firstDelay && $deltaInMinutes >= $firstDelay) {
                    $isAllowed = true;
                }
                break;
            case 1 :
                $secondDelay = Mage::getStoreConfig(self::XML_PATH_SECOND_DELAY);
                if ($secondDelay && $deltaInMinutes >= $secondDelay) {
                    $isAllowed = true;
                }
                break;
            case 2 :
                $thirdDelay = Mage::getStoreConfig(self::XML_PATH_THIRD_DELAY);
                if ($thirdDelay && $deltaInMinutes >= $thirdDelay) {
                    $isAllowed = true;
                }
                break;
            case 3 :
                $fourthDelay = Mage::getStoreConfig(self::XML_PATH_FOURTH_DELAY);
                if ($fourthDelay && $deltaInMinutes >= $fourthDelay) {
                    $isAllowed = true;
                }
                break;
        }

        return $isAllowed;
    }
}