<?php
/**
 * Batch Member service - Update member data by files
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Service_BatchMember extends Emv_Core_Model_Service_Abstract
{
    /**
     * Url type
     * @var string
     */
    protected $_urlType = Emv_Core_Model_Account::URL_BATCH_MEMBER_SERVICE_TYPE;

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_Service_Abstract::createNewService()
     * @return EmailVision_Api_BatchMemberService
     */
    public function createNewService(Emv_Core_Model_Account $account, $options = array()){
        // create api object
        $api = new EmailVision_Api_BatchMemberService($options);
        $api->setApiCredentials(array(
            'login' => $account->getAccountLogin(),
            'pwd'   => $account->getAccountPassword(),
            'key'   => $account->getManagerKey()
        ));

        return $api;
    }
}