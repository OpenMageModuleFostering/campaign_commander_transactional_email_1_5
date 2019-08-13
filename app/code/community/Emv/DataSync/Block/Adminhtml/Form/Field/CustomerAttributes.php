<?php
/**
 * Block to manage the customer attributes form in back office configuration
 *
 * @category   Emv
 * @package    Emv_DataSync
 * @copyright  Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Block_Adminhtml_Form_Field_CustomerAttributes extends Mage_Core_Block_Html_Select
{
    /**
     * Get available attributes array
     *
     * @return array
     */
    protected function _getCustomerAttributes()
    {
        $config = Mage::getModel('emvdatasync/attributeProcessing_config');
        return $config->toOptionArray();
    }

    /**
     * Setter for input fields names
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
            foreach ($this->_getCustomerAttributes() as $type => $optionValue) {
                $this->addOption($optionValue['value'], addslashes($optionValue['label']));
            }
        }

        return parent::_toHtml();
    }
}
