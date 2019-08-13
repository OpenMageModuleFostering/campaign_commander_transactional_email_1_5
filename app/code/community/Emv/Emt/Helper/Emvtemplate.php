<?php
/**
 * EmailVision Email template Helper
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Helper_Emvtemplate extends Mage_Core_Helper_Abstract
{
    /**
     * Xml path for different configurations
     */
    const XML_PATH_LOG_ENABLED = 'emvemt/transactional_service/log_enabled';
    const XML_PATH_SENDING_PARAMETER_LOG_ENABLED = 'emvemt/transactional_service/parameter_log_enabled';

    /**
     * SmartFocus attributes for a current SmartFocus template from registry
     * @var array
     */
    protected $_emvFields = null;

    /**
     * List of sorted mapped attributes
     * @var array
     */
    protected $_sortedMappedAttributes = null;

    /**
     * List of different accounts and their associated default templates
     *
     * @var array
     */
    protected $_accountAndDefaultTemplates = array();

    /**
     * Return an array of strings between [EMV DYN] and [EMV /DYN]
     *
     * @return array
     */
    public function getEmvAttributesFromText($text)
    {
        preg_match_all('/\[EMV\sDYN\](.*)\[EMV\s\/DYN\]/U', $text, $attributes);
        $sortedAttributes = array();
        if (isset($attributes[1])) {
            foreach($attributes[1] as $name) {
                $sortedAttributes[$name] = $name;
            }
        }
        return $sortedAttributes;
    }

    /**
     * Return an array of strings between [EMV CONTENT] and [EMV /CONTENT]
     *
     * @return array
     */
    public function getEmvAttributesContentFromText($text)
    {
        preg_match_all('/\[EMV\sCONTENT\](.*)\[EMV\s\/CONTENT\]/U', $text, $attributes);

        $sortedAttributes = array();
        if (isset($attributes[1])) {
            foreach($attributes[1] as $name) {
                $sortedAttributes[$name] = $name;
            }
        }

        return $sortedAttributes;
    }

    /**
     * Get all attributes (dyn content and dyn attributes)
     *
     * @param stdClass $template
     * @param string $emvTemplateId
     * @param string $emvAccountId
     * @return array
     */
    public function getAllDynContentAndAttributes(stdClass $template = null, $emvTemplateId = '', $emvAccountId ='')
    {
        $text = '';
        if ($template) {
            $text = '';
            if (isset($template->body)) {
                $text .= $template->body;
            }
            if (isset($template->from)) {
                $text .= $template->from;
            }
            if (isset($template->to)) {
                $text .= $template->to;
            }
            if(isset($template->replyTo)) {
                $text .= $template->replyTo;
            }
            if(isset($template->replyToEmail)) {
                $text .= $template->replyToEmail;
            }
            if(isset($template->subject)) {
                $text .= $template->subject;
            }
        }

        $result = array(
            Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_CONTENT    => $this->getEmvAttributesContentFromText($text),
            Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_DYN        => $this->getEmvAttributesFromText($text)
        );
        return $result;
    }

    /**
     * Get data necessary for email sending
     *
     * @param string $mageTemplateId
     * @param string $accountId
     * @return array
     */
    public function getDataForSending($mageTemplateId, $accountId)
    {
        $collection = $this->getCollectionForSending($mageTemplateId, $accountId);
        $result = $collection->getFirstItem();

        // prepare account
        $account = Mage::getModel('emvcore/account');
        $account->setData($result->getData());
        $account->setId($result->getData('account_id'));

        // tmp_sending
        $tmpSending = Mage::getModel('emvemt/emt');
        $tmpSending->setEmvParameters($result->getData('sending_template.emv_parameters'));
        $tmpSending->setEmvTemplateId($result->getData('sending_template.emv_template_id'));

        // prepare magento template name and original template name
        $mageTemplateName = $result->getData('magento_template');
        $origTemplateName = $result->getData('original_magento_template');
        if (!$result->getData('magento_template') || !$result->getData('original_magento_template')) {
            $magentoTemplates = Mage::getResourceModel('core/email_template_collection');
            $magentoTemplates->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                // only get template_id, template_code, orig_template_code
                ->columns(array('template_id', 'template_code', 'orig_template_code'));
            $magentoTemplates->addFieldToFilter('template_id ', array('eq' => $mageTemplateId));
            $template = $magentoTemplates->getFirstItem();

            $mageTemplateName = $template->getData('template_code');
            $origTemplateName = $template->getData('orig_template_code');
        }

        $data = array(
            'emv_template'              => $result,
            'account'                   => $account,
            'tmp_sending'               => $tmpSending,

            'magento_template_name'     => ($mageTemplateName != null) ? $mageTemplateName : $mageTemplateId,
            'original_magento_template_name' => ($origTemplateName != null) ? $origTemplateName : $mageTemplateId
        );

        return $data;
    }

    /**
     * Get collection to retreive objects for email sending
     *
     * @param string $mageTemplateId
     * @param string $accountId
     * @return Emv_Emt_Model_Mysql4_Emt_Collection
     */
    public function getCollectionForSending($mageTemplateId, $accountId)
    {
        $emtModel = Mage::getModel('emvemt/emt');
        $collection = $emtModel->getCollection();

        $select = $collection->getSelect();
        $resource = Mage::getSingleton('core/resource');
        $emtTable = $resource->getTableName('emvemt/emt');
        $accountTable = $resource->getTableName('emvcore/account');
        $magentoEmailTable = $resource->getTableName('core/email_template');

        // get SmartFocus sending template
        $codition = 'tmp_sending.emv_account_id  = main_table.emv_account_id  AND tmp_sending.mage_template_id = "'
            . Emv_Emt_Model_Emt::MAGENTO_TEMPLATE_ID_FOR_EMV_SEND . '"'
            . ' AND tmp_sending.emv_send_mail_mode_id = ' . Emv_Emt_Model_Mailmode::EMV_CREATE
            ;
        $select->joinLeft(
                array('tmp_sending'  => $emtTable),
                $codition,
                array(
                    'sending_template.emv_template_id' => 'tmp_sending.emv_template_id',
                    'sending_template.emv_parameters' => 'tmp_sending.emv_parameters',
                )
        );

        // get information for associated account
        $select->join(
            array('account'  => $accountTable),
            'account.id  = main_table.emv_account_id',
            array(
                'account_id' => 'id',
                'account_name' => 'name',
                'account_login',
                'account_password',
                'manager_key',
                'use_proxy',
                'proxy_host',
                'proxy_port',
                'emv_urls'
            )
        );

        // get magento template code, orignal template code
        $select->join(
            array('magento_template'  => $magentoEmailTable),
            'magento_template.template_id  = main_table.mage_template_id',
            array(
                'magento_template' => 'template_code',
                'original_magento_template' => 'orig_template_code'
            )
        );

        $select->where('main_table.emv_account_id = ?', $accountId);
        $select->where('main_table.mage_template_id = ?', $mageTemplateId);

        return $collection;
    }

    /**
     * Need to log sending?
     * @return boolean
     */
    public function needToLogSending($storeId = null)
    {
        return (bool)Mage::getStoreConfigFlag(self::XML_PATH_LOG_ENABLED, $storeId);
    }

    /**
     * Need to log all Sending parameters ?
     * @return boolean
     */
    public function needToLogEmvParameters($storeId = null)
    {
        return (bool)Mage::getStoreConfigFlag(self::XML_PATH_SENDING_PARAMETER_LOG_ENABLED, $storeId);
    }

    /**
     * Prepare Log before sending email
     *
     * @param string $mode
     * @param array $sendingData
     * @param array | string $emails
     * @param string $storeId
     * @param string $type
     * @return Emv_Emt_Model_Log
     */
    public function prepareLogBeforeSend($mode, $sendingData, $emails, $storeId = null, $type = Emv_Emt_Model_Log::SENDING_BASIC_WORKFLOW)
    {
        $log = Mage::getModel('emvemt/log');
        $log->setSendingMode($mode);
        $log->setEmail($emails);
        $log->setSendingType($type);
        $log->setStoreId($storeId);

        if (isset($sendingData['account']) &&  $sendingData['account']->getId()) {
            $log->setAccountId($sendingData['account']->getId());
        }

        $log->setMagentoTemplateName($sendingData['magento_template_name']);
        $log->setOriginalMagentoTemplateName($sendingData['original_magento_template_name']);

        return $log;
    }

    /**
     * Prepare log after sending email
     *
     * @param Emv_Emt_Model_Log $log
     * @param array $lastData
     * @param array $emails
     * @param boolean $sucess
     * @param array $error
     * @return Emv_Emt_Model_Log
     */
    public function prepareLogAfterSend(Emv_Emt_Model_Log $log, $lastData = array(), $emails, $sucess, $error = array(), $storeId = null)
    {
        $log->setSentSucess($sucess);

        $needToSave = $this->needToLogSending($storeId);

        if (isset($lastData['emv_name'])) {
            $log->setEmvName($lastData['emv_name']);
        }

        // set email
        $log->setEmail($emails);

        if (!empty($error)) {
            // only log error email
            $needToSave = true;
            if (isset($error['msg'])) {
                $log->setError($error['msg']);
            }
            if (isset($error['code'])) {
                $log->setErrorCode($error['code']);
            }
        }

        if ($this->needToLogEmvParameters($storeId) || !empty($error)) {
            if (isset($lastData['emv_encrypt']) && isset($lastData['emv_id']) && isset($lastData['emv_random'])) {
                $log->setEmvParams(
                    array(
                        'emv_encrypt' => $lastData['emv_encrypt'],
                        'emv_id'      => $lastData['emv_id'],
                        'emv_random'  => $lastData['emv_random'],
                        'emv_name'    => $lastData['emv_name'],
                    )
                );
            }
            if (isset($lastData['emv_content_variables'])) {
                $log->setEmvContentVariables($lastData['emv_content_variables']);
            }
            if (isset($lastData['emv_dyn_variables'])) {
                $log->setEmvDynVariables($lastData['emv_dyn_variables']);
            }
        }

        if ($needToSave) {
            // save in the new object
            $log->unsId();

            try {
                $log->save();
            } catch(Exception $e) {
                Mage::logException($e);
            }
        }

        return $log;
    }

    /**
     * Check if EmailVision parameters are correct
     * @param array $params
     * @return boolean
     */
    public function validateEmvParams($params = array())
    {
        $result = true;
        if (
            isset($params['emv_encrypt']) == false
            || isset($params['emv_random']) == false
            || isset($params['emv_id']) == false
            || isset($params['emv_name']) == false
        ) {
            // throw exception
            Mage::throwException('SmartFocus template parameters are not valid !');
        }

        return $result;
    }

    /**
     * @param Emv_Core_Model_Account $account
     */
    public function checkAndCreateDefaultSendingTemplate(Emv_Core_Model_Account $account)
    {
        $defaultEmt = Mage::getModel('emvemt/emt');
        $defaultEmt->setAccount($account);

        $existingDefault = $this->getEmvEmt(Emv_Emt_Model_Emt::MAGENTO_TEMPLATE_ID_FOR_EMV_SEND, $account->getId());
        $needToCreate = false;
        if ($existingDefault && $existingDefault->getId()) {
            if (!$existingDefault->getEmvTemplate()) {
                $needToCreate = true;
                $defaultEmt->setId($existingDefault->getId());
            } else {
                $defaultEmt = $existingDefault;
            }
        } else {
            $needToCreate = true;
        }

        if ($needToCreate) {
            $service = Mage::getModel('emvcore/service_transactional');
            $service->setAccount($account);
            $emvTemplate = $service->checkOrCreateEmvDefaultSendTemplate();
            if ($emvTemplate) {
                $defaultEmt->setEmvTemplate($emvTemplate);
                $defaultEmt->setEmvSendMailModeId(Emv_Emt_Model_Mailmode::EMV_CREATE);
                $defaultEmt->setMageTemplateId(Emv_Emt_Model_Emt::MAGENTO_TEMPLATE_ID_FOR_EMV_SEND);
                $defaultEmt->save();
            }
        }

        return $defaultEmt;
    }

    /**
     * Get an account model and its associated default template
     *
     * @param string $accountId
     * @return array :
     *   - account
     *   - tmp_sending
     */
    public function getAccountAndDefaultTemplate($accountId)
    {
        if (!isset($this->_accountAndDefaultTemplates[$accountId])) {
            $account = Mage::getModel('emvcore/account')->load($accountId);

            $defaultEmt = Mage::getModel('emvemt/emt');
            $existingEmt = $this->getEmvEmt(Emv_Emt_Model_Emt::MAGENTO_TEMPLATE_ID_FOR_EMV_SEND, $accountId);
            if ($existingEmt && $existingEmt->getId()) {
                $defaultEmt = $existingEmt;
            }

            $this->_accountAndDefaultTemplates[$accountId] = array('account' => $account, 'tmp_sending' => $defaultEmt);
        }

        return $this->_accountAndDefaultTemplates[$accountId];
    }

    /**
     * Get SmartFocus mapped template for a given magento template id and account id
     *
     * @param string $mageTemplateId
     * @param string $accountId
     * @return Emv_Emt_Model_Emt
     */
    public function getEmvEmt($mageTemplateId, $accountId)
    {
        $emvCollection = Mage::getResourceModel('emvemt/emt_collection');
        $emvCollection->addAccountFilter($accountId)
            ->addMageTemplateFilter($mageTemplateId);
        $emt = $emvCollection->getFirstItem();

        return $emt;
    }

    /**
     * Get all dynamic and content attributes for a current SmartFocus template from registry
     *  - if we have some problems => return empty array
     *
     * @return array | false - if we have a problem to connect to the webservice
     */
    public function getAllEmvFieldsFromRegistry()
    {
        if ($this->_emvFields === null) {
            $availableAttributes = Mage::registry(Emv_Emt_Adminhtml_TemplateController::EMV_AVAILABLE_ATTRIBUTE_REGISTRY);
            if ($availableAttributes) {
                $this->_emvFields = $availableAttributes;
            } else {
                $invalidField = Mage::registry(Emv_Emt_Adminhtml_TemplateController::INVALID_FIELD_EMV_TEMPLATE_REGISTRY);
                $currentEmv = Mage::registry(Emv_Emt_Adminhtml_TemplateController::CURRENT_EMVEMT_TEMPLATE_REGISTRY);

                $this->_emvFields = array();
                if (!$invalidField || !isset($invalidField['emv_template_id'])) {
                    if (
                        $currentEmv
                        && $currentEmv instanceof Emv_Emt_Model_Emt
                        && $currentEmv->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_CREATE
                    ) {
                        try {
                            if ($currentEmv->getEmvTemplate()) {
                                $this->_emvFields = $this->getAllDynContentAndAttributes($currentEmv->getEmvTemplate());
                            }
                        } catch (Exception $e) {
                            Mage::logException($e);
                            $this->_emvFields = false;
                        }
                    }
                }
            }
        }

        return $this->_emvFields;
    }

    /**
     * Prepare a list of all mapped attributes from registry.
     * The mapped attributes are sorted by their SmartFocus attribute name
     *
     * @return array
     */
    public function getSortedMappedAttributesFromRegistry()
    {
        if ($this->_sortedMappedAttributes === null) {
            $attributeData = Mage::registry(Emv_Emt_Adminhtml_TemplateController::EMV_ATTRIBUTES_REGISTRY);
            $this->_sortedMappedAttributes = array();
            if ($attributeData && is_array($attributeData)) {
                foreach ($attributeData as $data) {
                    if (!isset($this->_sortedMappedAttributes[$data['emv_attribute_type']])) {
                        $this->_sortedMappedAttributes[$data['emv_attribute_type']] = array();
                    }
                    $this->_sortedMappedAttributes[$data['emv_attribute_type']][$data['emv_attribute']] = $data;
                }
            }
        }

        return $this->_sortedMappedAttributes;
    }
}
