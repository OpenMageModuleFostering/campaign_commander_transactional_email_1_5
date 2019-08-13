<?php
/**
 * Emailvision account resource model class
 * emv_urls => $type => array('url' => $url)
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Mysql4_Account extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    protected function _construct()
    {
        $this->_init('emvcore/account','id');
    }

    /**
     * Check if account name exists in database
     * @param Mage_Core_Model_Abstract $account
     * @return null | array
     */
    public function nameExists(Mage_Core_Model_Abstract $account)
    {
        $accountTable = $this->getTable('emvcore/account');
        $select = $this->_getReadAdapter()->select();
        $select->from($accountTable);
        $select->where("{$accountTable}.name = ?", addslashes($account->getName()));
        $select->where("{$accountTable}.id != ?", $account->getId());
        return $this->_getReadAdapter()->fetchRow($select);
    }

    /**
     * Check if api manager key exsits in database
     * @param Mage_Core_Model_Abstract $account
     * @return null | array
     */
    public function managerKeyExists(Mage_Core_Model_Abstract $account)
    {
        $accountTable = $this->getTable('emvcore/account');
        $select = $this->_getReadAdapter()->select();
        $select->from($accountTable);
        $select->where("{$accountTable}.manager_key = ?", addslashes($account->getManagerKey()));
        $select->where("{$accountTable}.id != ?", $account->getId());
        return $this->_getReadAdapter()->fetchRow($select);
    }
}