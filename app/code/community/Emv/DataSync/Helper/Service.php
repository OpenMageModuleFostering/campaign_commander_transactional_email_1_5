<?php
/**
 * Data helper for Sync Services
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Helper_Service extends Mage_Core_Helper_Abstract
{
/**
     * @var Varien_Data_Collection_Db
     */
    protected $_entityFieldsToSelect;

    /**
     * Attribute Join list
     * @var array
     */
    protected $_joinFactory = null;

    /**
     * @var string
     */
    protected $_customerTypeId = null;

    /**
     * @var string
     */
    protected $_customerAddressTypeId = null;

    const FIELD_MEMBER_LAST_UPDATE = 'member_last_update_date';
    const FIELD_DATA_LAST_UPDATE   = 'data_last_update_date';
    const FIELD_DATE_UNJOIN        = 'date_unjoin';

    /**
     * Get magento attribute for the given attribute ids
     *
     * @param array $attributeIds
     * @return Mage_Eav_Model_Resource_Entity_Attribute_Collection
     */
    public function getMagentoAttributes($attributeIds = array())
    {
        $magentoAttributes = array();
        $customerTypeId              = $this->getCustomerTypeId();
        $customerAddressEntityTypeId = $this->getCustomerAddressTypeId();

        $collection = Mage::getResourceModel('eav/entity_attribute_collection')
            ->addFieldToFilter(
                'entity_type_id',
                array('in' => array($customerTypeId, $customerAddressEntityTypeId))
            )
            ->addFieldToFilter(
                'attribute_id',
                array('in' => $attributeIds)
            )
        ;

        return $collection;
    }

    /**
     * Prepare and get mapped customer attributes
     *
     * @return Varien_Data_Collection_Db
     */
    public function prepareAndGetMappedCustomerAttributes()
    {
        if ($this->_entityFieldsToSelect == null) {
            // init $this->_entityFieldsToSelect
            $this->_entityFieldsToSelect = new Varien_Data_Collection_Db();

            $mappedFields = Mage::helper('emvdatasync')->getEmvMappedCustomerAttributes();
            if (count($mappedFields)) {
                // retreive magento attribute ids
                $attributeIds = array();
                foreach ($mappedFields as $field) {
                    $attributeIds[] = $field['magento_fields'] ;
                }

                if (count($attributeIds)) {
                    $collection = $this->getMagentoAttributes($attributeIds);
                    if ($collection && $collection->count() > 0) {
                        foreach($mappedFields as $field) {
                            $attribute = $collection->getItemById($field['magento_fields']);
                            if ($attribute) {
                                // need to know which SmartFocus attribute has been mapped to magento one
                                $attribute->setEmailVisionKey($field['emailvision_fields']);
                            }
                        }

                        $this->_entityFieldsToSelect = $collection;
                    }
                }
            }
        }

        return $this->_entityFieldsToSelect;
    }

    /**
     * Add customer and address attributes into subscriber select
     *
     * @param Mage_Newsletter_Model_Resource_Subscriber_Collection $collection
     * @return Mage_Newsletter_Model_Resource_Subscriber_Collection
     */
    public function addCustomerAndAddressAttributes(Varien_Data_Collection_Db $collection)
    {
        /* @var $customerModel Mage_Customer_Model_Customer */
        /* @var $addressModel Mage_Customer_Model_Address */
        // Get connection from collection
        $adapter = $collection->getConnection();

        // Prepare required table data (id, etc...)
        $tablePrefix         = (string)Mage::getConfig()->getTablePrefix();
        $customerEntityTable = $tablePrefix . 'customer_entity';
        $customerEavIntTable = $tablePrefix . 'customer_entity_int';
        $addressEntityTable  = $tablePrefix . 'customer_address_entity';

        // Prepare Default billing attribute (customer eav, required to get only one address)
        $defaultBillingAttribute = Mage::getModel('customer/customer')->getAttribute('default_billing');

        // Get address entity type
        $customerAddressEntityTypeId = $this->getCustomerAddressTypeId();

        if (!isset($this->_joinFactory)) {
            // Prepare empty joinBuilder array
            // (customer entity must be joined in order to be able of getting address)
            $this->_joinFactory = array(
                'entity_table' => array(
                    $customerEntityTable => array($customerEntityTable . '.entity_id'),
                    $addressEntityTable  => array($addressEntityTable . '.entity_id')
                ),
                'eav_tables' => array()
            );

            // Prepare a join builder array (to avoid to do a join per attribute, even on a unique table)
            foreach ($this->prepareAndGetMappedCustomerAttributes() as $attribute) {
                $tableName = $attribute->getBackend()->getTable();
                $tableType = 'eav_tables';
                $fieldPrefix = '';
                $preparedAttributeCode = $attribute->getAttributeCode();

                if ($attribute->getEntityTypeId() == $customerAddressEntityTypeId) {
                    if (strpos($tableName, $addressEntityTable) !== false) {
                        $preparedAttributeCode = 'customer_address_' . $preparedAttributeCode;
                    }
                } else {
                    // Check table type (entity or eav)
                    if ($tableName == $customerEntityTable) {
                        $tableType = 'entity_table';
                        $fieldPrefix = $tableName . '.';
                    }

                    // only get store_id from newsletter subscriber table
                    if ($attribute->getAttributeCode() == 'store_id') {
                        continue;
                    }
                }

                // Add current attribute as field to join
                $this->_joinFactory[$tableType][$tableName][$attribute->getId()]
                   = $fieldPrefix . $attribute->getAttributeCode();

                // !!! use final_attribute_code to get value
                $attribute->setFinalAttributeCode($preparedAttributeCode);
            }
        }

        // Manage entities fields
        $collection->getSelect()
            // Add customer_entity fields (no aliases, an only call for all customer entity fields)
            ->joinLeft(
                $customerEntityTable,
                $customerEntityTable . '.entity_id = main_table.customer_id',
                $this->_joinFactory['entity_table'][$customerEntityTable]
            )
            // Join default billing attributes (limit address results to 1)
            ->joinLeft(
                array('default_billing_id_table' => $customerEavIntTable),
                $adapter->quoteInto(
                    'default_billing_id_table.entity_id = main_table.customer_id'
                        . ' AND default_billing_id_table.attribute_id = ?',
                    (int)$defaultBillingAttribute->getAttributeId()
                ),
                array('default_billing_id' => 'default_billing_id_table.value')
            )
            // Add customer address entity fields
            ->joinLeft(
                $addressEntityTable,
                $adapter->quoteInto(
                    $customerEntityTable . '.entity_id = '. $addressEntityTable . '.parent_id'
                        . ' AND '. $addressEntityTable . '.entity_id = default_billing_id_table.value'
                        . ' AND default_billing_id_table.attribute_id = ?',
                    (int)$defaultBillingAttribute->getAttributeId()
                ),
                $this->_joinFactory['entity_table'][$addressEntityTable]
            );

        // Join a table foreach eav attributes. Two ways, customer eav or address eav
        foreach ($this->_joinFactory['eav_tables'] as $tableName => $fields) {
            // Address eav attributes
            if (strpos($tableName, 'customer_address') !== false) {
                foreach ($fields as $fieldId => $fieldSql) {
                    $collection->getSelect()
                        ->joinLeft(
                            array('customer_address_' . $fieldSql . '_table' => $tableName),
                            $adapter->quoteInto(
                                'customer_address_' . $fieldSql . '_table' . '.entity_id = ' . $addressEntityTable . '.entity_id'
                                    . ' AND customer_address_' . $fieldSql . '_table' . '.attribute_id = ?',
                                $fieldId
                            ),
                            array('customer_address_' . $fieldSql  => 'customer_address_' . $fieldSql . '_table' . '.value')
                        );
                }
            } else {// Customer eav attributes
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
        }

        // dispatch a new event in order to add additional data if needed
        Mage::dispatchEvent('member_collection_load_after', array('members' => $collection));

        return $collection;
    }

    /**
     * Get date in EmailVison format in the following format : m/d/Y H:i:s (such as 10/09/2013 14:31:24)
     *
     * @param string $date in GMT timezone
     * @return string
     */
    public function getEmailVisionDate($date)
    {
        $dateModel = Mage::getSingleton('core/date');
        return $dateModel->date(
            'm/d/Y H:i:s',
            $date
        );
    }

    /**
     * @return string
     */
    public function getCustomerTypeId()
    {
        if ($this->_customerTypeId == null) {
            $this->_customerTypeId = Mage::getModel('eav/entity')->setType('customer')->getTypeId();
        }
        return $this->_customerTypeId;
    }

    /**
     * @return string
     */
    public function getCustomerAddressTypeId()
    {
        if ($this->_customerAddressTypeId == null) {
            $this->_customerAddressTypeId = Mage::getModel('eav/entity')->setType('customer_address')->getTypeId();
        }
        return $this->_customerAddressTypeId;
    }

    /**
     * @param array $updatedSubscribers
     * @param array $rejoinedSubscribers
     * @param string $time
     * @return boolean
     */
    public function massSetMemberLastUpdateDate($updatedSubscribers = array(), $rejoinedSubscribers = array(), $time = null)
    {
        $error = true;

        if (!empty($updatedSubscribers)) {
            /* @var $write Varien_Db_Adapter_Pdo_Mysql */
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $subscriberTable = Mage::getResourceModel('newsletter/subscriber')->getMainTable();
            // date should be store ind GMT timezone
            $now = Mage::helper('emvdatasync')->getFormattedGmtDateTime();
            if ($time == null) {
                $time = $now;
            }

            try {
                // Update memberLastUpdateDate
                $fieldMemberLastUpdate = self::FIELD_MEMBER_LAST_UPDATE;
                $sqlQuery = "UPDATE $subscriberTable SET {$fieldMemberLastUpdate} = '$time'
                WHERE subscriber_id  IN(" . implode(',', $updatedSubscribers) . ');';
                $write->query($sqlQuery);

                if (!empty($rejoinedSubscribers)) {
                    // Reset UnjoinDate for Rejoined subscribers
                    $fieldUnjoinDate = self::FIELD_DATE_UNJOIN;
                    $sqlQuery = "UPDATE $subscriberTable SET $fieldUnjoinDate = null
                    WHERE subscriber_id  IN(" . implode(',', $rejoinedSubscribers) . ');';
                    $write->query($sqlQuery);
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $error  = 'Exception while processing massSetMemberLastUpdateDate with exceptionMessage:\n        ' . $e->getMessage();
                $error .= '\n        Subscribers ids detail: ' . implode(', ', $updatedSubscribers);
            }
        }

        return $error;
    }
}