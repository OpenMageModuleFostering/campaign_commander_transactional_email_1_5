<?php
/**
 * EmailVision email template edit form
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('emv_template_tabs');

        // !!! set id where the content will be copied to
        $this->setDestElementId('emv_template_edit_form');
        $this->setTitle(Mage::helper('emvemt')->__('Template Information'));
    }

    /**
     * Add different tabs into the menu
     *
     * (non-PHPdoc)
     * @see Mage_Core_Block_Abstract::_prepareLayout()
     */
    protected function _prepareLayout()
    {
        $this->addTab('general', array(
            'label'     => Mage::helper('emvemt')->__('General'),
            'content'   => $this->getLayout()
                ->createBlock('emvemt/adminhtml_template_edit_tab_general')->toHtml(),
            'active'    => true
        ));

        $emvEmt = Mage::registry(Emv_Emt_Adminhtml_TemplateController::CURRENT_EMVEMT_TEMPLATE_REGISTRY);
        if ($emvEmt && $emvEmt->getId()) {
            $this->addTab('emv_dyn', array(
                'label'     => Mage::helper('catalog')->__('EMV DYN Attribute Mapping'),
                'content'   => $this->getLayout()
                    ->createBlock('emvemt/adminhtml_template_edit_tab_emvDyn')
                    ->toHtml(),
                'active'    => false
            ));

            $this->addTab('emv_content', array(
                'label'     => Mage::helper('catalog')->__('EMV CONTENT Attribute Mapping'),
                'content'   => $this->getLayout()
                    ->createBlock('emvemt/adminhtml_template_edit_tab_emvContent')->toHtml(),
                'active'    => false
            ));
        }

        return parent::_prepareLayout();
    }
}