<?php
/**
 * Day of the week source for cron configuration
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 *
 */
class Emv_Core_Model_Adminhtml_System_Config_Source_CronDay
{

    /**
     * @var array
     */
    protected static $_options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = array(
                array(
                    'label' => Mage::helper('cron')->__('Monday'),
                    'value' => 1,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Tuesday'),
                    'value' => 2,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Wednesday'),
                    'value' => 3,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Thursday'),
                    'value' => 4,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Friday'),
                    'value' => 5,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Saturday'),
                    'value' => 6,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Sunday'),
                    'value' => 0,
                ),
            );
        }
        return self::$_options;
    }
}