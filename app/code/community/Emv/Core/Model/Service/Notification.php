<?php
/**
 * Notification service - handle all email sending to SmartFocus platform
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Service_Notification extends Mage_Core_Model_Abstract
{
    /**
     * XML path for default wsdl
     */
    const XML_PATH_EMV_WSDL_LIST_TRANSACTIONNAL = 'global/emv/wsdl_list';

    /**
     * Api service list separted by store
     *
     * @var array
     */
    protected $_apiService = array();

    /**
     * Get api service
     *
     * @param string $storeId
     * @return EmailVision_Api_NotificationService
     */
    public function getApiService($storeId = null, Emv_Core_Model_Account $account = null)
    {
        $key = 'store_id' . $storeId;
        if ($account != null && $account->getId()) {
            $key .= '-account_id' . $account->getId();
        }

        if (!isset($this->_apiService[$key])) {
            $restUrl = null;
            if ($account) {
                $restUrl = $account->getUrlForType(Emv_Core_Model_Account::URL_REST_NOTIFICATION_SERVICE_TYPE);
            }

            if (!$restUrl) {
                Mage::throwException(Mage::helper('emvcore')
                    ->__('The given account does not have a valid url for Notification service'));
            }

            $api = new EmailVision_Api_NotificationService(array());
            $api->setRestServiceUrl($restUrl);
            $this->_apiService[$key] = $api;
        }

        return $this->_apiService[$key];
    }

    /**
     * Send Email with given template. The rest api will be used
     *
     * @param string $encrypt
     * @param string $notificationId
     * @param string $random
     * @param string $email
     * @param array $content
     * @param array $variable
     * @param string $sendDate  - need to be in a format YYYY-MM-DDThh:mm:ss - 2013-01-01T00:00:00
     * @return boolean | string
     * @throws EmailVision_Api_Exception
     */
    public function sendTemplate(
        $encrypt, $notificationId, $random,
        $email, $content = array(), $variable = array(),
        Emv_Core_Model_Account $account = null,
        $storeId = null,
        $sendDate = '2013-01-01T00:00:00'
    )
    {
        // get api service
        $apiService = $this->getApiService($storeId, $account);

        $result = '';
        $proxyPort = null;
        $proxyHost = null;
        if ($account != null && $account->getUseProxy()) {
            $proxyPort = $account->getData('proxy_port');
            $proxyHost = $account->getData('proxy_host');
        }

        return $apiService->sendRest(
            $encrypt,
            $notificationId,
            $random,
            $email,
            $content,
            $variable,
            $sendDate,
            $proxyHost,
            $proxyPort
        );
    }
}