<?php
/**
 * Emailvision account resource collection
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Mysql4_Account_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('emvcore/account','emvcore/account');
    }

    /**
     * @param string $name
     * @return Emv_Core_Model_Mysql4_Account_Collection
     */
    public function addNameFilter($name)
    {
        $this->addFieldToFilter('name', array('like' => $name));

        return $this;
    }

    /**
     * Get an account corresponding to account login
     *
     * @param string $login
     * @return Emv_Core_Model_Mysql4_Account_Collection
     */
    public function addLoginFilter($login)
    {
        $this->addFieldToFilter('login', array('like' => $login));

        return $this;
    }

    /**
     * Get an account corresponding to key manager
     * @param string $keyManager
     * @return Emv_Core_Model_Mysql4_Account_Collection
     */
    public function addManagerKeyFilter($keyManager)
    {
        $this->addFieldToFilter('manager_key', array('like' => $keyManager));

        return $this;
    }

    /**
     * Exclude id from the list
     *
     * @param string $id
     * @return Emv_Core_Model_Mysql4_Account_Collection
     */
    public function addExcludeIdFilter($id)
    {
        $this->addFieldToFilter('id', array('neq' => $id));

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Varien_Data_Collection::toOptionArray()
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('id', 'name');
    }

}