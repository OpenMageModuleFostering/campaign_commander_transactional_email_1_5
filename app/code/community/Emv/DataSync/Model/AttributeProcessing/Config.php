<?php
/**
 * Attribute handler config for data sync
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_AttributeProcessing_Config
{
    const XML_PATH_ATTRIBUTE_CONFIG = 'global/smartfocus_attribute_processing';

    /**
     * Default attribute handler model
     */
    const DEFAULT_HANDLER_MODEL     = 'emvdatasync/attributeProcessing_handler_customer';

    /**
     * Config node that stores available attribute handlers
     *
     * @var Mage_Core_Model_Config_Element
     */
    protected $_configNode;

    /**
     * List of attribute handlers
     * @var array
     */
    protected $_handlerList = array();

    /**
     * Attributes to be selected
     *
     * @var array
     */
    protected $_attributesToSelect = array();

    /**
     * Attributes, fields sorted by handlers
     *
     * @var array
     */
    protected $_attributesByHandlers = array();

    /**
     * Store information
     * @var array
     */
    protected $_stores;

    /**
     * constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->_loadHandlerConfig();
    }

    /**
     * Load all attribute handler from config xml
     */
    protected function _loadHandlerConfig()
    {
        $this->_handlerList = array();
        $this->_handlerList['default'] = Mage::getSingleton(self::DEFAULT_HANDLER_MODEL);
        foreach($this->getConfigNode()->children() as $code => $modelClassName)
        {
            $model = Mage::getSingleton($modelClassName);
            if ($model instanceof Emv_DataSync_Model_AttributeProcessing_Handler_Abstract) {
                $this->_handlerList[$code] = $model;
            }
        }
    }

    /**
     * Get handler list
     *
     * @return array
     */
    public function getHandlerList()
    {
        return $this->_handlerList;
    }

    /**
     * Get config node that contains available attribute handlers from config xml
     *
     * @return Mage_Core_Model_Config_Element
     */
    public function getConfigNode()
    {
        if (is_null($this->_configNode)) {
            $this->_configNode = Mage::app()->getConfig()->getNode(self::XML_PATH_ATTRIBUTE_CONFIG);
        }
        return $this->_configNode;
    }

    /**
     * Get all attribute option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrayReturn = array();
        foreach($this->getHandlerList() as $model) {
            $arrayReturn = array_merge($arrayReturn, $model->toOptionArray());
        }
        return $arrayReturn;
    }

    /**
     * @param string $default
     * @return string
     */
    public static function getArrayKey($default)
    {
        $arrayKey = $default;
        if ($arrayKey === null) {
            $arrayKey = 'undefined';
        }
        return $arrayKey;
    }

    /**
     * Prepare and get mapped customer attributes for a given store
     *
     * @param string $storeId
     * @return array
     */
    public function prepareAndGetMappedCustomerAttributes($storeId = null)
    {
        // by default the key will be store id, else will be set to undefined
        $arrayKey = self::getArrayKey($storeId);

        if (!isset($this->_attributesToSelect[$arrayKey])) {
            // init $this->_entityFieldsToSelect
            $this->_attributesToSelect[$arrayKey]   = array();
            $this->_attributesByHandlers[$arrayKey] = array();

            $mappedAttributes = Mage::helper('emvdatasync')->getEmvMappedCustomerAttributes($storeId);
            if (count($mappedAttributes)) {
                 $preparedArray = $this->_prepareAttributes($mappedAttributes, $storeId);
                 $this->_attributesToSelect[$arrayKey]   = $preparedArray['mapped_attributes'];
                 $this->_attributesByHandlers[$arrayKey] = $preparedArray['attributes_by_handler'];
            }
        }

        return $this->_attributesToSelect[$arrayKey];
    }

    /**
     * @param array $mappedAttributes
     * @param int $storeId
     * @return array - associative array
     *     mapped_attributes => all mapped attributes
     *     attributes_by_handler => attributes sorted by handlers
     */
    protected function _prepareAttributes($mappedAttributes, $storeId)
    {
        $attributesByHandlers = array();

        foreach ($mappedAttributes as $key => $field) {
            // the field follows one of these patterns :
            // billing|1, shipping|2, customer|1, newsletter|subscriber_email
            $info = Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::getAttributeConfigParts(
                    $field['magento_fields']
                );

            // create a new object
            $fieldObject = new Varien_Object();

            // !!! use final_attribute_code to get value
            $fieldObject->setFinalAttributeCode($info['name']);

            // need to know which SmartFocus attribute has been mapped to magento one
            $fieldObject->setMappedEmailVisionKey($field['emailvision_fields']);
            $fieldObject->setMappedAttributeId($info['name']);
            $fieldObject->setMappedEntityType($info['prefix']);
            $fieldObject->setMappedMagentoFields($field['magento_fields']);
            $mappedAttributes[$key] = $fieldObject;

            foreach ($this->getHandlerList() as $name => $handler) {
                // test handler can handle this mapped attribute
                if ($handler->canHandle($info, $storeId)) {
                    if (!isset($attributesByHandlers[$name])) {
                        $attributesByHandlers[$name] = array();
                    }
                    $attributesByHandlers[$name][$info['name']] = $fieldObject;
                }
            }
        }

        foreach ($this->getHandlerList() as $name => $handler) {
            if (isset($attributesByHandlers[$name])) {
                $handler->postPrepareAttribute($attributesByHandlers[$name]);
            }
        }

        return array('mapped_attributes' => $mappedAttributes, 'attributes_by_handler' => $attributesByHandlers);
    }

    /**
     * Prepare subscriber collection according to mapped attributes. A event called member_collection_load_after will
     * be dispatched with collection and store id data
     *
     * @param Varien_Data_Collection_Db $collection
     * @param string $storeId
     * @return Varien_Data_Collection_Db
     */
    public function prepareSubscriberCollection(
            Varien_Data_Collection_Db $collection,
            $storeId = null
    )
    {
        // by default the key will be store id, else will be set to undefined
        $arrayKey = self::getArrayKey($storeId);
        $this->prepareAndGetMappedCustomerAttributes($storeId);
        foreach ($this->getHandlerList() as $name => $handler) {
            if (
                isset($this->_attributesByHandlers[$arrayKey])
                && isset($this->_attributesByHandlers[$arrayKey][$name])
            ) {
                $handler->prepareSubscriberCollection(
                    $collection,
                    $this->_attributesByHandlers[$arrayKey][$name],
                    $storeId
                );
            }
        }

        // dispatch a new event in order to add additional data if needed
        Mage::dispatchEvent('member_collection_load_after', array('members' => $collection, 'store_id' => $storeId));

        return $collection;
    }

    /**
     * Get subscriber data according to mapped attributes
     *
     * @param Varien_Object $subscriber
     * @param string $storeId
     * @return array
     */
    public function getSubscriberData(Varien_Object $subscriber, $storeId = null)
    {
        // Get mapped SmartFocus entity id
        $entityId = strtoupper(Mage::helper('emvdatasync')->getMappedEntityId($storeId));

        if (is_null($this->_stores)) {
           $this->_stores = Mage::app()->getStores(true);
        }

        // Prepare and add a customer's data row
        $subscriberData = array();

        // retreive all maped attribute values from subscriber, build them into array
        $fieldsToSelect = $this->prepareAndGetMappedCustomerAttributes($storeId);

        foreach ($fieldsToSelect as $attribute) {
            $emailVisionKey = strtoupper($attribute->getMappedEmailVisionKey());

            $fieldCode  = ($attribute->getFinalAttributeCode())
                ? $attribute->getFinalAttributeCode() : $attribute->getAttributeCode();
            $fieldValue = '';

            if ($attribute->getMappedDataType() == 'datetime') {
                if ($subscriber->getData($fieldCode)) {
                    // date time should be in EmailVision format
                    $fieldValue = Mage::helper('emvdatasync/service')
                        ->getEmailVisionDate($subscriber->getData($fieldCode));
                }
            } else {
                $fieldValue = $subscriber->getData($fieldCode);
                if ($fieldCode == 'store_id' && isset($stores[$fieldValue])) {
                    $fieldValue = $stores[$fieldValue]->getName();
                }
            }

            $subscriberData[$emailVisionKey] = $fieldValue;
        }

        // entity id field
        $subscriberData[$entityId] = $subscriber->getId();

        // If is unjoined
        $unjoinedDate = '';
        if ($subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
            if ($subscriber->getData(Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN)) {
                // date time should be in EmailVision format
                $unjoinedDate = Mage::helper('emvdatasync/service')
                    ->getEmailVisionDate($subscriber->getData(Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN));
            }
        }
        $subscriberData[strtoupper(Emv_Core_Model_Service_Member::FIELD_UNJOIN)] = $unjoinedDate;

        return $subscriberData;
    }

    /**
     * Clean mapped customer attributes
     *
     * @param string $storeId
     */
    public function cleanMappedCustomerAttributes($storeId = null)
    {
        // by default the key will be store id, else will be set to undefined
        $arrayKey = $this->getArrayKey($storeId);
        foreach ($this->_attributesToSelect[$arrayKey] as $key => $attribute)
        {
            if ($attribute->getAttributeObject()) {
                $attribute->getAttributeObject()->unsetData()->unsetOldData();
            }
            $attribute->unsetData()->unsetOldData();
        }
        unset($this->_attributesToSelect[$arrayKey]);
    }
}