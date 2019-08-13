<?php
/**
 * Abandonment Collection Model
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Mysql4_Abandonment_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Db_Collection_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('abandonment/abandonment');
    }

    /**
     * Update customer abandonment subscribed flag for a given customer
     *
     * @param string $customerId
     * @param boolean $isSubscribed
     * @return boolean
     */
    public function updateCustomerAbadonementSubscribed($customerId, $isSubscribed)
    {
        if (!$customerId) {
            return false;
        }

        $customerId   = $this->getConnection()->quote($customerId);
        $isSubscribed = $this->getConnection()->quote($isSubscribed);

        $sql = 'UPDATE ' . $this->getMainTable()
            . ' SET customer_abandonment_subscribed = ' . $isSubscribed
            . ' WHERE customer_id = ' . $customerId
        ;

        $this->getConnection()->query($sql);

        return true;
    }
}

