<?php
/**
 * Used to manage EmailVision fields' block in back-office configuration
 *
 * @category   Emv
 * @package    Emv_DataSync
 * @copyright  Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Block_Adminhtml_Form_Field_EmailVisionFields extends Mage_Core_Block_Html_Select
{
    /**
     * @var array
     */
    protected $_emailVisionFields;

    /**
     * Prepare options attribute to render from EmailVision fields stored in conf
     * If conf is empty, will call the member webservice to get members description (fields array)
     *
     * @return Array $this->_emailVisionFields    An array containing lowered attributes names as keys, and their not lowered counterparts as values
     */
    protected function _getEmailVisionFields($fieldId = null)
    {
        if (is_null($this->_emailVisionFields)) {
            $this->_emailVisionFields = array();
            $savedFields = Mage::helper('emvdatasync')->getEmailVisionFieldsFromConfig();
            if (!empty($savedFields)) {
                foreach ($savedFields as $field) {
                    $this->_emailVisionFields[strtolower($field['name'])] = $field['name'];
                }
            } else {
                $this->_emailVisionFields['empty'] = Mage::helper('emvdatasync')
                        ->__('Please synchronize with SmartFocus webservice (see above)');
            }
        }

        if (!is_null($fieldId)) {
            return isset($this->_emailVisionFields[$fieldId]) ? $this->_emailVisionFields[$fieldId] : null;
        }
        return $this->_emailVisionFields;
    }

    /**
     * Setter for fields input name
     *
     * @param unknown $value
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Block_Html_Select::_toHtml()
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getEmailVisionFields() as $attributeId => $attributeCode) {
                $this->addOption($attributeId, addslashes($attributeCode));
            }
        }
        $this->setClass('emailvision_select');

        return parent::_toHtml();
    }
}
