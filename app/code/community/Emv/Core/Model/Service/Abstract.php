<?php
/**
 * Service Abstract class
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
abstract class Emv_Core_Model_Service_Abstract extends Mage_Core_Model_Abstract
{
    /**
     * Api service list separted by store
     *
     * @var array
     */
    protected $_apiService = array();

    /**
     * EmailVision Account
     * @var Emv_Core_Model_Account
     */
    protected $_account;

    /**
     * Url type
     * @var string
     */
    protected $_urlType = null;

    /**
     * Get api service
     *
     * @param string $storeId
     * @throws Mage_Core_Exception
     * @return EmailVision_Api_Common
     */
    public function getApiService(Emv_Core_Model_Account $account = null, $storeId = null)
    {
        if (!$account) {
            $account = $this->getAccount();
            if (!$account) {
                $account = Mage::getModel('emvcore/account');
            }
        }

        $key = 'store_id' . $storeId;
        if ($account != null && $account->getId()) {
            $key .= '-account_id-' . $account->getId();
        } else {
            $key .= '-account_id-undefined';
        }

        if (!isset($this->_apiService[$key])) {
            $wsdl = $account->getUrlForType($this->_urlType);

            if (!$wsdl) {
                // prepare service label
                $labels = Emv_Core_Model_Account::getUrlTypesAndLabels();
                $serviceLabel = $this->_urlType;
                if (isset($labels[$this->_urlType])) {
                    $serviceLabel = $labels[$this->_urlType];
                }

                Mage::throwException(
                    Mage::helper('emvcore')->__('Please give a valid url for %s !', $serviceLabel)
                );
            }

            // prepare option
            $options = array('wsdl' => $wsdl);
            if ($account != null && $account->getUseProxy()) {
                if ($account->getData('proxy_host')) {
                    $options['proxy_host'] = $account->getData('proxy_host');
                }
                if ($account->getData('proxy_port')) {
                    $options['proxy_port'] = $account->getData('proxy_port');
                }
            }

            // create api object
            $api = $this->createNewService($account, $options);
            $this->_apiService[$key] = $api;
        }

        return $this->_apiService[$key];
    }

    /**
     * Create a new service from account and option
     *
     * @param Emv_Core_Model_Account $account
     * @param array $account
     * @return EmailVision_Api_Common
     */
    public abstract function createNewService(Emv_Core_Model_Account $account, $options = array());

    /**
     * Set SmartFocus account
     * @param Emv_Core_Model_Account $account
     * @return Emv_Core_Model_Service_Transactional
     */
    public function setAccount(Emv_Core_Model_Account $account)
    {
        $this->_account = $account;
        return $this;
    }

    /**
     * Get SmartFocus
     *
     * @return Emv_Core_Model_Account
     */
    public function getAccount()
    {
        return $this->_account;
    }

    /**
     * Check if API credential is valid. Return the token if success
     *
     * @throws EmailVision_Api_Exception $exception api error
     * @throws Exception - in case of errors
     * @return string
     */
    public function checkConnection()
    {
        $service = $this->getApiService($this->getAccount());
        return $service->openApiConnection();
    }

    /**
     * Throw exception
     *
     * @throws Mage_Core_Exception
     * @param Exception $e
     */
    public function throwException(Exception $e)
    {
        if ($e instanceof EmailVision_Api_Exception) {
            // network problem or server errors
            if ($e->isRecoverable()) {
                Mage::throwException(
                    Mage::helper('emvcore')
                        ->__('Network problems occured ! Please verify the service url or your network settings!')
                );
            }

            switch ($e->getCode()) {
                case EmailVision_Api_Exception::EXPIRED_SECURITY_TOKEN :
                    Mage::throwException(
                        Mage::helper('emvcore')->__('Your token has expired! Please retry your request again !')
                    );
                    break;
                case EmailVision_Api_Exception::INVALID_CREDENTIAL :
                    Mage::throwException(
                        Mage::helper('emvcore')->__('Your API credential is not valid ! Please correct it !')
                    );
                    break;
                case EmailVision_Api_Exception::INVALID_TEMPLATE_ID :
                    Mage::throwException(
                        Mage::helper('emvcore')
                            ->__('Your SmartFocus template is not found! Please select a new one!')
                    );
                    break;
                case EmailVision_Api_Exception::APPLICATION_ERROR :
                default :
                    Mage::throwException(Mage::helper('emvcore')->__('Unknown error with SmartFocus webservice'));
                    break;
            }
        } else {
            Mage::throwException($e->getMessage());
        }
    }
}