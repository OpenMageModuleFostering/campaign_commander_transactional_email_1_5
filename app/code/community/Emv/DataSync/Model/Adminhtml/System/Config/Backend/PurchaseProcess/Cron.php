<?php
/**
 * Cron Expression for Triggered Export
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Adminhtml_System_Config_Backend_PurchaseProcess_Cron extends Mage_Core_Model_Config_Data
{
    /**
     * @var string
     */
    protected $_crontabPath = 'crontab/jobs/emailvision_purchase_process/schedule/cron_expr';

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

        $cronExprArray = array(
            'quarter_hour' => '*/15 * * * *',
            'half_hour'    => '*/30 * * * *',
            'hour'         => '0 * * * *',
            'two_hours'    => '0 */2 * * *',
            'four_hours'   => '0 */4 * * *',
            'six_hours'    => '0 */6 * * *',
            'twelve_hours' => '0 */12 * * *',
            'daily'        => '0 0 * * *',
        );

        $cronExprString = $cronExprArray[$this->getValue()];
        try {
            $cronConfigModel
                ->setValue($cronExprString)
                ->setPath($this->_crontabPath)
                ->save();
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('emvdatasync')->__('Unable to save the cron expression for purchase process'));
        }

        parent::_afterSave();
    }

}
