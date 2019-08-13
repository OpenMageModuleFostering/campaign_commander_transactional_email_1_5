<?php
/**
 * Emailvision account class
 * emv_urls => $type => array('url' => $url)
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Account extends Mage_Core_Model_Abstract
{
    /**
     * Constant types
     */
    const URL_TRANSACTIONAL_SERVICE_TYPE = 'transactional';
    const URL_REST_NOTIFICATION_SERVICE_TYPE = 'rest_notification';
    const URL_BATCH_MEMBER_SERVICE_TYPE = 'batch_member';
    const URL_MEMBER_SERVICE_TYPE = 'member';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'emv_account';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'emv_account';

    /**
     * Constructor
     *
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
         $this->_init('emvcore/account');
    }

    /**
     * Get Allowed Url Types
     *
     * @return array
     */
    public static function getAllowedUrlTypes()
    {
        return array(
            self::URL_TRANSACTIONAL_SERVICE_TYPE,
            self::URL_REST_NOTIFICATION_SERVICE_TYPE,
            self::URL_BATCH_MEMBER_SERVICE_TYPE,
            self::URL_MEMBER_SERVICE_TYPE
        );
    }

    /**
     * @return array
     */
    public static function getUrlTypesToTestCredential()
    {
        return array(
            self::URL_TRANSACTIONAL_SERVICE_TYPE,
            self::URL_BATCH_MEMBER_SERVICE_TYPE,
            self::URL_MEMBER_SERVICE_TYPE
        );
    }

    /**
     * Get available url types that account can have
     *
     * @return array
     */
    public function getAvailableUrlTypesToEnter()
    {
        $emvUrls = $this->getEmvUrls();
        $allAvailable = self::getAllowedUrlTypes();
        $available = $allAvailable;
        if (is_array($emvUrls) && count($emvUrls)) {
            // try to take the rest of types
            $usedTypes = array_keys($emvUrls);
            $available = array_diff($allAvailable, $usedTypes);
        }

        $options = array();
        $servicesAndLabels = self::getUrlTypesAndLabels();
        foreach($available as $type) {
            if (isset($servicesAndLabels[$type])) {
                $options[] = array(
                    'value' => $type,
                    'label' => $servicesAndLabels[$type]
                );
            }
        }

        return $options;
    }

    /**
     * Get url types and labels
     *
     * @return array
     */
    public static function getUrlTypesAndLabels()
    {
        return array (
            self::URL_TRANSACTIONAL_SERVICE_TYPE => Mage::helper('emvcore')->__('Transactional Service'),
            self::URL_REST_NOTIFICATION_SERVICE_TYPE => Mage::helper('emvcore')->__('REST Notification Service'),
            self::URL_BATCH_MEMBER_SERVICE_TYPE => Mage::helper('emvcore')->__('Batch Member Service'),
            self::URL_MEMBER_SERVICE_TYPE => Mage::helper('emvcore')->__('Member Service')
        );
    }

    /**
     * All urls need to be serialized before saving
     *
     * (non-PHPdoc)
     * @see Mage_Core_Model_Abstract::_beforeSave()
     */
    protected function _beforeSave()
    {
        $emvUrls = $this->getData('emv_urls');
        if ($emvUrls && is_array($emvUrls)) {
            $this->setData('emv_urls', serialize($emvUrls));
        }

        // update datetime field
        $gmtDate = Mage::getModel('core/date')->gmtDate();
        if (!$this->getId()) {
            $this->setData('created_at', $gmtDate);
        }
        $this->setData('updated_at', $gmtDate);

        return parent::_beforeSave();
    }

    /**
     * Get emv urls
     * @return array
     */
    public function getEmvUrls()
    {
        $emvUrls = $this->getData('emv_urls');
        if ($emvUrls && !is_array($emvUrls)) {
            $this->setData('emv_urls', unserialize($emvUrls));
        }

        return $this->getData('emv_urls');
    }

    /**
     * Validate account - if everything is ok, return true - else the list of erros
     *
     * @return boolean|array
     */
    public function validate()
    {
        $errors = array();

        if (!Zend_Validate::is($this->getName(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvcore')->__('Name is required.');
        }

        if (!Zend_Validate::is($this->getAccountLogin(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvcore')->__('API login is required.');
        }

        if (!Zend_Validate::is($this->getAccountPassword(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvcore')->__('API password is required.');
        }

        if (!Zend_Validate::is($this->getManagerKey(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvcore')->__('API Manager key is required field.');
        }

        // proxy
        if($this->getUse_proxy() && (null === $this->getProxyHost() || null === $this->getProxyPort()))
        {
            $errors[] = Mage::helper('emvcore')->__('Proxy host and port are required.');
        }

        // check account name
        if ($this->nameExists()) {
            $errors[] = Mage::helper('emvcore')->__('Account with the same name already exists.');
        }

        // check manager key
        if ($this->managerKeyExists()) {
            $errors[] = Mage::helper('emvcore')
                ->__('Account with the same API manager key already exists.');
        }

        $apiErrors = $this->validateApiCredential();
        if (count($apiErrors) > 0) {
            $errors = array_merge($errors, $apiErrors);
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Validate api credential
     *
     * @return array
     */
    public function validateApiCredential()
    {
        $errors = array();

        // check whether all given urls are valid
        $invalidUrls = $this->checkEmvUrls();
        if (count($invalidUrls)) {
            $errors = array_merge($errors, $invalidUrls);
        } else {
            $typeToTest = '';
            $url = false;
            $labels = array();

            // service urls
            $emvUrls = $this->getData('emv_urls');
            // find a service url to check credential
            foreach (self::getUrlTypesToTestCredential() as $type) {
                if (isset($emvUrls[$type])) {
                    $typeToTest = $type;
                    $url = $emvUrls[$type]['url'];
                    break;
                } else {
                    $servicesAndLabels = self::getUrlTypesAndLabels();
                    if (isset($servicesAndLabels[$type])) {
                        $labels[] = $servicesAndLabels[$type];
                    }
                }
            }

            if (!$url || !$typeToTest) {
                $errors[] = Mage::helper('emvcore')
                    ->__('You need to enter at least one of the following service(s) : %s', implode(', ', $labels));
            } else {
                $serviceClient = null;
                switch($typeToTest) {
                    case self::URL_TRANSACTIONAL_SERVICE_TYPE:
                        $serviceClient = Mage::getModel('emvcore/service_transactional');
                        break;
                    case self::URL_BATCH_MEMBER_SERVICE_TYPE:
                         $serviceClient = Mage::getModel('emvcore/service_batchMember');
                        break;
                    case self::URL_MEMBER_SERVICE_TYPE:
                        $serviceClient = Mage::getModel('emvcore/service_member');
                        break;
                }

                // check if account is valid
                try {
                    $serviceClient->setAccount($this);
                    $serviceClient->checkConnection();
                } catch (EmailVision_Api_Exception $e) {
                     if ($e->isRecoverable()) {
                        $errors[] = Mage::helper('emvcore')
                            ->__('Connection problem. Could not check api credential, please try again');
                    } else {
                        $errors[] = Mage::helper('emvcore'
                            )->__('This SmartFocus account is invalid. Please check your API credential');
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Check all given SmartFocus service urls are valid.
     * Return the eventual error messages in case of error
     *
     * @return array
     */
    public function checkEmvUrls()
    {
        $errors = array();
        $emvUrls = $this->getData('emv_urls');

        if (!$emvUrls || !is_array($emvUrls)) {
            $errors[] = Mage::helper('emvcore')
                ->__('This SmartFocus account does not have any SmartFocus service url');
        } else {
            // check if the urls are valid
            foreach ($emvUrls as $type => $urlData) {
                if (!in_array($type, self::getAllowedUrlTypes())) {
                    $errors[] = Mage::helper('emvcore')->__('%s is unknown by SmartFocus platform.', $type);
                } else {
                    $invalidUrl = true;
                    if (is_array($urlData) && isset($urlData['url']) && Zend_Validate::is($urlData['url'], 'NotEmpty')) {
                        $url = $urlData['url'];

                        //get a Zend_Uri_Http object for our URL, this will only accept http(s) schemes
                        try {
                            $uriHttp = Zend_Uri_Http::fromString($url);
                            // if we have a valid URI then we check the hostname for valid TLDs, and not local urls
                            // do not allow local hostnames, this is the default
                            $hostnameValidator = new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_DNS);
                            if ($hostnameValidator->isValid($uriHttp->getHost())) {
                                $invalidUrl = false;
                            }
                        } catch (Exception $e) {
                            // here does not need to catch exception!!!
                            // Zend_Uri_Http::fromString will generate an exception if the protocol given in url is not supported
                        }
                    }

                    if ($invalidUrl) {
                        $servicesAndLabels = self::getUrlTypesAndLabels();
                        if (isset($servicesAndLabels[$type])) {
                            $errors[] = Mage::helper('emvcore')
                                ->__('Please enter a valid url for %s!', $servicesAndLabels[$type]);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get url for type
     * - if type is not allowed or not defined return false,
     * - else the predefined url
     *
     * @param string $type
     * @return boolean|string
     */
    public function getUrlForType($type)
    {
        $url = false;
        if (in_array($type, self::getAllowedUrlTypes())) {
            $emvUrls = $this->getEmvUrls();
            if (isset($emvUrls[$type]) && isset($emvUrls[$type]['url'])) {
                $url = $emvUrls[$type]['url'];
            }
        }
        return $url;
    }

    /**
     * Check if account name exists in database
     *
     * @return boolean
     */
    public function nameExists()
    {
        $result = $this->_getResource()->nameExists($this);
        return (is_array($result) && count($result) > 0 ) ? true : false;
    }

    /**
     * Check if manager key exists in database
     * @return boolean
     */
    public function managerKeyExists()
    {
        $result = $this->_getResource()->managerKeyExists($this);
        return (is_array($result) && count($result) > 0 ) ? true : false;
    }
}