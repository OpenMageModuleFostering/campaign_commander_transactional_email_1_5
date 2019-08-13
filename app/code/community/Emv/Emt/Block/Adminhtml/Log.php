<?php
/**
 * Email Sending Log grid wrapper
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'emvemt';
        $this->_controller = 'adminhtml_log';
        $this->_headerText = Mage::helper('emvemt')->__('Email Sending Logs');
        $this->removeButton('add');
    }
}