<?php
/**
 * Attribute mapping class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Attribute extends Mage_Catalog_Model_Abstract
{
    /**
     * EmailVision Dynamic Attribute type
     */
    const ATTRIBUTE_TYPE_EMV_DYN     = 'EMV_DYN';

    /**
     * EmailVision Dynamic content type
     */
    const ATTRIBUTE_TYPE_EMV_CONTENT = 'EMV_CONTENT';

    /**
     * Constructor
     *
     * @see Varien_Object::_construct()
     */
    protected function _construct()
    {
        $this->_init('emvemt/attribute');
    }

    public function validate()
    {
        $error = array();

        if (!Zend_Validate::is($this->getEmvAttribute(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvemt')->__('SmartFocus attribute is required !');
        }

        if (!Zend_Validate::is($this->getEmvAttributeType(), 'NotEmpty')) {
            $errors[] = Mage::helper('emvemt')->__('Please provide the attribute type !');
        } else if(!in_array($this->getEmvAttributeType(), array(self::ATTRIBUTE_TYPE_EMV_CONTENT, self::ATTRIBUTE_TYPE_EMV_DYN))) {
            $errors[] = Mage::helper('emvemt')->__('Unknown attribute type !');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Check if this used mageno attribute already exists in other mapping
     *
     * @return boolean
     */
    public function mageAttributeExists()
    {
        $result = $this->_getResource()->mageAttributeExists($this);
        return (is_array($result) && count($result) > 0 ) ? true : false;
    }

    /**
     * Check if this used SmartFocus attribute already exists in other mapping
     *
     * @return boolean
     */
    public function emvAttributeExists()
    {
        $result = $this->_getResource()->emvAttributeExists($this);
        return (is_array($result) && count($result) > 0 ) ? true : false;
    }
}
