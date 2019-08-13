<?php
/**
 * EmailVision email template grid
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Prepare collection for grid
     *
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('emvemt/emt_collection');
        $collection->prepareQueryToGetMagentoName();
        $collection->prepareQueryToGetAccountName();
        $collection->unselectEmvSendingTemplate();
        $this->setCollection($collection);

        parent::_prepareCollection();
    }

    /**
     * Prepare column for campaign commander templates grid
     *
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareColumns()
     */
    protected function _prepareColumns()
    {
        // id
        $this->addColumn('id', array(
            'header' => Mage::helper('emvemt')->__('ID'),
            'index'  => 'id',
            'type'   => 'number',
        ));

        // Magento email template code
        $this->addColumn('template_code', array(
            'header' => Mage::helper('emvemt')->__('Magento Template Name'),
            'index'  => 'template_code',
            'type'   => 'varchar',
            'renderer'  => 'emvemt/adminhtml_template_grid_renderer_mageTemplate',
        ));

        // Created at
        $this->addColumn('created_at', array(
            'header'    => Mage::helper('sales')->__('Created At'),
            'type'      => 'datetime',
            'width'     =>  '110',
            'align'     => 'center',
            'index'     => 'created_at',
            'gmtoffset' => true
        ));

        // Update at
        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('sales')->__('Updated At'),
            'type'      => 'datetime',
            'width'     =>  '110',
            'align'     => 'center',
            'index'     => 'updated_at',
            'gmtoffset' => true
        ));

        // Created at
        $this->addColumn('account_name', array(
            'header'    => Mage::helper('emvemt')->__('SmartFocus Account'),
            'type'      => 'varchar',
            'align'     => 'center',
            'index'     => 'account_name',
        ));

        //list of all avaiable modes
        $this->addColumn('emv_send_mail_mode_id',
            array(
                'header'=> Mage::helper('emvemt')->__('Sending Mode'),
                'width' => '100px',
                'index' => 'emv_send_mail_mode_id',
                'type'  => 'options',
                'options' => Emv_Emt_Model_Mailmode::getMailModesAndLabels(),
        ));

        // SmartFocus Template Id And Name
        $this->addColumn('emv_template_id_and_name', array(
            'header' => Mage::helper('emvemt')->__('SmartFocus Template Id And Name'),
            'index'  => 'emv_template_id_and_name',
            'type'   => 'varchar',
            'filter'    => false,
            'renderer'  => 'emvemt/adminhtml_template_grid_renderer_emvname',
        ));

        // Action column
        $this->addColumn('action', array(
            'header'   => Mage::helper('emvemt')->__('Action'),
            'type'     => 'action',
            'width'    => '100',
            'getter'   => 'getId',
            'actions'  => array(
                array(
                    'caption'   => Mage::helper('emvemt')->__('Edit'),
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
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareMassaction()
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('emt');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('customer')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('customer')->__('Are you sure?')
        ));

        return $this;
    }

    /**
     * Get row url
     *
     * @see Mage_Adminhtml_Block_Widget_Grid::getRowUrl()
     * @return string
     */
    public function getRowUrl($row)
    {
         return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    /**
     * Get row class - determine the row class according to invalidity information
     *
     * @param Varien_Object $emvEmt
     * @return string
     */
    public function getRowClass(Varien_Object $emvEmt)
    {
        $class = "";
        // if row does not have a valid magento template and account name -> set invalid css class
        if (!$emvEmt->getData('template_code') || !$emvEmt->getData('account_name')) {
            $class= "invalid";
        }
        return $class;
    }
}