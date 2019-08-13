<?php
/**
 * Account Backend model for Transactionnal Messages(NMP)
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_Emt_Model_Adminhtml_System_Config_Backend_Account extends Emv_Core_Model_Adminhtml_System_Config_Backend_Account_Abstract
{
    /**
     * @var string
     */
    protected $_urlType = Emv_Core_Model_Account::URL_TRANSACTIONAL_SERVICE_TYPE;

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_Adminhtml_System_Config_Backend_Account_Abstract::_getService()
     */
    protected function _getService()
    {
        return Mage::getModel('emvcore/service_transactional');
    }

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_Adminhtml_System_Config_Backend_Account_Abstract::_beforeSave()
     */
    protected function _beforeSave()
    {
        if ($this->isValueChanged() && $this->getValue()) {
            Mage::helper('emvemt/emvtemplate')->validateEmvAccount($this->getValue());
        }

        return Mage_Core_Model_Abstract::_beforeSave();
    }
}
