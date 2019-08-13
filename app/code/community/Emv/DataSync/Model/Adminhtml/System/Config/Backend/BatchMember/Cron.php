<?php
/**
 * Cron Expression for Scheduled Exports
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Adminhtml_System_Config_Backend_BatchMember_Cron extends Mage_Core_Model_Config_Data
{
    /**
     * @var string
     */
    protected $_crontabPath = 'crontab/jobs/emailvision_batchmember_export/schedule/cron_expr';

    /**
     * Save a formated crontime in core_config_data
     *
     * @see Mage_Core_Model_Abstract::_afterSave()
     */
    protected function _afterSave()
    {
        $cronConfigModel = Mage::getModel('core/config_data')
            ->load($this->_crontabPath, 'path');

        // if the cron is disabled, we remove the cron expression to avoid launching it again
        $enabled = $this->getFieldsetDataValue('enabled');
        if (!$enabled) {
            if ($cronConfigModel->getConfigId()) {
                $cronConfigModel->delete();
            }
            return;
        }

        $frequencyDaily   = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_DAILY;
        $frequencyWeekly  = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
        $frequencyMonthly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;

        // get time and frequency
        $time      = $this->getFieldsetDataValue('time');
        $frequency = $this->getFieldsetDataValue('frequency');

        // build cron expression string
        $cronExprArray = array(
            intval($time[1]),                                    // Minute
            intval($time[0]),                                    // Hour
            ($frequency == $frequencyMonthly) ? '1' : '*',       // Day of the Month
            '*',                                                       // Month of the Year
            ($frequency == $frequencyWeekly) ? '1' : '*',        // Day of the Week
        );
        $cronExprString = join(' ', $cronExprArray);

        try {
           $cronConfigModel
                ->setValue($cronExprString)
                ->setPath($this->_crontabPath)
                ->save();
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('emvdatasync')->__('Unable to save the cron expression for scheduled exports'));
        }
    }
}