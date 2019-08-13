<?php
/**
 * Date block for back-office configuration
 *
 * @category   Emv
 * @package    Emv_DataSync
 * @copyright  Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Block_Adminhtml_System_Config_Date extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_System_Config_Form_Field::_getElementHtml()
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $value = $element->getValue();

        $date = Mage::helper('emvdatasync')->__('Not scheduled yet');
        if ((string)$value) {
            $date = Mage::app()->getLocale()->date(
                  $value,
                  Varien_Date::DATETIME_INTERNAL_FORMAT,
                  null,
                  true
              );
        }

        return '<strong>' . $date . '</strong>';
    }

}
