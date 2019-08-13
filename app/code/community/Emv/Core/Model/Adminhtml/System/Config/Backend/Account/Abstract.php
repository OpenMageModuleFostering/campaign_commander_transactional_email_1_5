<?php
/**
 * Account Backend model
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
abstract class Emv_Core_Model_Adminhtml_System_Config_Backend_Account_Abstract extends Mage_Core_Model_Config_Data
{
    /**
     * @var string
     */
    protected $_urlType = '';

    /**
     * @var Emv_Core_Model_Account
     */
    protected $_account;

    /**
     * @return Emv_Core_Model_Service_Abstract
     */
    protected abstract function _getService();

    /**
     * Check if account is valid
     *  - posesses a valid url
     *  - is allowed to use the defined service
     *
     * (non-PHPdoc)
     * @see Mage_Core_Model_Abstract::_beforeSave()
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
                $service = $this->_getService();
                $service->setAccount($account);
                $service->checkConnection();
            } catch (EmailVision_Api_Exception $e) {
                if ($e->isRecoverable()) {
                    Mage::throwException(
                        Mage::helper('emvcore')
                            ->__('Could not verify the account. Network problems occured! Please Save again!')
                    );
                } else {
                    $labels = Emv_Core_Model_Account::getUrlTypesAndLabels();
                    $serviceLabel = $this->_urlType;
                    if (isset($labels[$this->_urlType])) {
                        $serviceLabel = $labels[$this->_urlType];
                    }
                    Mage::throwException(
                        Mage::helper('emvcore')
                            ->__('Your account "%s" is not allowed for %s !', $account->getName(), $serviceLabel)
                    );
                }
            }
        }
        return parent::_beforeSave();
    }

    /**
     * Get EmailVision account
     *
     * @return Emv_Core_Model_Account
     */
    protected function _getAccount()
    {
        if ($this->_account == null) {
            $this->_account = Mage::getModel('emvcore/account')->load($this->getValue());
        }
        return $this->_account;
    }

    /**
     * @param Emv_Core_Model_Account $account
     * @param string $type
     * @return boolean|string
     */
    protected function _checkAndGetUrlForType(Emv_Core_Model_Account $account, $type)
    {
        return Mage::helper('emvcore')->checkAndGetUrlForType($account, $type);
    }
}
