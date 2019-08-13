<?php
/**
 * Purchase Information Collection Resource Model
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Mysql4_DataProcess_PurchaseInformation_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Db_Collection_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('emvdatasync/dataProcess_purchaseInformation');
    }
}