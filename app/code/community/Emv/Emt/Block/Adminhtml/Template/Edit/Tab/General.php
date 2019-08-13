<?php
/**
 * SmartFocus email template edit form
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * @var Emv_Core_Model_Mysql4_Account_Collection
     */
    protected $_accountCollection;

    /**
     * Prepare block children and data
     */
    protected function _prepareLayout()
    {
        $this->setChild('continue_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('emvemt')->__('Continue'),
                    'onclick'   => 'editForm.submit();',
                    'class'     => 'save'
                ))
        );

        $this->setChild('get_emv_templates',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('emvemt')->__('Get SmartFocus templates'),
                    'class'     => 'save',
                    'id'        => 'get_emv_templates'
                ))
        );

        parent::_prepareLayout();
    }

    /**
     * Prepare form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        // get SmartFocus template
        $emtModel = Mage::registry(Emv_Emt_Adminhtml_TemplateController::CURRENT_EMVEMT_TEMPLATE_REGISTRY);
        if (null === $emtModel)
        {
            $emtModel = Mage::getModel('emvemt/emt');
        } elseif ($emtModel->getId()) {
            $form->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        // set form to block
        $this->setForm($form);

        $fieldset = $form->addFieldset('emt_info',
            array(
                'legend' => Mage::helper('emvemt')->__('Template Mapping Configuration'),
                'class' => 'fieldset-wide',
            )
        );

        // build SmartFocus account select
        $this->prepareEmailVisionAccountSelect($fieldset, $emtModel);
        // build magento template select
        $this->prepareMagentoTemplateSelect($fieldset, $emtModel);

        // if emt is new, only display continue button
        if (!$emtModel->getId()) {
            $this->addJsTemplate($fieldset);

            $fieldset->addField('continue_button', 'note', array(
                'text' => $this->getChildHtml('continue_button'),
            ));
            return ;
        }

        // add mail mode select
        // javascript removing unused input according to selected send mail mode
        $fieldset->addField('emv_send_mail_mode_id', 'select', array(
            'value' => $emtModel->getEmvSendMailModeId(),
            'label' => Mage::helper('emvemt')->__('Sending Mode'),
            'name' => 'emv_send_mail_mode_id',
            'required' => true,
            'class' => 'validate-select',
            'values' => Emv_Emt_Model_Mailmode::toOptionArray(),
        ));

        // Emailvision template select
        $this->prepareEmailVisionTemplateSelect($fieldset, $emtModel);

        // Handle the way of displaying get Emailvision template button.
        $getEmvTemplateButtonParam = array(
            'text' => '<span id="emtTemplateButtonContainer" >'
                . $this->getChildHtml('get_emv_templates').'</span>',
        );
        $fieldset->addField('emv_template_button', 'note', $getEmvTemplateButtonParam);

        // date from
        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );
        $dateFromParam = array(
            'value' => '',
            'label' => Mage::helper('emvemt')->__('From'),
            'name' => 'from_date',
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso,
        );
        $fieldset->addField('from_date', 'date', $dateFromParam);

        // date to
        $dateToParam = array(
            'value' => '',
            'label' => Mage::helper('emvemt')->__('To'),
            'name' => 'to_date',
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => $dateFormatIso,
        );
        $fieldset->addField('to_date', 'date', $dateToParam);
        $this->addJsTemplate($fieldset);

        // set template value into form
        $form->setValues($emtModel->getData());
    }

    /**
     * Add js template into form
     *
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     */
    public function addJsTemplate(Varien_Data_Form_Element_Fieldset $fieldset)
    {
        // js block contains all common javascript functions
        $jsBlock = $this->getLayout()->createBlock('adminhtml/template');
        $jsBlock->setData('edit_block', $this)->setTemplate('smartfocus/emt/template/common_js.phtml');
        $fieldset->addField(
            'js_block',
            'note',
            array(
                'note'      => $jsBlock->toHtml(),
            )
        );
    }
    /**
     * Prepare Magento Template Select Element
     *
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @param Emv_Emt_Model_Emt $emtModel
     */
    public function prepareMagentoTemplateSelect(Varien_Data_Form_Element_Fieldset $fieldset, Emv_Emt_Model_Emt $emtModel)
    {
        $error = $this->displayErrorMessage('mage_template_id');

        $options = array();

        if ($error || !$emtModel->getMageTemplateId() || !Mage::registry(Emv_Emt_Adminhtml_TemplateController::MAGE_TEMPLATE_NAME_REGISTRY)) {
            $emvAccount = $this->getAccountCollection();
            // try to get account model from emt object
            $account = $emtModel->getAccount();
            // else, get the first account from the list
            if (!$account && $emvAccount->getSize()) {
                $account = $emvAccount->getFirstItem();
            }

            /* @var $magentoTemplates Emv_Emt_Model_MageTemplate */
            $magentoTemplates = Mage::getModel('emvemt/mageTemplate')
                ->getNotMappedMageTemplateCollection($account->getId());
            if ($magentoTemplates) {
                $options = $magentoTemplates->toOptionArray();
            }
        } else {
            $options[] = array(
                'value' => $emtModel->getMageTemplateId(),
                'label' => Mage::registry(Emv_Emt_Adminhtml_TemplateController::MAGE_TEMPLATE_NAME_REGISTRY)
            );
        }

        if (count($options) == 0) {
            $error = $this->displayErrorMessage(
                'mage_template_id',
                Mage::helper('emvemt')
                    ->__('There is no Magento email templates which can be used for this account !')
            );
        }

        // display all available Magento email templates
        $fieldset->addField('mage_template_id', 'select', array(
            'value' => $emtModel->getMageTemplateId(),
            'label' => Mage::helper('emvemt')->__('Magento Template Name'),
            'name' => 'mage_template_id',
            'disabled' => ($error || !$emtModel->getEmvSendMailModeId()) ? false : true,
            'required' => true,
            'class' => 'validate-select',
            'values' => $options,
            'after_element_html' => $error
        ));
    }

    /**
     * Get Campaign Commmander Account collection
     *
     * @return Emv_Core_Model_Mysql4_Account_Collection
     */
    public function getAccountCollection()
    {
        if (!$this->_accountCollection) {
            $accounts = Mage::getResourceModel('emvcore/account_collection');
            $accounts->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('id', 'name')); // we only take two fields

            $this->_accountCollection = $accounts;
        }

        return $this->_accountCollection;
    }

    /**
     * Prepare EmailVision Account select element - for Campaign Commader Accounts
     *
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @param Emv_Emt_Model_Emt $emtModel
     */
    public function prepareEmailVisionAccountSelect(Varien_Data_Form_Element_Fieldset $fieldset, Emv_Emt_Model_Emt $emtModel)
    {
        $accountOptions = array();
        if ($account = $emtModel->getAccount()) {
            $accountOptions[] = array(
                'value' => $account->getId(),
                'label' => $account->getName()
            );
        } else {
            $accounts = $this->getAccountCollection();
            $accountOptions = $accounts->toOptionArray();
        }

        $error = $this->displayErrorMessage('emv_account_id');
        $disable = true;
        $onchange = '';
        if (!$emtModel->getEmvAccountId() || $error) {
            $disable = false;
        }

        // display all available SmartFocus accounts
        $fieldset->addField('emv_account_id', 'select', array(
            'value'    => $emtModel->getEmvAccountId(),
            'label'    => Mage::helper('emvemt')->__('SmartFocus Account'),
            'name'     => 'emv_account_id',
            'required' => true,
            'disabled' => $disable,
            'class'    => 'validate-select',
            'values'   => $accountOptions,
            'after_element_html' => $error,
        ));
    }

    /**
     * Prepare SmartFocus Templates
     *
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @param Emv_Emt_Model_Emt $emtModel
     */
    public function prepareEmailVisionTemplateSelect(Varien_Data_Form_Element_Fieldset $fieldset, Emv_Emt_Model_Emt $emtModel)
    {
        $values = array();
        if (Mage::registry(Emv_Emt_Adminhtml_TemplateController::EMV_TEMPLATE_ARRAY_REGISTRY)) {
            $values = array(Mage::registry(Emv_Emt_Adminhtml_TemplateController::EMV_TEMPLATE_ARRAY_REGISTRY));
        }

        $fieldset->addField(
            'emv_template_id',
            'select',
            array(
                'value' => $emtModel->getEmvTemplateId(),
                'label' => Mage::helper('emvemt')->__('SmartFocus Template Name'),
                'name' => 'emv_template_id',
                'required' => true,
                'class' => 'validate-select',
                'values' => $values,
            )
        );
    }

    /**
     * Display error messages for form elements
     *
     * @param string $id - field id
     * @return string
     */
    public function displayErrorMessage($id, $message = null)
    {
        $invalidFields = Mage::registry(Emv_Emt_Adminhtml_TemplateController::INVALID_FIELD_EMV_TEMPLATE_REGISTRY);

        $afterElementHtml = '';
        $preparedMessage = $message;
        if ($invalidFields && isset($invalidFields[$id])) {
            $preparedMessage = $invalidFields[$id];
        }

        if($preparedMessage) {
            $afterElementHtml = '<div id="advice-' . $id .'" class="validation-advice">';
            $afterElementHtml .= $preparedMessage;
            $afterElementHtml .= '</div>';
        }

        return $afterElementHtml;
    }
}