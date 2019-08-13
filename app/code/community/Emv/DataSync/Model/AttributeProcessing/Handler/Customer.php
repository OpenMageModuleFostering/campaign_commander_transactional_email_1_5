<?php
/**
 * Customer Attribute handler (customer and customer address attributes)
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_AttributeProcessing_Handler_Customer
    extends Emv_DataSync_Model_AttributeProcessing_Handler_Abstract
{
    /**
     * List of prefixes for different support entities
     */
    const PREFIX_CUSTOMER_ATTR     = 'customer';
    const PREFIX_SHIPPING_ATTR     = 'shipping';
    const PREFIX_BILLING_ATTR      = 'billing';

    const PREFIX_LABEL_CUSTOMER    = 'Customer';
    const PREFIX_LABEL_BILLING     = 'Billing';
    const PREFIX_LABEL_SHIPPING    = 'Shipping';
    const SEPARATOR_LABEL          = ' - ';

    /**
     * Prohibited attributes
     * @var Array
     */
    protected $_prohibitedAttributes = array('store_id');
    protected $_joinFactory = array();

    /**
     * Default billing and shipping attribute id
     * @var string
     */
    protected $_defaultBillingAttrId;
    protected $_defaultShippingAttrId;

    /**
     * Prepare default billing and shipping attribute ids
     */
    public function prepareDefaultBillingAndShippingAttrId()
    {
        if ($this->_defaultBillingAttrId == null || $this->_defaultShippingAttrId == null) {
            $attributes = Mage::helper('emvdatasync/service')->getMagentoAttributes(
                    array(),
                    array('default_billing','default_shipping')
                );
            foreach ($attributes as $attr) {
                if ($attr->getAttributeCode() == 'default_billing') {
                    $this->_defaultBillingAttrId = $attr->getId();
                }
                if ($attr->getAttributeCode() == 'default_shipping') {
                    $this->_defaultShippingAttrId = $attr->getId();
                }

            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::toOptionArray()
     */
    public function toOptionArray()
    {
        if (is_null($this->_optionCustomerAttributes)) {
            $this->_optionCustomerAttributes = array();

            $addressLabel          = Mage::helper('emvdatasync')->__('Customer Billing Address');
            $shippingAddressLabel  = Mage::helper('emvdatasync')->__('Customer Shipping Address');
            $customerLabel         = Mage::helper('emvdatasync')->__('Customer');

            $customerEntityTypeId        = Mage::helper('emvdatasync/service')->getCustomerTypeId();
            $customerAddressEntityTypeId = Mage::helper('emvdatasync/service')->getCustomerAddressTypeId();
            $collection = Mage::helper('emvdatasync/service')->getMagentoAttributes();

            if ($collection && $collection->count()>0) {
                foreach ($collection as $item) {
                    // only take attribute isn't prohibited
                    if (in_array($item->getAttributeCode(),$this->_prohibitedAttributes)) {
                        continue;
                    }

                    // only display attributes that have a correct label
                    if ($item->getFrontendLabel() && $item->getFrontendInput() !== 'hidden') {
                        if ($item->getEntityTypeId() == $customerAddressEntityTypeId) {
                            if (!isset($this->_optionCustomerAttributes['customer_address'])) {
                                // initialize customer address attribute array
                                $this->_optionCustomerAttributes['customer_address'] = array(
                                    'value' => array(),
                                    'label' => $addressLabel
                                );
                               // initialize customer address attribute array
                                $this->_optionCustomerAttributes['customer_shipping_address'] = array(
                                    'value' => array(),
                                    'label' => $shippingAddressLabel
                                );
                            }

                            // billing address attribute
                            $finalAttributeId = Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::buildAttributeName(
                                    self::PREFIX_BILLING_ATTR,
                                    $item->getAttributeId()
                                );
                            $this->_optionCustomerAttributes['customer_address']['value'][$finalAttributeId]
                                = self::PREFIX_LABEL_BILLING . self::SEPARATOR_LABEL . $item->getFrontendLabel();

                            // shipping address attribute
                            $finalAttributeId = Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::buildAttributeName(
                                    self::PREFIX_SHIPPING_ATTR,
                                    $item->getAttributeId()
                                );
                            $this->_optionCustomerAttributes['customer_shipping_address']['value'][$finalAttributeId]
                                = self::PREFIX_LABEL_SHIPPING . self::SEPARATOR_LABEL . $item->getFrontendLabel();
                        } else {
                            if (!isset($this->_optionCustomerAttributes['customer'])) {
                                // initialize customer attribute array
                                $this->_optionCustomerAttributes['customer'] = array(
                                    'value' => array(),
                                    'label' => $customerLabel
                                );
                            }

                            $finalAttributeId = Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::buildAttributeName(
                                    self::PREFIX_CUSTOMER_ATTR,
                                    $item->getAttributeId()
                                );
                            $this->_optionCustomerAttributes['customer']['value'][$finalAttributeId]
                                = self::PREFIX_LABEL_CUSTOMER . self::SEPARATOR_LABEL . $item->getFrontendLabel();
                        }
                    }
                }
            }
        }

        return $this->_optionCustomerAttributes;
    }

    /**
     * For each customer/customer address attribute, provide an eav attribute model
     *
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::postPrepareAttribute()
     */
    public function postPrepareAttribute(array $attributeList)
    {
        $attributeIds = array_keys($attributeList);
        if (count($attributeIds)) {
            $collection = Mage::helper('emvdatasync/service')->getMagentoAttributes($attributeIds);
            if ($collection && $collection->count() > 0) {
                foreach($attributeList as $key => $fieldObject) {
                    $attribute = $collection->getItemById($fieldObject->getMappedAttributeId());
                    if ($attribute) {
                        $fieldObject->setAttributeObject($attribute);
                        $fieldObject->setMappedDataType($attribute->getBackendType());
                    }
                }
            }
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::canHandle()
     */
    public function canHandle($attributeData, $storeId)
    {
        $ok = false;
        if (isset($attributeData['prefix'])) {
            $allowedPrefix = array(self::PREFIX_BILLING_ATTR, self::PREFIX_CUSTOMER_ATTR, self::PREFIX_SHIPPING_ATTR);
            if (in_array($attributeData['prefix'], $allowedPrefix)) {
                $ok = true;
            }
        }

        return $ok;
    }


    /**
     * Apply customer and customer address attribute to select
     *
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::prepareSubscriberCollection()
     */
    public function prepareSubscriberCollection(
        Varien_Data_Collection_Db $collection,
        array $attributeList,
        $storeId = null
    )
    {
        $this->prepareDefaultBillingAndShippingAttrId();
        $joinFactory = array();
        // Get connection from collection
        $adapter = $collection->getConnection();

        // Prepare required table data (id, etc...)
        $tablePrefix         = (string)Mage::getConfig()->getTablePrefix();
        $customerEntityTable = $tablePrefix . 'customer_entity';
        $customerEavIntTable = $tablePrefix . 'customer_entity_int';

        // Get address entity type
        $customerAddressEntityTypeId = Mage::helper('emvdatasync/service')->getCustomerAddressTypeId();

        $joinFactory = array(
            'entity_table' => array(
                $customerEntityTable => array($customerEntityTable . '.entity_id'),
            ),
            'eav_tables' => array(
                self::PREFIX_CUSTOMER_ATTR => array(),
                self::PREFIX_BILLING_ATTR  => array(),
                self::PREFIX_SHIPPING_ATTR => array(),
            )
        );
        foreach ($attributeList as $mappedField) {
            // the attribute needs to have a backend table
            if ($mappedField->getAttributeObject() && $mappedField->getAttributeObject()->getBackend()) {
                // get attribute object
                $attribute = $mappedField->getAttributeObject();
                $preparedAttributeCode =  $attribute->getAttributeCode();

                $tableName = $attribute->getBackend()->getTable();
                // if the attribute value is retrieved from customer entity table
                if ($tableName == $customerEntityTable) {
                    $joinFactory['entity_table']
                        [$customerEntityTable][$attribute->getId()]
                        = $customerEntityTable . '.' . $attribute->getAttributeCode();
                } else {
                    $preparedAttributeCode =  $mappedField->getMappedEntityType()
                        . '_'. $attribute->getAttributeCode();
                    $joinFactory['eav_tables']
                        [$mappedField->getMappedEntityType()][$tableName][$attribute->getId()]
                        = $preparedAttributeCode;
                }

                // !!! use final_attribute_code to get value
                $mappedField->setFinalAttributeCode($preparedAttributeCode);
            }
        }

         // Manage entities fields
        $collection->getSelect()
            // Add customer_entity fields (no aliases, an only call for all customer entity fields)
            ->joinLeft(
                $customerEntityTable,
                $customerEntityTable . '.entity_id = main_table.customer_id',
                $joinFactory['entity_table'][$customerEntityTable]
            )
            // find default billing address entity id
            ->joinLeft(
                array('default_billing_id_table' => $customerEavIntTable),
                $adapter->quoteInto(
                    'default_billing_id_table.entity_id = main_table.customer_id'
                        . ' AND default_billing_id_table.attribute_id = ?',
                    $this->_defaultBillingAttrId
                ),
                array('default_billing_id' => 'default_billing_id_table.value')
            )
            // find default shipping address entity id
            ->joinLeft(
                array('default_shipping_id_table' => $customerEavIntTable),
                $adapter->quoteInto(
                    'default_shipping_id_table.entity_id = main_table.customer_id'
                        . ' AND default_shipping_id_table.attribute_id = ?',
                    $this->_defaultShippingAttrId
                ),
                array('default_shipping_id' => 'default_shipping_id_table.value')
            )
            ;

        // retrieve different attributes from different tables
        foreach ($joinFactory['eav_tables'] as $type => $tables) {
            if ($type == self::PREFIX_CUSTOMER_ATTR) {
                foreach($tables as $tableName => $fields) {
                    foreach ($fields as $fieldId => $fieldSql) {
                        $collection->getSelect()
                            ->joinLeft(
                                array($fieldSql . '_table' => $tableName),
                                $adapter->quoteInto(
                                    $fieldSql . '_table' . '.entity_id = main_table.customer_id'
                                        . ' AND ' . $fieldSql . '_table' . '.attribute_id = ?',
                                    $fieldId
                                ),
                                array($fieldSql  => $fieldSql . '_table' . '.value')
                            );
                    }
                }
            } else {
                foreach($tables as $tableName => $fields) {
                    foreach ($fields as $fieldId => $fieldSql) {
                        if ($type == self::PREFIX_SHIPPING_ATTR) {
                            $condition = $adapter->quoteInto(
                                $fieldSql . '_table' . '.entity_id = default_shipping_id_table.value'
                                    . ' AND ' . $fieldSql . '_table' . '.attribute_id = ?',
                                $fieldId
                            );
                        } else {
                            $condition = $adapter->quoteInto(
                                $fieldSql . '_table' . '.entity_id = default_billing_id_table.value'
                                    . ' AND ' . $fieldSql . '_table' . '.attribute_id = ?',
                                $fieldId
                            );
                        }

                        $collection->getSelect()
                            ->joinLeft(
                                array($fieldSql . '_table' => $tableName),
                                $condition,
                                array($fieldSql  => $fieldSql . '_table' . '.value')
                            );
                    }
                }
            }
        }

        return $collection;
    }
}