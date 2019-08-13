<?php
/**
 * Account Backend model for Scheduled Exports
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Adminhtml_System_Config_Backend_BatchMember_Account
    extends Emv_Core_Model_Adminhtml_System_Config_Backend_Account_Abstract
{
    /**
     * @var string
     */
    protected $_urlType = Emv_Core_Model_Account::URL_BATCH_MEMBER_SERVICE_TYPE;

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_Adminhtml_System_Config_Backend_Account_Abstract::_getService()
     */
    protected function _getService()
    {
        return Mage::getModel('emvcore/service_batchMember');
    }
}
