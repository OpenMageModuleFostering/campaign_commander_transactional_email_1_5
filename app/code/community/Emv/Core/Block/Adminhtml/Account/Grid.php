<?php
/**
 * This block is used to manage account grid list
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_Account_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Prepare SmartFocus Account Collection
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('emvcore/account')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareColumns()
     */
    protected function _prepareColumns()
    {
        // Account id
        $this->addColumn('id', array(
            'header' => Mage::helper('emvcore')->__('Account ID'),
            'index'  => 'id',
            'type'   => 'number',
        ));

        // Created at
        $this->addColumn('created_at', array(
                'header'    => Mage::helper('sales')->__('Created At'),
                'type'      => 'datetime',
                'width'     =>  '150',
                'align'     => 'center',
                'index'     => 'created_at',
                'gmtoffset' => true
        ));

        // Update at
        $this->addColumn('updated_at', array(
                'header'    => Mage::helper('sales')->__('Updated At'),
                'type'      => 'datetime',
                'width'     =>  '150',
                'align'     => 'center',
                'index'     => 'updated_at',
                'gmtoffset' => true
        ));

        // Name
        $this->addColumn('name', array(
            'header' => Mage::helper('emvcore')->__('SmartFocus Account'),
            'index'  => 'name',
            'type'   => 'varchar',
        ));

        // Row Action
        $this->addColumn('action', array(
            'header'   => Mage::helper('emvcore')->__('Action'),
            'type'     => 'action',
            'width'    => '100',
            'getter'   => 'getId',
            'actions'  => array(
                    array(
                        'caption'   => Mage::helper('emvcore')->__('Edit'),
                        'title'   => Mage::helper('emvcore')->__('Edit Account'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id',
                    )
                ),
            'filter'    => false,
            'sortable'  => false,
            'is_system' => true,
        ));

        return parent::_prepareColumns();
    }

    /**
     * Get row url
     *
     * @param var $row
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}