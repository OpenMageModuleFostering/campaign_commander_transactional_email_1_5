<?php
/**
 *  EMV DYN attribute tab for SmartFocus email template
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Edit_Tab_EmvDyn extends Mage_Adminhtml_Block_Widget
{
    /**
     * Max rows for textarea
     * @var int
     */
    protected $_maxRowsForTextarea = 2;

    /**
     * PREFIX constants
     */
    const PREFIX_REFRESH_BUTTON = 'refresh_attributes_';
    const PREFIX_ATTRIBUTE_TABLE = 'attributes_';
    const PREFIX_INVALID_ATTRIBUTE_TABLE = 'attributes_';
    const PREFIX_CONTENT_DIV = 'content_';

    /**
     * Index attribute Registry
     */
    const ATTRIBUTE_INDEX_REGISTRY = 'emv_attributes_index';

    /**
     * Invalid attributes
     * @var array
     */
    protected $_invalidAttributes = array();

    /**
     * Available attributes
     * @var array
     */
    protected $_availableAttributes = array();

    /**
     * Attribute type (EMV DYN)
     * @var string
     */
    protected $_attributeType = Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_DYN;

    /**
     * Set a new template
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('smartfocus/emt/template/mapped_attributes.phtml');
    }

    /**
     * @return string
     */
    public function getAttributeType()
    {
        return $this->_attributeType;
    }

    /**
     * @param string $type
     * @return Emv_Emt_Block_Adminhtml_Template_Edit_Tab_EmvDyn
     */
    public function setAttributeType($type)
    {
        $this->_attributeType = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getRefreshButtonId()
    {
        return self::PREFIX_REFRESH_BUTTON . $this->getAttributeType();
    }

    /**
     * @return string
     */
    public function getAttributesTableId()
    {
        return self::PREFIX_ATTRIBUTE_TABLE . $this->getAttributeType();
    }

    /**
     * @return string
     */
    public function getInvalidAttributeTableId()
    {
        return self::PREFIX_INVALID_ATTRIBUTE_TABLE . $this->getAttributeType();
    }

    /**
     * @return string
     */
    public function getDivContentId()
    {
        return self::PREFIX_CONTENT_DIV . $this->getAttributeType();
    }

    /**
     * @return string
     */
    public function getRefreshButtonHtml()
    {
        return $this->getChildHtml('refresh_button');
    }

    /**
     * Prepare attributes from registry
     */
    public function prepareAttributes()
    {
        $helper = Mage::helper('emvemt/emvtemplate');
        $allAttributes = $helper->getAllEmvFieldsFromRegistry();
        $mappedAttributes = $helper->getSortedMappedAttributesFromRegistry();

        if (
            $allAttributes
            && is_array($allAttributes)
            && isset($allAttributes[$this->getAttributeType()])
        ) {
            // prepare invalid attributes
            // invalid attributes are mapped ones which are not in available attribute list
            if (
                $mappedAttributes
                && is_array($mappedAttributes)
                && isset($mappedAttributes[$this->getAttributeType()])
            ) {
                foreach ($mappedAttributes[$this->getAttributeType()] as $attribute) {
                    $preparedAttribute = $this->getAttribute($attribute['emv_attribute'], $attribute);
                    if (
                        !isset($allAttributes[$this->getAttributeType()][$attribute['emv_attribute']])
                    ) {
                        $preparedAttribute['invalid'] = true;
                        $this->_invalidAttributes[$attribute['emv_attribute']]   = $preparedAttribute;
                    } else {
                        $this->_availableAttributes[$attribute['emv_attribute']] = $preparedAttribute;
                    }
                }
            }

            foreach ($allAttributes[$this->getAttributeType()] as $attributeName) {
                if (!isset($this->_availableAttributes[$attributeName])) {
                    $this->_availableAttributes[$attributeName] = $this->getAttribute($attributeName);
                }
            }
        } elseif (
            $mappedAttributes
            && is_array($mappedAttributes)
            && isset($mappedAttributes[$this->getAttributeType()])
        ) {
            foreach ($mappedAttributes[$this->getAttributeType()] as $attribute) {
                $preparedAttribute = $this->getAttribute($attribute['emv_attribute'], $attribute);
                $this->_availableAttributes[$attribute['emv_attribute']] = $preparedAttribute;
            }
        }
    }

    /**
     * Prepare an EMV attribute according to the given parameters.
     *
     * @param string $emvName
     * @param array $mappedAttribute
     * @return Ambigous <string, multitype:string unknown >
     */
    public function getAttribute($emvName, $mappedAttribute = null)
    {
        $preparedAttribute = array();
        if ($mappedAttribute) {
            $preparedAttribute = $mappedAttribute;
        } else {
            $preparedAttribute['emv_attribute'] = $emvName;
            $preparedAttribute['mage_attribute'] = '';
            $preparedAttribute['id'] = '';
            $preparedAttribute['emv_attribute_type'] = $this->getAttributeType();
        }

        return $preparedAttribute;
    }

    /**
     * @return array
     */
    public function getInvalidAttributes()
    {
        return $this->_invalidAttributes;
    }

    /**
     * @return array
     */
    public function getAvailableAttributes()
    {
        return $this->_availableAttributes;
    }

    /**
     * @return int
     */
    public function getNewIndexForAttributes()
    {
        $indexFromRegistry = Mage::registry(self::ATTRIBUTE_INDEX_REGISTRY);
        if ($indexFromRegistry === null) {
            $indexFromRegistry = 0;
        } else {
            (int)$indexFromRegistry++;
        }

        Mage::unregister(self::ATTRIBUTE_INDEX_REGISTRY);
        Mage::register(self::ATTRIBUTE_INDEX_REGISTRY, $indexFromRegistry);
        return $indexFromRegistry;
    }

    /**
     * Get max rows for textarea
     *
     * @return number
     */
    public function getTextareaRows()
    {
        return $this->_maxRowsForTextarea;
    }
}