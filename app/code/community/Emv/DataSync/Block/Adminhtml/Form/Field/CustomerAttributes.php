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
     * @var array
     */
    protected $_customerAttributes;

    /**
     * Get customer attributes array
     * @param string $attributeId
     * @return NULL|Ambigous <multitype:, string>
     */
    protected function _getCustomerAttributes($attributeId = null)
    {
        if (is_null($this->_customerAttributes)) {
            $this->_customerAttributes = array();
            $customerEntityTypeId = Mage::getModel('eav/entity')->setType('customer')->getTypeId();
            $customerAddressEntityTypeId = Mage::getModel('eav/entity')->setType('customer_address')->getTypeId();
            $collection = Mage::getResourceModel('eav/entity_attribute_collection')
                ->addFieldToFilter(
                    'entity_type_id',
                    array(
                        'in' => array($customerEntityTypeId, $customerAddressEntityTypeId)
                    )
                );

            if ($collection && $collection->count()>0) {
                foreach ($collection as $item) {
                    if ($item->getEntityTypeId() == $customerAddressEntityTypeId) {
                        $this->_customerAttributes[$item->getAttributeId()] = 'customer_address_'
                            . $item->getAttributeCode();
                    }
                    else {
                        $this->_customerAttributes[$item->getAttributeId()] = $item->getAttributeCode();
                    }
                }
                asort($this->_customerAttributes);
            }
        }

        if (!is_null($attributeId)) {
            return isset($this->_customerAttributes[$attributeId]) ? $this->_customerAttributes[$attributeId] : null;
        }

        return $this->_customerAttributes;
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
            foreach ($this->_getCustomerAttributes() as $attributeId => $attributeCode) {
                $this->addOption($attributeId, addslashes($attributeCode));
            }
        }

        return parent::_toHtml();
    }
}
