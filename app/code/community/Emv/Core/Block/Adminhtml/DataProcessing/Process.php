<?php
/**
 * Data Processing Process Grid Container
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_DataProcessing_Process extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Construct data process menu
     * @see Mage_Adminhtml_Block_Widget_Grid_Container::__construct()
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'emvcore';
        $this->_controller = 'adminhtml_dataProcessing_process';
        $this->_headerText = Mage::helper('emvcore')->__('SmartFocus Data Process List');
        $this->_removeButton('add');

    }
}