<?php
/**
 * Block to display customer attributes in back-office configuration selects
 *
 * @category   Emv
 * @package    Emv_DataSync
 * @copyright  Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Block_Adminhtml_System_Config_CustomerAttributes
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var Emv_DataSync_Block_Adminhtml_Form_Field_CustomerAttributes
     */
    protected $_magentoFieldsRenderer;

    /**
     * @var Emv_DataSync_Block_Adminhtml_Form_Field_EmailVisionFields
     */
    protected $_emailVisionFieldsRenderer;

    public function __construct()
    {
        $this->setTemplate('smartfocus/datasync/system/config/form/field/array.phtml');
        parent::__construct();
    }

    /**
     * Retrieve group column renderer
     *
     * @return Emv_DataSync_Block_Adminhtml_Form_Field_CustomerAttributes
     */
    protected function _getMagentoFieldsRenderer()
    {
        if (!$this->_magentoFieldsRenderer) {
            $this->_magentoFieldsRenderer = $this->getLayout()->createBlock(
                'emvdatasync/adminhtml_form_field_customerAttributes', '',
                array('is_render_to_js_template' => true)
            );
            $this->_magentoFieldsRenderer->setClass('customer_group_select');
            $this->_magentoFieldsRenderer->setExtraParams('style="width:200px"');
        }
        return $this->_magentoFieldsRenderer;
    }

    /**
     * Retrieve EmailVision fields column renderer
     *
     * @return Emv_DataSync_Block_Adminhtml_Form_Field_EmailVisionFields
     */
    protected function _getEmailVisionFieldsRenderer()
    {
        if (!$this->_emailVisionFieldsRenderer) {
            $this->_emailVisionFieldsRenderer = $this->getLayout()->createBlock(
                'emvdatasync/adminhtml_form_field_emailVisionFields', '',
                array('is_render_to_js_template' => true)
            );
            $this->_magentoFieldsRenderer->setClass('customer_group_select');
            $this->_magentoFieldsRenderer->setExtraParams('style="width:200px"');
        }
        return $this->_emailVisionFieldsRenderer;
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract::_prepareToRender()
     */
    protected function _prepareToRender()
    {
        $this->addColumn('magento_fields', array(
            'label' => Mage::helper('adminhtml')->__('Magento Fields'),
            'style' => 'width:200px',
            'renderer' => $this->_getMagentoFieldsRenderer(),
        ));
        $this->addColumn('emailvision_fields', array(
            'label' => Mage::helper('adminhtml')->__('SmartFocus Member Fields'),
            'style' => 'width:200px',
            'renderer' => $this->_getEmailVisionFieldsRenderer(),
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Field');
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract::_prepareArrayRow()
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_'
                . $this->_getMagentoFieldsRenderer()->calcOptionHash($row->getData('magento_fields')),
            'selected="selected"'
        );
        $row->setData(
            'option_extra_attr_'
                . $this->_getEmailVisionFieldsRenderer()->calcOptionHash($row->getData('emailvision_fields')),
            'selected="selected"'
        );
    }
}
