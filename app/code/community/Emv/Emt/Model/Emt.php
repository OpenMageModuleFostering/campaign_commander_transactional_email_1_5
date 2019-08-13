<?php
/**
 * EmailVision email template class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Emt extends Mage_Core_Model_Abstract
{
    const MAGENTO_TEMPLATE_ID_FOR_EMV_SEND = 'magento_emv_sending';

    /**
     * SmartFocus account
     * @var Emv_Core_Model_Account
     */
    protected $_account;

    /**
     * SmartFocus Template
     *
     * @var stdClass
     */
    protected $_emailVisionTemplate;

    /**
     * Invalid fields
     * @var array
     */
    protected $_invalidFields = array();

    /**
     * SmartFocus paramters
     *
     * @var array
     */
    protected $_unserializedEmvParams = null;

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_init('emvemt/emt');
    }

    /**
     * Add new treatment before saving
     *  - Modify updated_at, created_at fields
     *  -
     * @see Mage_Core_Model_Abstract::_beforeSave()
     */
    protected function _beforeSave()
    {
        $this->_emailVisionParams = array();

        //reset emt template if user switch mail mode from 'SmartFocus Template' to 'SmartFocus Routage' or 'Classic'
        if (
            $this->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::CLASSIC_MODE
            || $this->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_SEND
        ) {
            $this->setEmvTemplateId(null);
        } else {
            if (
                $this->getEmvTemplate()
                && isset($this->getEmvTemplate()->id)
            ) {
                $this->_unserializedEmvParams = $this->getEmailVisionParams($this->_emailVisionTemplate);
            } else {
                // throw exception, SmartFocus template shoud be loaded
                Mage::throwException(
                    Mage::helper('emvemt')->__('Please select a SmartFocus template !')
                );
            }
        }

        // if _unserializedEmvParams property is defined, and in mode create
        if (
            $this->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_CREATE
            && is_array($this->_unserializedEmvParams)
        ) {
            $this->setData('emv_parameters', serialize($this->_unserializedEmvParams));
        } else {
            $this->setData('emv_parameters', null);
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
     * Get SmartFocus parameters :
     * - from a given SmartFocus template.
     * - from SmartFocus template if defined as object's property
     * - unserialize $this->getData('emv_parameters')
     *
     * @param stdClass $emv
     * @return array
     */
    public function getEmailVisionParams(stdClass $emv = null)
    {
        $result = array();

        if ($emv == null) {
            if (is_array($this->_unserializedEmvParams)) {
                return $this->_unserializedEmvParams;

            } elseif ($this->getData('emv_parameters')) {
                $params = $this->getData('emv_parameters');
                $params = unserialize($params);
                if ($params && is_array($params)) {
                    $this->_unserializedEmvParams = $params;
                }

                return $this->_unserializedEmvParams;
            }
        }

        if ($emv && isset($emv->id) && isset($emv->random) && isset($emv->encrypt) && isset($emv->name)) {
            $result['emv_id']      = $emv->id;
            $result['emv_random']  = $emv->random;
            $result['emv_encrypt'] = $emv->encrypt;
            $result['emv_name']    = $emv->name;

            $vars = Mage::helper('emvemt/emvtemplate')->getAllDynContentAndAttributes($emv);
            $result[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_CONTENT]
                = $vars[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_CONTENT];
            $result[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_DYN]
                = $vars[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_DYN];
        }

        return $result;
    }

    /**
     * Validate first page of emt creation
     *
     * @return true | array - errors
     */
    public function validateNewForm()
    {
        $error = array();

        if (!Zend_Validate::is($this->getEmvAccountId(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvemt')->__('SmartFocus account is required !');
            $this->_invalidFields['emv_account_id'] = Mage::helper('emvemt')
                ->__('Please select a SmartFocus account !');
        } else {
            if ($this->getAccount() == null) {
                $errors[] = Mage::helper('emvemt')->__('SmartFocus account does not exist !');
                $this->_invalidFields['emv_account_id'] = Mage::helper('emvemt')
                    ->__('Your selected SmartFocus account does not exist anymore ! Please select a new one !');
            }
        }

        if (!Zend_Validate::is($this->getMageTemplateId(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvemt')->__('Magento template is required !');
            $this->_invalidFields['mage_template_id'] = Mage::helper('emvemt')
                ->__('Please select a Magento template !');
        } else if ($this->mageTemplateMapped()) {
            $errors[] = Mage::helper('emvemt')->__('This Magento template is already mapped !');
            $this->_invalidFields['mage_template_id'] = Mage::helper('emvemt')
                ->__('Please select another Magento template !');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Get invalid fields
     *
     * @return array
     */
    public function getInvalidFields()
    {
        return $this->_invalidFields;
    }

    /**
     * Get SmartFocus account - make sure emv_account_id is set
     *
     * @return Emv_Core_Model_Account
     */
    public function getAccount()
    {
        if (
            $this->getEmvAccountId()
            && (!isset($this->_account) || $this->dataHasChangedFor('emv_account_id'))
        ) {
            $this->_account = Mage::getModel('emvcore/account')->load($this->getEmvAccountId());
            if (!$this->_account->getId()) {
                $this->_account = null;
            }
        }

        return $this->_account;
    }

    /**
     * Set SmartFocus Account
     * @param Emv_Core_Model_Account $account
     * @return Emv_Emt_Model_Emt
     */
    public function setAccount(Emv_Core_Model_Account $account)
    {
        if ($account->getId()) {
            $this->setEmvAccountId($account->getId());
            $this->setOrigData('emv_account_id', $account->getId());
            $this->_account = $account;
        }

        return $this;
    }

    /**
     * Get SmartFocus template
     *
     * @throws Exception - if network errors occur
     * @return stdClass | null
     */
    public function getEmvTemplate()
    {
        if (
            $this->getEmvTemplateId()
            && (!isset($this->_emailVisionTemplate) || $this->dataHasChangedFor('emv_template_id'))
        ) {
            $service = Mage::getModel('emvcore/service_transactional');
            $service->setAccount($this->getAccount());
            $this->_emailVisionTemplate = $service->getTemplateById($this->getEmvTemplateId());
        }

        return $this->_emailVisionTemplate;
    }

    /**
     * Set SmartFocus Template
     *
     * @param stdClass $emvTemplate
     */
    public function setEmvTemplate(stdClass $emvTemplate)
    {
        if ($emvTemplate && isset($emvTemplate->id)) {
            $this->setEmvTemplateId($emvTemplate->id);
            $this->setOrigData('emv_template_id', $emvTemplate->id);
            $this->_emailVisionTemplate = $emvTemplate;
        }

        return $this;
    }

    /**
     * Validate SmartFocus mapped template. Return true if everything is ok or an list of errors
     *
     * @return true | array - errors
     */
    public function validate()
    {
        $errors = $this->validateNewForm();

        if (is_array($errors) && count($errors) > 0) {
            return $errors;
        } else {
            $errors = array();
        }

        // validate if send mode is valide
        if (!Zend_Validate::is($this->getEmvSendMailModeId(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvemt')->__('Sending mode is required !');
            $this->_invalidFields['emv_send_mail_mode_id'] = Mage::helper('emvemt')
                ->__('Please select a sending mode !');
        } else {
            if (
                !in_array($this->getEmvSendMailModeId(), Emv_Emt_Model_Mailmode::getSupportedModes())
            ) {
                $errors[] = Mage::helper('emvemt')->__('This sending mode doesn\'t exists !');
                $this->_invalidFields['emv_send_mail_mode_id'] = Mage::helper('emvemt')
                    ->__('Please select another sending mode !');
            }
        }

        // in case we use email vision template to send email
        if ($this->getEmvSendMailModeId() == Emv_Emt_Model_Mailmode::EMV_CREATE) {
            if (!Zend_Validate::is($this->getEmvTemplateId(), 'NotEmpty')) {
                $errors[] = Mage::helper('emvemt')->__('SmartFocus template is required !');
                $this->_invalidFields['emv_template_id'] = Mage::helper('emvemt')
                    ->__('Please select an SmartFocus template !');
            } else {
                $emv = null;
                try {
                    // Verify if SmartFocus template exists or not
                    $emv = $this->getEmvTemplate();
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }

                if (null === $emv) {
                    $this->_invalidFields['emv_template_id'] = Mage::helper('emvemt')
                        ->__('Please select another SmartFocus template !');
                }
            }
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Validate mapped attributes
     *
     * @param array $attributes
     * @return array
     */
    public function validateMappedAttributes($attributes = array())
    {
        $errors = array();

        $emvTypes = array();
        foreach ($attributes as $attrInfo) {
            if ($attrInfo['emv_attribute']) {
                if (!isset($emvType['emv_attribute'])) {
                    $emvTypes[$attrInfo['emv_attribute']] = 1;
                } else {
                    $emvTypes[$attrInfo['emv_attribute']] ++;
                }
            }
        }

        foreach ($emvTypes as $type => $occurrence) {
            if ($occurrence > 1) {
                $errors[] = Mage::helper('emvemt')->__(
                    "SmartFocus attribute '%s' is already mapped !",
                    Mage::helper('core')->escapeHtml($type)
                );
            }
        }

        return $errors;
    }

    /**
     * Check if a Magento template has been mapped to current account
     *
     * @return boolean
     */
    public function mageTemplateMapped()
    {
        $result = $this->_getResource()->mageTemplateMapped($this);
        return (is_array($result) && count($result) > 0 ) ? true : false;
    }

    /**
     * Check if an SmartFocus email template already exists with the current data
     *
     * @return boolean
     */
    public static function emvTemplateExists()
    {
        $result = $this->_getResource()->emvTemplateExists($this);
        return (is_array($result) && count($result) > 0 ) ? true : false;
    }
}