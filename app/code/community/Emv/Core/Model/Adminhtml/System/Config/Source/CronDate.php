<?php
/**
 * Date of the month source for Cron configuration
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 *
 */
class Emv_Core_Model_Adminhtml_System_Config_Source_CronDate
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
            self::$_options = array();

            for ($i = 1; $i < 32; $i++) {
                self::$_options[] = array(
                    'label' => $i,
                    'value' => $i,
                );
            }
        }
        return self::$_options;
    }
}