<?php
/**
 * Purchase Information Model
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_DataProcess_PurchaseInformation extends Mage_Core_Model_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
        $this->_init('emvdatasync/dataProcess_purchaseInformation');
    }

    /**
     * Load purchase information by subscriber
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return Emv_DataSync_Model_DataProcess_PurchaseInformation
     */
    public function loadBySubscriber(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $this->addData($this->getResource()->loadBySubscriber($subscriber));
        return $this;
    }
}