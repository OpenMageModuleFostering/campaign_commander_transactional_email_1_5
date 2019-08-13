<?php
/**
 * Emt Mapped Attribute Resource  Class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Attribute extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('emvemt/attribute', 'id');
    }

    /**
     * Check if the given Mage Attribute is already mapped
     *
     * @param Mage_Core_Model_Abstract $attribute
     * @return array | null
     */
    public function mageAttributeExists(Mage_Core_Model_Abstract $attribute)
    {
        return $this->_fieldAlreadyExist('mage_attribute', $attribute->getMageAttribute(),
            $attribute->getEmvEmtId(), $attribute->getId());
    }

    /**
     * Check if the given EmailVision Attribute is mapped
     * @param Mage_Core_Model_Abstract $attribute
     * @return array | null
     */
    public function emvAttributeExists(Mage_Core_Model_Abstract $attribute)
    {
        return $this->_fieldAlreadyExist('emv_attribute', $attribute->getEmvAttribute(),
            $attribute->getEmvEmtId(), $attribute->getId());
    }

    /**
     * Check if a value associated to an emt already exists in a column
     * @param $columnName string database column where to check unicity
     * @param $fieldValue string value to check unicity
     * @param $emtId string
     * @param $mappingId integer
     */
    protected function _fieldAlreadyExist($columnName, $fieldValue, $emtId, $mappingId)
    {
        $attributeTable = $this->getTable('emvemt/attribute');
        $select = $this->_getReadAdapter()->select();
        $select->from($attributeTable);
        $select->where("{$attributeTable}.{$columnName} = '{$fieldValue}' ".
            "AND {$attributeTable}.emv_emt_id = '{$emtId}' ".
            "AND {$attributeTable}.id != '{$mappingId}'");
        return $this->_getReadAdapter()->fetchRow($select);
    }
}
