<?php
/**
 * EmailVision email template grid wrapper
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Templates extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'emvemt';
        $this->_controller = 'adminhtml_template';
        $this->_headerText = Mage::helper('emvemt')->__('SmartFocus Transactional Emails');
        $this->_updateButton('add', 'label', Mage::helper('emvemt')->__('Add New Transactional Email'));
    }
}