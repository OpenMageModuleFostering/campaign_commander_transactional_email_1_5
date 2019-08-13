<?php
/**
 * Coverted Cart Model - contains the reminder information for a given order
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Orderflag extends Mage_Core_Model_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
        $this->_init('abandonment/orderflag');
    }

    /**
     * Load Order flag for a given order id
     * @param string $orderId
     * @return Emv_CartAlert_Model_Orderflag
     */
    public function loadByOrderId($orderId)
    {
        $this->setData($this->getResource()->loadByOrderId($orderId));
        return $this;
    }
}
