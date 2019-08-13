<?php
/**
 * This block is used to manage account edit view
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_Account_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Prepare edit view
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'emvcore';
        $this->_controller = 'adminhtml_account';
        $this->_headerText = $this->getHeaderText();

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save and Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('current_emvaccount')->getId()) {
            return Mage::helper('emvcore')->__("Edit SmartFocus Account '%s'", $this->htmlEscape(Mage::registry('current_emvaccount')->getName()));
        } else {
            return Mage::helper('emvcore')->__('New SmartFocus Account');
        }
    }
}