<?php
/**
 * Reminder Statistic Collection Model
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Mysql4_Stats_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Db_Collection_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('abandonment/stats');
    }
}

