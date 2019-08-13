<?php
/**
 * Tabs for "edit campaign commander template" menu
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Edit extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        $this->setTemplate('smartfocus/emt/template/edit.phtml');
        $this->setId('emv_email_template');
    }

    /**
     * Prepare all necessary buttons
     *
     * (non-PHPdoc)
     * @see Mage_Core_Block_Abstract::_prepareLayout()
     */
    protected function _prepareLayout()
    {
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->addItem('js', 'prototype/window.js')
                ->addItem('js_css', 'prototype/windows/themes/default.css')
                ->addCss('lib/prototype/windows/themes/magento.css')
                ->addCss('smartfocus.css')
                ->addItem('js', 'mage/adminhtml/variables.js')
                ->addItem('js_css', 'prototype/windows/themes/magento.css');
        }

        // back button - get back to grid menu
        $this->setChild('back_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('catalog')->__('Back'),
                    'onclick'   => 'setLocation(\''.$this->getUrl('*/*/', array()).'\')',
                    'class' => 'back'
                ))
        );

        // reset button
        $this->setChild('reset_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('catalog')->__('Reset'),
                    'onclick'   => 'setLocation(\''.$this->getUrl('*/*/*', array('_current'=>true)).'\')'
                ))
        );

        // get SmartFocus email template
        $emtModel = $this->getEmt();
        // only display the following buttons if a template has been created
        if ($emtModel->getId()) {
            // save and continue edit button
            $this->setChild('save_and_edit_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label'     => Mage::helper('catalog')->__('Save and Continue Edit'),
                        'onclick'   => 'saveAndContinueEdit(\''.$this->getSaveAndContinueUrl().'\')',
                        'class' => 'save'
                    ))
            );

            // delete button
            $this->setChild('delete_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label'     => Mage::helper('catalog')->__('Delete'),
                        'onclick'   => 'confirmSetLocation(\''.Mage::helper('catalog')->__('Are you sure?').'\', \''.$this->getDeleteUrl().'\')',
                        'class'  => 'delete'
                    ))
            );

            // refresh SmartFocus Attributes
            $this->setChild('refresh_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('emvemt')->__('Refresh All Attributes'),
                        'onclick' => "getEmvAttribute();"
                    ))
            );
            // insert Magento template
            $this->setChild('insert_variable_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('emvemt')->__('Insert Prepared Magento Variables'),
                        'onclick' => "openVariableChooser();"
                    ))
            );
            // reset button
            $this->setChild('preview_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label'     => Mage::helper('emvemt')->__('Preview'),
                        'onclick'   => 'openPreview();'
                    ))
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeader()
    {
        if (Mage::registry('mage_template_name')) {
            return Mage::helper('emvemt')->__("Edit '%s' Email Template",
                $this->htmlEscape(Mage::registry('mage_template_name')));
        } else {
            return Mage::helper('emvemt')->__("New Email Template");
        }
    }

    /**
     * @return Emv_Emt_Model_Emt
     */
    public function getEmt()
    {
        // get template from registry
        $emtModel = Mage::registry(Emv_Emt_Adminhtml_TemplateController::CURRENT_EMVEMT_TEMPLATE_REGISTRY);
        if ($emtModel == null) {
            $emtModel = Mage::getModel('emvemt/emt');
        }
        return $emtModel;
    }

    /**
     * @return string
     */
    public function getBackButtonHtml()
    {
        return $this->getChildHtml('back_button');
    }

    /**
     * @return string
     */
    public function getSaveAndEditButtonHtml()
    {
        return $this->getChildHtml('save_and_edit_button');
    }

    /**
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    /**
     * @return string
     */
    public function getCancelButtonHtml()
    {
        return $this->getChildHtml('reset_button');
    }

    /**
     * @return string
     */
    public function getEmvAttributeButtonHtml()
    {
        return $this->getChildHtml('refresh_button');
    }

    /**
     * @return string
     */
    public function getInsertMageVariableButtonHtml()
    {
        return $this->getChildHtml('insert_variable_button');
    }

    /**
     * @return string
     */
    public function getPreviewButtonHtml()
    {
        return $this->getChildHtml('preview_button');
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current'=>true, 'back'=>null));
    }

    /**
     * @return string
     */
    public function getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', array(
            '_current'   => true,
            'back'       => 'edit',
            'tab'        => '{{tab_id}}',
            'active_tab' => null
        ));
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array('_current'=>true));
    }

    /**
     * @return string
     */
    public function getPreviewUrl()
    {
        return $this->getUrl('*/*/getPreview', array('_current'=>true));
    }

    /**
     * @return string
     */
    public function getSelectedTabId()
    {
        return addslashes(htmlspecialchars($this->getRequest()->getParam('tab')));
    }
}