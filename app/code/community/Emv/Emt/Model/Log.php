<?php
/**
 * Message sending log Model
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Log extends Mage_Core_Model_Abstract
{

    /**
     * Constant for sending basic workflow
     */
    const SENDING_BASIC_WORKFLOW = 'normal';

    /**
     * Constant for resending workflow
     */
    const RESENDING_WORKFLOW = 'resending';
    const RESCHEDULED = 1;

    /**
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
        $this->_init('emvemt/log', 'id');
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Abstract::_beforeSave()
     */
    protected function _beforeSave()
    {
        $this->prepareDataBeforeSave();
        return parent::_beforeSave();
    }

    /**
     * @return array
     */
    public static function getAllWorkFlowOptions()
    {
        return array(
            self::SENDING_BASIC_WORKFLOW => Mage::helper('emvemt')->__('Normal'),
            self::RESENDING_WORKFLOW => Mage::helper('emvemt')->__('Resending'),
        );
    }
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Abstract::_afterLoad()
     */
    protected function _afterLoad()
    {
        $this->prepareDataAfterLoad();
        return parent::_afterLoad();
    }

    /**
     * Prepare data before save
     *  - all data of type "array" will be serailized
     *  - if created_at is not set, use gmt date
     *  - email will be store in the the following form : email1,email2 ....
     *
     * @return Emv_Emt_Model_Log
     */
    public function prepareDataBeforeSave()
    {
        $coreHelper = Mage::helper('core');
        $emvParams = $this->getData('emv_params');
        if (is_array($emvParams)) {
            $this->setData('emv_params', $coreHelper->jsonEncode($emvParams));
        } else {
            $this->setData('emv_params', null);
        }

        $emvContent = $this->getData('emv_content_variables');
        if (is_array($emvContent)) {
            $this->setData('emv_content_variables', $coreHelper->jsonEncode($emvContent));
        } else {
            $this->setData('emv_content_variables', null);
        }

        $emvVariables = $this->getData('emv_dyn_variables');
        if (is_array($emvVariables)) {
            $this->setData('emv_dyn_variables', $coreHelper->jsonEncode($emvVariables));
        } else {
            $this->setData('emv_dyn_variables', null);
        }

        $email = $this->getData('email');
        if (is_array($email)) {
            $this->setData('email', implode(',', $email));
        }

        if ($this->getData('created_at') == null) {
            // created_at is also in gmt date
            $gmtDate = Mage::getModel('core/date')->gmtDate();
            $this->setData('created_at', $gmtDate);
        }

        return $this;
    }

    /**
     * Prepare data before load
     * @return Emv_Emt_Model_Log
     */
    public function prepareDataAfterLoad()
    {
        $coreHelper = Mage::helper('core');

        $emvParams = $this->getData('emv_params');
        if ($emvParams) {
            $this->setData('emv_params', $coreHelper->jsonDecode($emvParams));
        }

        $emvContent = $this->getData('emv_content_variables');
        if ($emvContent) {
            $this->setData('emv_content_variables', $coreHelper->jsonDecode($emvContent));
        }

        $emvVariables = $this->getData('emv_dyn_variables');
        if ($emvVariables) {
            $this->setData('emv_dyn_variables', $coreHelper->jsonDecode($emvVariables));
        }

        $email = $this->getData('email');
        $this->setData('email', explode(',', $email));
        return $this;
    }

    /**
     * @return array
     */
    public static function getErrorCodeOptions()
    {
        return array(
            0 => Mage::helper('emvemt')->__('Unknow Error'),
            EmailVision_Api_Exception::APPLICATION_ERROR => Mage::helper('emvemt')->__('Server Error'),
            EmailVision_Api_Exception::CONNECT_ERROR => Mage::helper('emvemt')->__('Network Problem'),
            EmailVision_Api_Exception::INVALID_EMAIL_SENDING_PARAMETERS
                => Mage::helper('emvemt')->__('Invalid SmartFocus Parameters')
        );
    }

    /**
     * Clean logs created before a time limit
     * @param int $timeLimit
     * @return Emv_Emt_Model_Log
     */
    public function cleanLogs($timeLimit)
    {
        $this->getResource()->cleanLogs($timeLimit);
        return $this;
    }
}