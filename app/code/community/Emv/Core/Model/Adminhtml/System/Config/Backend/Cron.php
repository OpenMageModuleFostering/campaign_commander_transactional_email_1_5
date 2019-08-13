<?php
/**
 * Cron Expression Backend Model
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_Core_Model_Adminhtml_System_Config_Backend_Cron extends Mage_Core_Model_Config_Data
{
    /**
     * Crontab expression path - please define an appropriate path in order to make this backend work
     * @var string
     */
    protected $_crontabPath = '';

    /**
     * Save a formated crontime in core_config_data
     *
     * @see Mage_Core_Model_Abstract::_afterSave()
     */
    protected function _afterSave()
    {
        if ($this->_crontabPath) {
            $cronConfigModel = Mage::getModel('core/config_data')
                ->load($this->_crontabPath, 'path');

            // if the cron is disabled, we remove the cron expression to avoid launching it again
            $enabled = (bool)$this->getFieldsetDataValue('enabled');
            if (!$enabled) {
                if ($cronConfigModel->getConfigId()) {
                    $cronConfigModel->delete();
                }
                return false;
            }

            $frequencyDaily   = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_DAILY;
            $frequencyWeekly  = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
            $frequencyMonthly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;

            // get time and frequency
            $time      = $this->getFieldsetDataValue('time');
            $frequency = $this->getFieldsetDataValue('frequency');

            $monthlySetting = '*';
            if ($frequency == $frequencyMonthly) {
                $dateSetting = (int)$this->getFieldsetDataValue('date');
                if ($dateSetting) {
                    $monthlySetting = $dateSetting;
                } else {
                    // trigger cron process at the first day of the month
                    $monthlySetting = 1;
                }
            }

            $weeklySetting = '*';
            if ($frequency == $frequencyWeekly) {
                $daySetting = (int)$this->getFieldsetDataValue('day');
                if ($daySetting) {
                    $weeklySetting = $daySetting;
                } else {
                    // trigger cron process at the first day of the week
                    $weeklySetting = 1;
                }
            }

            // build cron expression string
            $cronExprArray = array(
                intval($time[1]),        // Minute
                intval($time[0]),        // Hour
                $monthlySetting,         // Day of the Month
                '*',                     // Month of the Year
                $weeklySetting,          // Day of the Week
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
}