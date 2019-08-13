<?php
/**
 * Transactional service - Handle EmailVision transactional email template content
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Service_Transactional extends Emv_Core_Model_Service_Abstract
{
    /**
     * Url type
     * @var string
     */
    protected $_urlType = Emv_Core_Model_Account::URL_TRANSACTIONAL_SERVICE_TYPE;

    const PAGE_SIZE = 50;

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_Service_Abstract::createNewService()
     * @return EmailVision_Api_TransactionalService
     */
    public function createNewService(Emv_Core_Model_Account $account, $options = array()){
        // create api object
        $api = new EmailVision_Api_TransactionalService($options);
        $api->setApiCredentials(array(
            'login' => $account->getAccountLogin(),
            'pwd'   => $account->getAccountPassword(),
            'key'   => $account->getManagerKey()
        ));

        return $api;
    }
    /**
     * Get SmartFocus template by Id
     *
     * @param string $templateId
     * @return stdClass
     */
    public function getTemplateById($templateId)
    {
        $emvTemplate = null;
        $service = $this->getApiService();
        try {
            $emvTemplate = $service->getTemplateById($templateId);
        } catch (Exception $e) {
            // log errors
            Mage::logException($e);
            $this->throwException($e);
        }

        return $emvTemplate;
    }

    /**
     * @param string $from  date with format 'YYYY-MM-DDTHH:MM:SS'
     * @param string $to    date with format 'YYYY-MM-DDTHH:MM:SS'
     * @return array
     */
    public function getTemplatesByPeriod($from, $to)
    {
        $list = array();
        $service = $this->getApiService();
        try {
            $cpt = 1;
            $next = true;
            while($next) {
                $return = $service->getTemplateSummmaryList(
                    $cpt,
                    self::PAGE_SIZE,
                    array('from' => $from, 'to' => $to),
                    true
                );

                if ($return && is_array($return) && $return['list_retreived'] >0) {
                    $list = array_merge($list, $return['list_retreived']);
                    if ($return['next_page'] == 0) {
                        $next = false;
                    } else {
                        $cpt++;
                    }
                } else {
                    $next = false;
                }
            }
        } catch (Exception $e) {
            // log errors
            Mage::logException($e);
            $this->throwException($e);
        }

        return $list;
    }

    /**
     * Check if a default send template exist,
     *  - if so return it
     *  - else create a new one and then return the created one
     *
     * @return NULL | stdClass
     */
    public function checkOrCreateEmvDefaultSendTemplate($closeApi = true)
    {
        $defaultTemplate = null;
        try {
            $service = $this->getApiService();

            $defaultTemplate = $service->getDefaultTemplateSend(
                EmailVision_Api_TransactionalService::DEFAULT_TEMPLATE_SEND, false
            );

            if (!$defaultTemplate) {
                $defaultId = $service->createDefaultEmvSendTemplate(
                    EmailVision_Api_TransactionalService::DEFAULT_TEMPLATE_SEND,
                    false
                );
                if ($defaultId) {
                    $defaultTemplate = $serivce->getTemplateById($templateId, false);
                }
            }

            if ($closeApi) {
                $service->closeApiConnection();
            }

        } catch(Exception $e) {
            // log errors
            Mage::logException($e);
            $this->throwException($e);
        }

        if (!$defaultTemplate) {
            Mage::throwException(Mage::helper('emvcore')->__('Can not create SmartFocus default sending template'));
        }
        return $defaultTemplate;
    }

    /**
     * @param string $templateId
     * @param array $attributes('EMV_DYN' or 'EMV_CONTENT')
     * @return false | string
     */
    public function getPreviewTemplate($templateId, $attributes = array())
    {
        $preview = false;
        try {
            $service = $this->getApiService();
            $preview = $service->getTemplatePreviewWithDynContent($templateId, $attributes);
        } catch(Exception $e) {
            // log errors
            Mage::logException($e);
            $this->throwException($e);
        }
        return $preview;
    }
}