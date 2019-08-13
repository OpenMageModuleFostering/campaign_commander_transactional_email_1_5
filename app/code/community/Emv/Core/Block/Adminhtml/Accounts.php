<?php
/**
 * This block is used to manage account grid list
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_Accounts extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Construct account menu
     * @see Mage_Adminhtml_Block_Widget_Grid_Container::__construct()
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'emvcore';
        $this->_controller = 'adminhtml_account';
        $this->_headerText = Mage::helper('emvcore')->__('SmartFocus Accounts');
        $this->_updateButton('add', 'label', Mage::helper('emvcore')->__('Add New Account'));
    }

}