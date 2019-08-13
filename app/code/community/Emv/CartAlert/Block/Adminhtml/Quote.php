<?php
/**
 * Quote grid container block
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Block_Adminhtml_Quote extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Construct account menu
     * @see Mage_Adminhtml_Block_Widget_Grid_Container::__construct()
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'abandonment';
        $this->_controller = 'adminhtml_quote';

        $quote = Mage::registry('smartfocus_quote');
        $this->_headerText = Mage::helper('abandonment')->__(
            'Abandoned Cart (customer email : %s - on store %s)',
            $quote->getCustomerEmail(),
            Mage::app()->getStore($quote->getStoreId())->getName()
        );

        $this->_removeButton('add');
        $this->_addBackButton();
    }

    /**
     * Link back to abandoned cart list page
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/list');
    }
}