<?php
/**
 * Abandoned Cart Detail Block
 *
 * @category    Emv
 * @package     Emv_Report
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Report_Block_Adminhtml_Details_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     */
    protected function _prepareCollection()
    {
        $alertId = Mage::registry('abandonmentalertid');
        $from = Mage::registry('from');
        $to = Mage::registry('to');

        $collection = Mage::getModel('abandonment/orderflag')->getCollection();
        $collection->filterByReminderFlag(Mage::helper('abandonment')->getReminderFlagFromId($alertId));
        $collection->joinConvertedCartDetails($from, $to);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareColumns()
     */
    protected function _prepareColumns()
    {
        $this->addColumn('email', array(
            'header' => Mage::helper('abandonment_report')->__('Email'),
            'index'  => 'customer_email',
            'type'   => 'varchar',
        ));

        $this->addColumn('firstname', array(
            'header' => Mage::helper('abandonment_report')->__('First Name'),
            'index'  => 'customer_firstname',
            'type'   => 'varchar',
        ));

        $this->addColumn('lastname', array(
            'header' => Mage::helper('abandonment_report')->__('Last Name'),
            'index'  => 'customer_lastname',
            'type'   => 'varchar',
        ));

        $this->addColumn('date', array(
            'header' => Mage::helper('abandonment_report')->__('Purchase On'),
            'index'  => 'created_at',
            'type'   => 'date',
            'filter' => false
        ));

        $this->addColumn('incrementid', array(
            'header' => Mage::helper('abandonment_report')->__('Order #'),
            'index'  => 'increment_id',
            'type'   => 'varchar',
        ));

        $this->addColumn('quantity', array(
            'header' => Mage::helper('abandonment_report')->__('Ordered Quantity'),
            'index'  => 'total_qty_ordered',
            'type'   => 'number',
            'filter' => false
        ));

        $this->addColumn('coupon_code', array(
            'header' => Mage::helper('abandonment_report')->__('Coupon Code'),
            'index'  => 'coupon_code',
            'type'   => 'varchar',
            'filter' => false
        ));

        $this->addColumn('itemtotal', array(
            'header' => Mage::helper('abandonment_report')->__('Subtotal'),
            'index'  => 'subtotal',
            'type'   => 'price',
            'filter' => false
        ));

        $this->addColumn('shipping_amount', array(
            'header' => Mage::helper('abandonment_report')->__('Shipping'),
            'index'  => 'shipping_amount',
            'type'   => 'price',
            'filter' => false
        ));

        $this->addColumn('discount_amount', array(
            'header' => Mage::helper('abandonment_report')->__('Discount'),
            'index'  => 'discount_amount',
            'type'   => 'price',
            'filter' => false
        ));

        $this->addColumn('ordertotal', array(
            'header' => Mage::helper('abandonment_report')->__('Order Total'),
            'index'  => 'grand_total',
            'type'   => 'price',
            'filter' => false
        ));

        // prepare paramters for csv export detail
        $param = array();
        $param['alertId'] = Mage::registry('abandonmentalertid');
        $param['_current'] = true;
        $from = Mage::registry('from');
        $to = Mage::registry('to');

        if($from !== null && $to !== null)
        {
            $param['from'] = $from;
            $param['to'] = $to;
        }

        $this->_exportTypes[] = new Varien_Object(
            array(
                'url'   => $this->getUrl('*/*/exportDetailsCsv', $param),
                'label' => Mage::helper('adminhtml')->__('Export CSV')
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Get row url (sale order view)
     *
     * @see Mage_Adminhtml_Block_Widget_Grid::getRowUrl()
     * @return string
     */
    public function getRowUrl($row)
    {
         return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getEntityId()));
    }
}