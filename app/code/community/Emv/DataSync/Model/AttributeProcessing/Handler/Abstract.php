<?php
/**
 * Attribute handler abstract
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_AttributeProcessing_Handler_Abstract
{
    const SEPARATOR_ATTR           = '|';

    /**
     * List of customer attribute options (html select element)
     * @var array
     */
    protected $_optionCustomerAttributes;

    /**
     * Get attribute config parts
     *
     * @param string $mappedAttributeName
     * @return array
     *     prefix -> prefix part
     *     name -> name part
     */
    public static function getAttributeConfigParts($mappedAttributeName)
    {
        $prefixPart = '';
        $name = $mappedAttributeName;

        $parts = explode(self::SEPARATOR_ATTR, $mappedAttributeName);
        if (is_array($parts) && count($parts) == 2) {
            $prefixPart = $parts[0];
            $name       = $parts[1];
        }
        return array('prefix' => $prefixPart, 'name' => $name);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @return string
     */
    public static function buildAttributeName($prefix, $name)
    {
        // the field follows one of these patterns :
        // billing|1, shipping|2, customer|1, newsletter|subscriber_email
        return $prefix . self::SEPARATOR_ATTR . $name;
    }

    /**
     * Generic method needs to be implemented in Child class. The method indicates whether the handler
     * can treat a given attribute
     *
     * @param array $attributeData
     * @param int $storeId
     * @return boolean
     */
    public function canHandle($attributeData, $storeId)
    {
        return false;
    }

    /**
     * Apply post treatments for a given attribute list
     *
     * @param array $attributeList
     * @return Emv_DataSync_Model_AttributeProcessing_Handler_Abstract
     */
    public function postPrepareAttribute(array $attributeList)
    {
        return $this;
    }

    /**
     * Return a list of customer attribute options (html select element)
     * Generic method needs to be implemented in Child class.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->_optionCustomerAttributes = array();
        return $this->_optionCustomerAttribute;
    }

    /**
     * Prepare subscriber collection according to mapped attribute list
     * Generic method needs to be implemented in Child class.
     *
     * @param Varien_Data_Collection_Db $collection
     * @param array $attributeList
     * @param string $storeId
     * @return Varien_Data_Collection_Db
     */
    public function prepareSubscriberCollection(
        Varien_Data_Collection_Db $collection,
        array $attributeList,
        $storeId = null
    )
    {
        return $collection;
    }
}