<?php
/**
 * Email sending log grid
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Resending_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Prepare collection for grid
     *
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('emvemt/resending_queue_message_collection');
        $this->setCollection($collection);

        parent::_prepareCollection();
    }

    /**
     * Prepare column for resending email grid
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

        $this->addColumn('email',
            array(
                'header'=> Mage::helper('emvemt')->__('Email'),
                'index' => 'email',
                'type'  => 'varchar',
            )
        );
        // Created at
        $this->addColumn('created_at', array(
            'header'    => Mage::helper('sales')->__('Created At'),
            'type'      => 'datetime',
            'width'     =>  '100',
            'align'     => 'center',
            'index'     => 'created_at',
            'gmtoffset' => true
        ));

        $this->addColumn('number_attempts',
            array(
                'header'=> Mage::helper('emvemt')->__('Number of Attempts'),
                'index' => 'number_attempts',
                'type'  => 'varchar',
            )
        );
        // First attempt
        $this->addColumn('first_attempt', array(
            'header'    => Mage::helper('customer')->__('First Attempt'),
            'type'      => 'datetime',
            'width'     =>  '100',
            'align'     => 'center',
            'index'     => 'first_attempt',
            'gmtoffset' => true
        ));
        // Last attempt
        $this->addColumn('last_attempt', array(
            'header'    => Mage::helper('customer')->__('Last Attempt'),
            'type'      => 'datetime',
            'width'     =>  '100',
            'align'     => 'center',
            'index'     => 'last_attempt',
            'gmtoffset' => true
        ));

        // Original Magento Template Name
        $this->addColumn('original_magento_template_name',
            array(
                'header'=> Mage::helper('emvemt')->__('Original Magento Template'),
                'index' => 'original_magento_template_name',
                'type'  => 'varchar',
            )
        );

        // Magento Template name
        $this->addColumn('magento_template_name',
            array(
                'header'=> Mage::helper('emvemt')->__('Magento Template'),
                'index' => 'magento_template_name',
                'type'  => 'varchar',
            )
        );

        // Account name
        $this->addColumn('account_id',
            array(
                'header'=> Mage::helper('emvemt')->__('Account Name'),
                'index' => 'account_id',
                'type'  => 'options',
                'options' => Mage::getResourceModel('emvcore/account_collection')->toOptionHash()
            )
        );

        // EmailVision Template name
        $this->addColumn('emv_name',
            array(
                'header'=> Mage::helper('emvemt')->__('SmartFocus Template'),
                'index' => 'emv_name',
                'type'  => 'varchar',
            )
        );

        $this->addColumn('sent_sucess',
            array(
                'header'    => Mage::helper('emvemt')->__('Success'),
                'align'     => 'center',
                'width'     => 1,
                'index'     => 'sent_sucess',
                'type'      => 'options',
                'options'   => array(
                    1  => Mage::helper('core')->__('Yes'),
                    0  => Mage::helper('core')->__('No'),
                ),
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
        $this->getMassactionBlock()->setFormFieldName('resending_message');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('customer')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('customer')->__('Are you sure?')
        ));

        return $this;
    }
}