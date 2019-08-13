<?php
/**
 * Associated Urls block for account form
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_Account_Edit_AssociatedUrls extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset_Element
{
    /**
     * XML path for default wsdl
     */
    const XML_PATH_EMV_WSDL_LIST_TRANSACTIONNAL = 'global/emv/wsdl_list';

    /**
     * Set template phtml
     */
    public function __construct()
    {
        $this->setTemplate('smartfocus/account/associated_urls.phtml');
    }

    /**
     * Get webservice div id
     *
     * @return string
     */
    public function getWebserviceDivId()
    {
        return 'associated_webservices';
    }

    /**
     * Get add button id
     * @return string
     */
    public function getAddId()
    {
        return 'webservice_add';
    }

    /**
     * Get available service select id
     * @return string
     */
    public function getAvailableServiceId()
    {
        return 'available_webservices';
    }

    /**
     * @return string
     */
    public function getDefaultUrls()
    {
        $config = Mage::getConfig()->getNode(self::XML_PATH_EMV_WSDL_LIST_TRANSACTIONNAL);
        $node   = $config->rest_notification_service;
        $url    = $node ? $node->asArray() : null;
        $defaultUrls = array(
            Emv_Core_Model_Account::URL_REST_NOTIFICATION_SERVICE_TYPE => $url
        );
        return Mage::helper('core')->jsonEncode($defaultUrls);
    }
}