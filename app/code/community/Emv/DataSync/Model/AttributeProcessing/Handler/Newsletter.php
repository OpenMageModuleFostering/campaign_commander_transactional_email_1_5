<?php
/**
 * Newslleter Attribute handler
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_AttributeProcessing_Handler_Newsletter
    extends Emv_DataSync_Model_AttributeProcessing_Handler_Abstract
{
    const PREFIX_NEWSLETTER_FIELD  = 'newsletter';

    protected $_tableName          = 'newsletter/subscriber';
    protected $_tableDefintion     = array();
    protected $_isLoaded           = false;

    /**
     * @return array
     */
    public function getTableDefinition()
    {
        if (!$this->_isLoaded) {
            // add subscriber's column definition into array
            $readAdaptator = Mage::getSingleton('core/resource')->getConnection('core_read');
            $this->_tableDefintion = $readAdaptator
                 ->describeTable(Mage::getSingleton('core/resource')->getTableName($this->_tableName));
            $this->_isLoaded = true;
        }
        return $this->_tableDefintion;
    }

    /**
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::toOptionArray()
     */
    public function toOptionArray()
    {
        if (is_null($this->_optionCustomerAttributes)) {
            $this->_optionCustomerAttributes = array();
            if ($this->getTableDefinition() && is_array($this->getTableDefinition())) {
                foreach ($this->getTableDefinition() as $columnName => $columnDefintion) {
                    if (!isset($this->_optionCustomerAttributes['newsletter_subscriber'])) {
                        $this->_optionCustomerAttributes['newsletter_subscriber'] = array(
                            'value' => array(),
                            'label' => Mage::helper('emvdatasync')->__('Newsletter Subscriber')
                        );
                    }
                    $finalAttributeId = Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::buildAttributeName(
                            self::PREFIX_NEWSLETTER_FIELD,
                            $columnName
                        );
                    $this->_optionCustomerAttributes['newsletter_subscriber']['value'][$finalAttributeId] = $columnName;
                }
            }
        }

        return $this->_optionCustomerAttributes;
    }

    /**
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::canHandle()
     */
    public function canHandle($attributeData, $storeId)
    {
        $ok = false;
        if (isset($attributeData['prefix'])) {
            $allowedPrefix = array(self::PREFIX_NEWSLETTER_FIELD);
            if (in_array($attributeData['prefix'], $allowedPrefix)) {
                $ok = true;
            }
        }

        return $ok;
    }

    /**
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::postPrepareAttribute()
     */
    public function postPrepareAttribute(array $attributeList)
    {
        $tableDefinition = $this->getTableDefinition();
        if ($tableDefinition && is_array($tableDefinition)) {
            foreach($attributeList as $key => $fieldObject) {
                if (
                    isset($tableDefinition[$fieldObject->getMappedAttributeId()])
                    && isset($tableDefinition[$fieldObject->getMappedAttributeId()]['DATA_TYPE'])
                ) {
                    $fieldObject->setMappedDataType($tableDefinition[$fieldObject->getMappedAttributeId()]['DATA_TYPE']);
                }
            }
        }

        return $this;
    }
}