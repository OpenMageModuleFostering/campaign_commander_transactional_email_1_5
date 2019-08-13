<?php
/**
 * Purchase Information Resource Model
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Mysql4_DataProcess_PurchaseInformation extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('emvdatasync/purchase_info', 'id');
    }

    /**
     * Load by subscriber
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return array
     */
    public function loadBySubscriber(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $result = array();
        if ($subscriber->getCustomerId()) {
            $select = $this->_getReadAdapter()->select()
                ->from(array('main_table' => $this->getMainTable()))
                ->where('customer_id = :customer_id');

            $result = $this->_getReadAdapter()->fetchRow($select, array('customer_id' => $subscriber->getCustomerId()));

            if (!$result) {
                $result = array();
            }
        }

        return $result;
    }
}