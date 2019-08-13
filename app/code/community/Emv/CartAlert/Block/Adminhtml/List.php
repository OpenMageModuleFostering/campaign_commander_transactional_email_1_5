<?php
/**
 * Abandoned Cart Reminder List Grid Container
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Block_Adminhtml_List extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Build abandonment list
     * @see Mage_Adminhtml_Block_Widget_Grid_Container::__construct()
     */
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'abandonment';
        $this->_controller = 'adminhtml_list';
        $this->_headerText = Mage::helper('abandonment')->__('Abandoned Cart Reminders');
        $this->_removeButton('add');
    }

    /**
     * Add Store switcher to layout
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid_Container::_prepareLayout()
     */
    protected function _prepareLayout()
    {
        $this->setChild('store_switcher',
            $this->getLayout()->createBlock('adminhtml/store_switcher')
                ->setUseConfirm(false)
                ->setSwitchUrl($this->getUrl('*/*/*', array('store'=>null)))
                ->setTemplate('report/store/switcher.phtml')
        );

        return parent::_prepareLayout();
    }

    /**
     * Get store switcher for multiple stores
     *
     * @return string
     */
    public function getStoreSwitcherHtml()
    {
        if (Mage::app()->isSingleStoreMode()) {
            return '';
        }
        return $this->getChildHtml('store_switcher');
    }

    /**
     * Get grid html with eventually store switcher block
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid_Container::getGridHtml()
     */
    public function getGridHtml()
    {
        return $this->getStoreSwitcherHtml() . parent::getGridHtml();
    }
}