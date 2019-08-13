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
            $account = $this->_getAccount();
            if (!$account->getId()) {
                Mage::throwException(Mage::helper('emvcore')->__('Your selected account does not exist anymore!'));
            }

            // check url
            $url = $this->_checkAndGetUrlForType($account, $this->_urlType);

            try {
                // check credentials
                Mage::helper('emvemt/emvtemplate')->checkAndCreateDefaultSendingTemplate($account);
            } catch (EmailVision_Api_Exception $e) {
                if ($e->isRecoverable()) {
                    Mage::throwException(
                        Mage::helper('emvcore')
                            ->__('Could not verify the account. Network problems occured! Please Save again!')
                    );
                } else {
                    Mage::throwException($e->getMessage());
                }
            }

            $this->_checkAndGetUrlForType($account, Emv_Core_Model_Account::URL_REST_NOTIFICATION_SERVICE_TYPE);
        }

        return Mage_Core_Model_Abstract::_beforeSave();
    }
}
