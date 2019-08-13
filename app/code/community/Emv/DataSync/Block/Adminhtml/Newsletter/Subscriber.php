<?php
/**
 * Subscriber Queue grid container
 *
 * @category   Emv
 * @package    Emv_DataSync
 * @author     Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright  Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Block_Adminhtml_Newsletter_Subscriber extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Construct Newsletter Subscriber Queue menu
     * @see Mage_Adminhtml_Block_Widget_Grid_Container::__construct()
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'emvdatasync';
        $this->_controller = 'adminhtml_newsletter_subscriber';
        $this->_headerText = Mage::helper('emvdatasync')->__('Newsletter Subscriber Queue');
        $this->_removeButton('add');
    }
}