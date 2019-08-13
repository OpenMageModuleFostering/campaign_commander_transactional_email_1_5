<?php
/**
 * Source Cron Time Triggered Exports
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Adminhtml_System_Config_Source_MemberCronTime
{
    /**
     *
     * @return Array    An array where keys are keys and values are labels
     */
    public function toOptionArray()
    {
        return array(
            'quarter_hour' => Mage::helper('emvdatasync')->__('Every 15 minutes'),
            'half_hour'    => Mage::helper('emvdatasync')->__('Every 30 minutes'),
            'hour'         => Mage::helper('emvdatasync')->__('Hourly'),
            'two_hours'    => Mage::helper('emvdatasync')->__('Every 2 hours'),
            'four_hours'   => Mage::helper('emvdatasync')->__('Every 4 hours'),
            'six_hours'    => Mage::helper('emvdatasync')->__('Every 6 hours'),
            'twelve_hours' => Mage::helper('emvdatasync')->__('Every 12 hours'),
            'daily'        => Mage::helper('emvdatasync')->__('Daily'),
        );
    }
}
