<?php
/**
 * Emt Mapped Attribute Collection  Class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Attribute_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('emvemt/attribute','emvemt/attribute');
    }

    /**
     * @param string $emtId
     * @return Emv_Emt_Model_Mysql4_Attribute_Collection
     */
    public function addEmtFilter($emtId)
    {
        $this->getSelect()->where('main_table.emv_emt_id = ?', $emtId);
        return $this;
    }

    /**
     * @param string $attributeType
     * @return Emv_Emt_Model_Mysql4_Attribute_Collection
     */
    public function addAttributeTypeFilter($attributeType)
    {
        $this->getSelect()->where('main_table.emv_attribute_type = ?', $attributeType);
        return $this;
    }

    /**
     * Return mapped attributes in an array of array (key is SmartFocus attribute name, value is
     * magento attribute name).
     * ex :
     * array ( 'emvAttribute1' => 'customer.firstname',
     *  'emvAttribute2' => 'customer.lastname')
     * @return array
     */
    public function getMapping()
    {
        $mappedAttributes = array();
        foreach($this as $mapping) {
            $mappedAttributes[$mapping->getEmvAttribute()] = $mapping->getMageAttribute();
        }

        return $mappedAttributes;
    }

    /**
     * Get all mapped attributes with their type
     * @return array
     */
    public function getMappingWithType()
    {
        $mappedAttributes = array();
        foreach($this as $mapping) {
            if (!isset($mappedAttributes[$mapping->getEmvAttributeType()])) {
                $mappedAttributes[$mapping->getEmvAttributeType()] = array();
            }

            $mappedAttributes[$mapping->getEmvAttributeType()][$mapping->getEmvAttribute()] = $mapping->getMageAttribute();
        }

        return $mappedAttributes;
    }
}
