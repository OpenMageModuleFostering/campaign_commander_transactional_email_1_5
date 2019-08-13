<?php
/**
 * SmartFocus email template controller
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Adminhtml_TemplateController extends Mage_Adminhtml_Controller_Action
{
    /**
     *  Registry variables
     */
    const EMV_ATTRIBUTES_REGISTRY              = 'emt_attributes';
    const EMV_TEMPLATE_REGISTRY                = 'current_emv_template';
    const EMV_TEMPLATE_ARRAY_REGISTRY          = 'current_emv_template_array';
    const MAGE_TEMPLATE_NAME_REGISTRY          = 'mage_template_name';
    const CURRENT_EMVEMT_TEMPLATE_REGISTRY     = 'current_emvemt';
    const INVALID_FIELD_EMV_TEMPLATE_REGISTRY  = 'invalid_emt_fields';
    const EMV_AVAILABLE_ATTRIBUTE_REGISTRY     = 'available_emv_attributes';

    /**
     * Date format for API
     */
    const API_DATE_FORMAT = 'YYYY-MM-ddTHH:mm:ssZZZZ';

    /**
     * Check if having a correct permission
     *
     * @return boolean
     */
    protected function _isallowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/emvemt/emt');
    }

    /**
     * Inialize action
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('emailvision/emvemt');

        $this->_title(Mage::helper('emvemt')->__('SmartFocus'))
            ->_title(Mage::helper('emvemt')->__('Manage Email Templates'));
    }

    /**
     * Action to create a new campaign commander template mapping
     */
    public function newAction()
    {
        // Check if an emv account is set
        $emvAccount = Mage::getResourceModel('emvcore/account_collection');
        $accountId = $emvAccount->getFirstItem()->getId();
        if (null === $accountId) {
            // get back to template grid and display the errors
            $this->_getSession()->addError(Mage::helper('emvemt')->__('SmartFocus account has not been set !'));
            $this->_redirect('*/*/index');
            return;
        }

        $this->_initAction();
        $this->_title(Mage::helper('emvemt')->__('New Template'));

        $this->_addContent($this->getLayout()->createBlock('emvemt/adminhtml_template_edit'))
            ->_addLeft($this->getLayout()->createBlock('emvemt/adminhtml_template_edit_tabs'))
        ;
        $this->renderLayout();
    }

    /**
     * Index action - display a grid of SmartFocus templates
     */
    public function indexAction()
    {
        // save all messages from session
        $this->getLayout()->getMessagesBlock()->setMessages(
            $this->_getSession()->getMessages()
        );

        $this->_initAction();

        $this->_addContent($this->getLayout()->createBlock('emvemt/adminhtml_templates'));
        $this->renderLayout();
    }

    /**
     * EmailVision template edit action
     */
    public function editAction()
    {
        $emtModel = $this->_initEmt();

        // prepare attribute data form
        $data = $this->_getSession()->getData('edit_emt_form_data',true);
        $attributeData = array();
        if (isset($data)) {
            // set entered data if was error when we do save
            $emtModel->addData($data);
        }

        if ($emtModel->getId() && $emtModel->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_CREATE){
            // take attributes of this emt
            $collection = Mage::getResourceModel('emvemt/attribute_collection')
                ->addEmtFilter($emtModel->getId())
                ->load();

            $attributeData = $collection->getItems();
        } else {
            // get back attribute data
            if ($data && isset($data['attributes']) && is_array($data['attributes'])) {
                $attributeData = $data['attributes'];
            }
        }
        Mage::register(self::EMV_ATTRIBUTES_REGISTRY, $attributeData);

        // don't display form if account does not exist in database
        $account = $emtModel->getAccount();
        if (!$account && null !== $emtModel->getEmvAccountId()) {
            $this->_getSession()->addError(
                Mage::helper('emvemt')
                    ->__('Your SmartFocus account associated to this template does not exist anymore ! Please delete it !')
            );
            $this->_redirect('*/*/');
            return;
        }

        // try to retreive invalid fields if we just came back from save action
        // all invalid fields are stored in session
        $invalidFields = $this->_getSession()->getData('invalid_emt_fields',true);
        if (!$invalidFields) {
            $invalidFields = array();
        }

        $options = null;
        $emvParams = $emtModel->getEmailVisionParams();
        if ($emvParams && is_array($emvParams)) {
            $options = array(
                'value' => $emvParams['emv_id'],
                'label' => $emvParams['emv_name'],
            );
        }

        $templateCode = '';
        $error = array();
        try {
            if (null != $emtModel->getEmvAccountId() &&  null != $emtModel->getMageTemplateId()) {
                if (count($invalidFields) == 0) {
                    // if emt is not already tested, verify emt data
                    $error = $emtModel->validate();
                    $invalidFields = $emtModel->getInvalidFields();
                }

                // check whether magento template exists
                /* @var $magentoTemplates Mage_Core_Model_Mysql4_Email_Template_Collection */
                $magentoTemplates = Mage::getResourceModel('core/email_template_collection');
                $magentoTemplates->getSelect()->reset(Zend_Db_Select::COLUMNS)
                    ->columns(array('template_id', 'template_code')); // only get template_id, and template_code
                $magentoTemplates->addFieldToFilter('template_id ', array('eq' => $emtModel->getMageTemplateId()));
                $template = $magentoTemplates->getFirstItem();
                if (!$template->getId()) {
                    $invalidFields['mage_template_id'] = Mage::helper('emvemt')
                        ->__('Your selected Magento template does not exist. Please select a new one !');
                } else {
                    $templateCode = $template->getTemplateCode();
                }

                // to enhance the performance, only get campaign commander template if we don't have any invalid fields
                // and the template is in mode emv_create
                if (
                    (!$invalidFields || !isset($invalidFields['emv_template_id']))
                    && $emtModel->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_CREATE
                ){
                    $template = $emtModel->getEmvTemplate();
                    if ($template && isset($template->name)) {
                        Mage::register(self::EMV_TEMPLATE_REGISTRY, $template);
                        $options = array(
                            'value' => $emtModel->getEmvTemplateId(),
                            'label' => $template->name
                        );
                    }
                }

                if (is_array($error)) {
                    foreach ($error as $message) {
                        $this->_getSession()->addError($message);
                    }
                }
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        Mage::register(self::EMV_TEMPLATE_ARRAY_REGISTRY,$options);
        Mage::register(self::MAGE_TEMPLATE_NAME_REGISTRY, $templateCode);
        Mage::register(self::INVALID_FIELD_EMV_TEMPLATE_REGISTRY, $invalidFields);

        $this->_initAction();
        $this->_title(($templateCode));
        // add emt edit block form html to return
        $this->_addContent($this->getLayout()->createBlock('emvemt/adminhtml_template_edit'))
            ->_addLeft($this->getLayout()->createBlock('emvemt/adminhtml_template_edit_tabs'))
        ;
        $this->renderLayout();
    }

    /**
     * Initialise the account model.
     *
     * @return Emv_Emt_Model_Emt
     */
    protected function _initEmt()
    {
        $id = $this->getRequest()->getParam('id', null);
        $emtModel = Mage::getModel('emvemt/emt')->load($id);
        Mage::register(self::CURRENT_EMVEMT_TEMPLATE_REGISTRY, $emtModel);

        return $emtModel;
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        $emtId = null;
        if ($data = $this->getRequest()->getPost()) {
            $emtId = $this->getRequest()->getParam('id', null);
            $emtModel = Mage::getModel('emvemt/emt')->load($emtId);
            $emtModel->addData($data);
            if ($emtId != $emtModel->getId()) {
                $emtId = null;
            }

            // try to validate data
            if (null !== $emtId) {
                // we're in mode edit (2nd form)
                $result = $emtModel->validate();
                $this->_getSession()->setData('edit_emt_form_data',$data);
            } else {
                // set send mode to classic by default
                $emtModel->setEmvSendMailModeId(Emv_Emt_Model_Mailmode::CLASSIC_MODE);
                // we're in 1st form
                $result = $emtModel->validateNewForm();
            }

            if (is_array($result)) {
                $this->_getSession()->setData('invalid_emt_fields', $emtModel->getInvalidFields());
                foreach ($result as $message) {
                    $this->_getSession()->addError($message);
                }

                $this->_redirect('*/*/edit', array('id' => $emtModel->getId()));
                return;
            }

            try {
                $emtModel->save();
                $emtId = $emtModel->getId();

                if (null !== $emtId) {
                    $errors = array();
                    $treatedAttributes = array();
                    $deletedAttributes = array();

                    // validate all given attributes
                    if ($emtModel->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_CREATE) {
                        $attributes = $this->getRequest()->getParam('attributes');
                        if (is_array($attributes)) {
                            $checkAttributeForEmt = $emtModel->validateMappedAttributes(
                                $attributes
                            );
                            if (is_array($checkAttributeForEmt) && count($checkAttributeForEmt)) {
                                $errors = array_merge($errors, $checkAttributeForEmt);
                            }

                            foreach ($attributes as $attrInfo) {

                                $emvAttributeId = null;
                                if (isset($attrInfo['id']) && $attrInfo['id'] > 0) {
                                    $emvAttributeId = $attrInfo['id'];
                                }

                                $attributeModel = Mage::getModel('emvemt/attribute')
                                    ->setEmvAttribute($attrInfo['emv_attribute'])
                                    ->setEmvAttributeType($attrInfo['emv_attribute_type'])
                                    ->setMageAttribute(
                                            isset($attrInfo['mage_attribute']) ? $attrInfo['mage_attribute'] : ''
                                        )
                                    ->setEmvEmtId($emtId)
                                    ->setId($emvAttributeId)
                                    ;

                                // treat delete information
                                if (isset($attrInfo['delete']) && $attrInfo['delete'] == 1) {
                                    if ($emvAttributeId) {
                                        $deletedAttributes[] = $attributeModel;
                                    }

                                    continue;
                                }

                                $treatedAttributes[] = $attributeModel;

                                // the errors willl be merged back to the global errors array
                                $result = $attributeModel->validate();
                                if (is_array($result) && count($result)) {
                                    $errors = array_merge($errors, $result);
                                }
                            }
                        }
                    } else {
                        $this->_deleteAllEmtAttribute($emtId);
                    }

                    // if some problems occur
                    if (is_array($errors) && count($errors) >0) {
                        $this->_getSession()->setData('edit_emt_form_data',$data);
                        foreach ($errors as $message) {
                            $this->_getSession()->addError($message);
                        }
                    } else {
                        // User come from edit mode
                        // delete old mapping attribute
                        foreach($deletedAttributes as $attribute) {
                            $attribute->delete();
                        }
                        foreach ($treatedAttributes as $attribute) {
                            $attribute->save();
                        }

                        $this->_getSession()->addSuccess(
                            Mage::helper('emvemt')->__('The template was saved.')
                        );
                    }
                }
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setEditEmtFormData($data);
            }
        }

        $this->_redirect('*/*/edit', array('id' => $emtId));
        return;
    }

    /**
     * Delete all mapping attribute of an emt
     **/
    protected function _deleteAllEmtAttribute($emtId)
    {
        $collection = Mage::getResourceModel('emvemt/attribute_collection')
            ->addEmtFilter($emtId);
        $collection->walk('delete');
    }

    /**
     * Delete emt action
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $this->_deleteAllEmtAttribute($id);

                $model = Mage::getModel('emvemt/emt')->load($id);
                $model->delete();
                $this->_getSession()->addSuccess(
                    Mage::helper('emvemt')->__('The template was deleted.'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getPost('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emvemt')
            ->__('Not found template!'));
        $this->_redirect('*/*/');
    }

    /**
     * Mass delete action
     */
    public function massDeleteAction()
    {
        $emtIds = $this->getRequest()->getParam('emt');
        if (!is_array($emtIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emvemt')->__('Please select email template(s) !'));
        } else {
            try {
                // delete emt template and its attributes
                $emt = Mage::getModel('emvemt/emt');
                foreach ($emtIds as $id) {
                    $emt->setId($id)
                        ->delete();

                    $this->_deleteAllEmtAttribute($id);
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d template(s) were deleted.', count($emtIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Return an array containing emv template from periode $fromDate to $toDate and eventual errors
     */
    public function getEmvTemplateSelectAjaxAction()
    {
        $data = $this->getRequest()->getPost();
        $options = array();
        $error = '';

        if (isset($data['from']) && isset($data['to']) && isset($data['account_id'])) {
            $data['from'] = Mage::helper('core')->escapeHtml($data['from']);
            $data['to'] = Mage::helper('core')->escapeHtml($data['to']);
            $data['account_id'] = Mage::helper('core')->escapeHtml($data['account_id']);

            // prepare date format
            $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
            );

            // get our locale
            $locale = Mage::app()->getLocale()->getLocale();

            /* @var $fromDate Zend_Date */
            /* @var $toDate Zend_Date */
            if (!Zend_Date::isDate($data['from'], $dateFormatIso, $locale)) {
                //set a default large periode to match all campaign commander templates
                $fromDate = new Zend_Date('1980-01-01', 'YYYY-MM-dd', $locale);
            } else {
                $fromDate = new Zend_Date($data['from'], $dateFormatIso, $locale);
            }

            // prepare date to
            if (!Zend_Date::isDate($data['to'], $dateFormatIso, $locale)) {
                $toDate = Zend_Date::now($locale);
            } else {
                $toDate = new Zend_Date($data['to'], $dateFormatIso, $locale);
            }
            // make the period cover all the day because Zend_Date set hour to 00:00:00 by default
            // so we add 23h59m59s
            $toDate->addHour(23);
            $toDate->addMinute(59);
            $toDate->addSecond(59);

            try {
                 /* @var $account Emv_Core_Model_Account */
                $account = Mage::getModel('emvcore/account')->load($data['account_id']);
                $service = Mage::getModel('emvcore/service_transactional');
                $service->setAccount($account);
                // get all lists
                $list = $service->getTemplatesByPeriod(
                    $fromDate->toString(self::API_DATE_FORMAT),
                    $toDate->toString(self::API_DATE_FORMAT)
                );

                // prepare options (id and name)
                if (count($list)) {
                    $options[] = array('value' => '', 'label' => Mage::helper('emvemt')->__('Please select a template'));
                }
                foreach ($list as $template) {
                    $options[] = array(
                        'value' => $template->id,
                        'label' => $template->name
                    );
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        // if we don't have any template
        if (count($options) == 0 && !$error) {
            $error = Mage::helper('emvemt')->__('There isn\'t any template in this period !');
        }

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array(
                    'options' => $options,
                    'error'   => $error
                )
            )
        );
    }

    /**
     * Display a select of magento template not already mapped with the emv account given in post
     */
    public function getNotMappedMagentoTemplateSelectAjaxAction()
    {
        $data = $this->getRequest()->getPost();

        $options = array();
        $error = '';

        if (isset($data['account_id']) && $data['account_id']) {
            try {
                /* @var $magentoTemplates Mage_Core_Model_Mysql4_Email_Template_Collection */
                $magentoTemplates = Mage::getModel('emvemt/mageTemplate')
                    ->getNotMappedMageTemplateCollection($data['account_id']);

                // in case that we do not have any available magento template
                if($magentoTemplates->getSize() > 0) {
                    $options = $magentoTemplates->toOptionArray();
                }
            } catch(Exception $e) {
                $error = $e->getMessage();
            }
        }

        if (count($options) == 0 && !$error) {
            $error = Mage::helper('emvemt')->__('There is no Magento email templates which can be used for this account !');
        }

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array(
                    'options' => $options,
                    'error'   => $error
                )
            )
        );
    }

    /**
     * Get SmartFocus Attributes action
     * The return will be in JSON format
     */
    public function getEmvAttributesAjaxAction()
    {
        $data = $this->getRequest()->getPost();

        $html = array();
        $error = '';
        if (
            $data
            && isset($data['emv_template_id'])
            && isset($data['account_id'])
        ) {
            $data['emv_template_id']      = Mage::helper('core')->escapeHtml($data['emv_template_id']);
            $data['account_id']          = Mage::helper('core')->escapeHtml($data['account_id']);

            $emtModel = $this->_initEmt();
            if ($emtModel->getId() && $emtModel->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_CREATE){
                // take attributes of this template
                $collection = Mage::getResourceModel('emvemt/attribute_collection')
                    ->addEmtFilter($emtModel->getId())
                    ->load();

                Mage::register(self::EMV_ATTRIBUTES_REGISTRY, $collection->getItems());
            }
        }

        if ($data['emv_template_id']) {
            try {
                /* @var $account Emv_Core_Model_Account */
                $account = Mage::getModel('emvcore/account')->load($data['account_id']);
                $service = Mage::getModel('emvcore/service_transactional');
                $service->setAccount($account);

                // retreive SmartFocus template
                $emvTemplate = $service->getTemplateById($data['emv_template_id']);
                if ($emvTemplate) {
                    $fields = Mage::helper('emvemt/emvtemplate')->getAllDynContentAndAttributes($emvTemplate);
                    Mage::register(self::EMV_AVAILABLE_ATTRIBUTE_REGISTRY, $fields);

                    $html = array();
                    $emvDynBlock = $this->getLayout()->createBlock('emvemt/adminhtml_template_edit_tab_emvDyn');
                    $html[$emvDynBlock->getDivContentId()] = $emvDynBlock->toHtml();
                    $emvContentBlock = $this->getLayout()->createBlock('emvemt/adminhtml_template_edit_tab_emvContent');
                    $html[$emvContentBlock->getDivContentId()] = $emvContentBlock->toHtml();
                }

            } catch(Exception $e) {
                $error = $e->getMessage();
            }
        }

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array(
                    'html' => $html,
                    'error_messages'   => $error
                )
            )
        );
    }

    /**
     * Retrieve all available Magento variables to insert into email
     * The return will be in JSON format
     */
    public function getVariablesForMageTemplateAction()
    {
        $variables = array();

        // default variables
        $variables[] = Mage::getModel('core/source_email_variables')
            ->toOptionArray(true);

        // custom variables
        $customVariables = Mage::getModel('core/variable')
            ->getVariablesOptionArray(true);
        if ($customVariables) {
            $variables[] = $customVariables;
        }

        // template's variables
        $mageTemplateId = (int)$this->getRequest()->getParam('mage_template_id');
        /* @var $mageTemplate Mage_Core_Model_Email_Template */
        $mageTemplate = Mage::getModel('adminhtml/email_template');
        if ($mageTemplateId) {
            $mageTemplate->load($mageTemplateId);
            if ($mageTemplate->getId() && $templateVariables = $mageTemplate->getVariablesOptionArray(true)) {
                $variables[] = $templateVariables;
            }
        }

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array(
                    'variables' => $variables,
                )
            )
        );
    }

    /**
     * Get preview action
     */
    public function getPreviewAction()
    {
        $data = $this->getRequest()->getPost();

        $preview = '';
        $vars = array();

        if ($data && isset($data['emv_send_mail_mode_id'])) {
            try {
                switch ($data['emv_send_mail_mode_id']) {
                    case Emv_Emt_Model_Mailmode::EMV_CREATE :
                        if (
                            isset($data['emv_template_id'])
                            && $data['emv_template_id']
                            && isset($data['emv_account_id'])
                            && $data['emv_account_id']
                        ) {
                            $preparedAttributes = array(
                                Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_DYN     => array(),
                                Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_CONTENT => array()
                            );

                            $attributes = $this->getRequest()->getParam('attributes');
                            if ($attributes) {
                                foreach ($attributes as $attrInfo) {
                                    if (
                                        (isset($attrInfo['emv_attribute_type']) && $attrInfo['emv_attribute_type'])
                                        && (isset($attrInfo['emv_attribute']) && $attrInfo['emv_attribute'])
                                        && (isset($attrInfo['mage_attribute']) && $attrInfo['mage_attribute'])
                                    ) {
                                        if (isset($attrInfo['delete']) && $attrInfo['delete']) {
                                            continue;
                                        }

                                        $preparedAttributes[$attrInfo['emv_attribute_type']][$attrInfo['emv_attribute']]
                                            = $attrInfo['mage_attribute'];
                                    }
                                }
                            }

                            /* @var $account Emv_Core_Model_Account */
                            $account = Mage::getModel('emvcore/account')->load($data['emv_account_id']);
                            $service = Mage::getModel('emvcore/service_transactional');
                            $service->setAccount($account);
                            $preview = $service->getPreviewTemplate($data['emv_template_id'], $preparedAttributes);
                        }
                        break;

                    default:
                        if (isset($data['mage_template_id']) && $data['mage_template_id']) {
                            /** @var $template Mage_Core_Model_Email_Template */
                            $template = Mage::getModel('core/email_template');
                            $template->load($data['mage_template_id']);

                            $preview = $template->getProcessedTemplate($vars, true);

                            if ($template->isPlain()) {
                                $preview = "<pre>" . htmlspecialchars($preview) . "</pre>";
                            }
                        }
                        break;
                }
            } catch (Exception $e) {
                $preview = Mage::helper('emvemt')->__($e->getMessage());
            }
        }

        echo $preview;
    }

    /**
     * Action to validate SmartFocus account
     */
    public function validateTemplateAction()
    {
        $accountId = $this->getRequest()->getParam('account_id');
        $messageReturn = array('error' => array(), 'information' => array());

        if ($accountId) {
            try {
                Mage::helper('emvemt/emvtemplate')->validateEmvAccount($accountId);
                $messageReturn['information'][] = Mage::helper('emvemt')
                    ->__('Your account is correctly set up and ready to send emails');
            } catch (Exception $e) {
                $messageReturn['error'][] = $e->getMessage();
            }
        } else {
            $messageReturn['error'][] = Mage::helper('emvemt')
                ->__('Please select a SmartFocus account !');
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($messageReturn));
    }
}