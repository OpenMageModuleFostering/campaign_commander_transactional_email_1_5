<?php
/**
 * Member service - Handle Member Data
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Service_Member extends Emv_Core_Model_Service_Abstract
{
    const FIELD_EMAIL         = 'EMAIL';
    const FIELD_SUBSCRIBER_ID = 'CLIENTURN';

    const FIELD_UNJOIN        = 'DATEUNJOIN';
    const FIELD_MEMBER_ID     = 'MEMBER_ID';
    const FIELD_CLIENT_ID     = 'CLIENT_ID';

    /**
     * Url type
     * @var string
     */
    protected $_urlType = Emv_Core_Model_Account::URL_MEMBER_SERVICE_TYPE;

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_Service_Abstract::createNewService()
     * @return EmailVision_Api_MemberService
     */
    public function createNewService(Emv_Core_Model_Account $account, $options = array()){
        // create api object
        $api = new EmailVision_Api_MemberService($options);
        $api->setApiCredentials(array(
            'login' => $account->getAccountLogin(),
            'pwd'   => $account->getAccountPassword(),
            'key'   => $account->getManagerKey()
        ));

        return $api;
    }

    /**
     * Get EmailVision member fields
     *
     * @return array
     */
    public function getEmailVisionFields()
    {
        $fields = array();
        $service = $this->getApiService();
        try {
            $fields = $service->descMemberTable();
        } catch (Exception $e) {
            // log errors
            Mage::logException($e);
            $this->throwException($e);
        }

        return $fields;
    }
}