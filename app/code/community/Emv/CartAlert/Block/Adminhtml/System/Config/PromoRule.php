<?php
/**
 * Promotion Rule Chooser block
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */

class Emv_CartAlert_Block_Adminhtml_System_Config_PromoRule
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself
     *
     * @return Emv_CartAlert_Block_Adminhtml_System_Config_PromoRule
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('smartfocus/cartalert/system/config/promo_rule.phtml');
        }
        return $this;
    }

    /**
     * Get button config in Json
     *
     * @return string
     */
    public function getButtonConfigInJson()
    {
        $config = array(
            'buttons' => array(
                'open'  => Mage::helper('abandonment')->__('Select Promotion Rule'),
                'close' => Mage::helper('abandonment')->__('Close'),
            )
        );
        return Mage::helper('core')->jsonEncode($config);
    }

    /**
     * Get promotion rule id
     *
     * @return int
     */
    public function getRuleId()
    {
        $id = '';
        if ($this->getElement()) {
            $id = (int)$this->getElement()->getValue();
        }
        return $id;
    }

    /**
     * Get Label for element
     *
     * @return string
     */
    public function getLabel()
    {
        $label = false;
        if ($this->getElement()) {
            $rule = Mage::getModel('salesrule/rule')->load((int)$this->getElement()->getValue());
            if ($rule->getId()) {
                $label = $rule->getName();
            }
        }

        if (!$label) {
            $label = Mage::helper('widget')->__('Not Selected');
        }

        return $label;
    }

    /**
     * @return string
     */
    public function getElementName()
    {
        return ($this->getElement()) ? $this->getElement()->getName() : '';
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }
}