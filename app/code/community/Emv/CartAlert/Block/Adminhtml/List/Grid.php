<?php
/**
 * Abandoned Cart Reminder List Grid Block
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Block_Adminhtml_List_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Stores current currency code
     */
    protected $_currentCurrencyCode = null;

    /**
     * Ids of current stores
     */
    protected $_storeIds            = array();

    /**
     * Constructor
     *
     * Set main configuration of grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('abandonedcartGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('main_table.updated_at ', 'desc');
    }

    /**
     * storeIds setter
     *
     * @param  array $storeIds
     * @return Mage_Adminhtml_Block_Report_Grid_Shopcart_Abstract
     */
    public function setStoreIds($storeIds)
    {
        $this->_storeIds = $storeIds;
        return $this;
    }

    /**
     * Retrieve currency code based on selected store
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        if (is_null($this->_currentCurrencyCode)) {
            reset($this->_storeIds);
            $this->_currentCurrencyCode = (count($this->_storeIds) > 0)
                ? Mage::app()->getStore(current($this->_storeIds))->getBaseCurrencyCode()
                : Mage::app()->getStore()->getBaseCurrencyCode();
        }
        return $this->_currentCurrencyCode;
    }

    /**
     * Prepare quote collection for grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceSingleton('sales/quote_collection');
        // prepare quote collection
        $collection
            ->addFieldToFilter('items_count', array('gt' => 0)) // have at least 1 item
            ->addFieldToFilter('customer_email', array('notnull' => 1)) // a quote with a valid email address
            ->addFieldToFilter('is_active', 1) // only get active quotes
            ->setOrder('main_table.updated_at', Varien_Data_Collection::SORT_ORDER_DESC) // sort the carts from older to newer
            ;

        // link to abandonment table to get sent reminder information
        $resource = Mage::getModel('core/resource');
        $collection->getSelect()->joinLeft(
                array('abandonment' => $resource->getTableName('abandonment/abandonment')),
                'main_table.entity_id = abandonment.entity_id',
                array(
                    'abandonment.customer_abandonment_subscribed' => 'customer_abandonment_subscribed',
                    'abandonment.coupon_code' => 'abandonment.coupon_code',
                    'abandonment.updated_at'  => 'abandonment.updated_at',
                    'abandonment.template'    => 'abandonment.template'
                )
            );

        // get subscription status to abandoned cart reminder notification
        $attr = Mage::getModel('customer/customer')->getAttribute('abandonment_subscribed');
        if ($attr->getAttributeId()) {
            $adapter = $collection->getConnection();
            $collection->getSelect()->joinLeft(
                array('abandonment_subscribed' => $attr->getBackend()->getTable()),
                $adapter->quoteInto(
                    'main_table.customer_id = abandonment_subscribed.entity_id'
                        . ' AND abandonment_subscribed.attribute_id = ?',
                    $attr->getAttributeId()
                ),
                array(
                    'abandonment.customer_abandonment_subscribed'
                        => 'IF(abandonment.customer_abandonment_subscribed IS NULL,
                            IF(abandonment_subscribed.value IS NULL, 1, abandonment_subscribed.value),
                            abandonment.customer_abandonment_subscribed)',
                )
            );
        }

        if (count($this->_storeIds)) {
            $collection->addFieldToFilter('store_id', array('in' => $this->_storeIds));
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareColumns()
     */
    protected function _prepareColumns()
    {
        $this->addColumn('customer_email', array(
            'header'    => Mage::helper('reports')->__('Email'),
            'index'     => 'customer_email',
            'sortable'  => false
        ));

        $this->addColumn('items_count', array(
            'header'    => Mage::helper('abandonment')->__('Number of Item Types'),
            'width'     => '80px',
            'align'     => 'right',
            'index'     => 'items_count',
            'sortable'  => false,
            'type'      => 'number'
        ));

        $this->addColumn('items_qty', array(
            'header'    => Mage::helper('abandonment')->__('Number of Items'),
            'width'     => '80px',
            'align'     => 'right',
            'index'     => 'items_qty',
            'sortable'  => false,
            'type'      => 'number'
        ));

        if ($this->getRequest()->getParam('website')) {
            $storeIds = Mage::app()->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
        } else if ($this->getRequest()->getParam('group')) {
            $storeIds = Mage::app()->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
        } else if ($this->getRequest()->getParam('store')) {
            $storeIds = array((int)$this->getRequest()->getParam('store'));
        } else {
            $storeIds = array();
        }
        $this->setStoreIds($storeIds);
        $currencyCode = $this->getCurrentCurrencyCode();

        $this->addColumn('subtotal', array(
            'header'        => Mage::helper('reports')->__('Subtotal'),
            'width'         => '80px',
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'subtotal',
            'sortable'      => false,
            'renderer'      => 'abandonment/adminhtml_list_grid_column_renderer_currency',
            'rate'          => $this->getRate($currencyCode),
        ));

        $this->addColumn('coupon_code', array(
            'header'       => Mage::helper('reports')->__('Applied Coupon'),
            'width'        => '80px',
            'index'        => 'coupon_code',
            'filter_index' => 'main_table.coupon_code',
            'sortable'     => false
        ));

        $this->addColumn('customer', array(
            'header'       => Mage::helper('abandonment')->__('Customer ID'),
            'width'        => '80px',
            'index'        => 'customer_id',
            'filter_index' => 'main_table.customer_id',
            'sortable'     => false
        ));

        $this->addColumn('created_at', array(
            'header'       => Mage::helper('reports')->__('Created On'),
            'width'        => '170px',
            'type'         => 'datetime',
            'index'        => 'created_at',
            'filter_index' => 'main_table.created_at',
            'sortable'     => false
        ));

        $this->addColumn('updated_at', array(
            'header'      => Mage::helper('reports')->__('Updated On'),
            'width'       => '170px',
            'type'        => 'datetime',
            'index'       => 'updated_at',
            'filter_index'=> 'main_table.updated_at',
            'sortable'    => false
        ));

        $this->addColumn('remote_ip', array(
            'header'    => Mage::helper('reports')->__('IP Address'),
            'width'     => '80px',
            'index'     => 'remote_ip',
            'sortable'  => false
        ));

        $this->addColumn('reminder_id', array(
            'header'    => Mage::helper('abandonment')->__('Sent Reminder'),
            'index'     => 'abandonment.template',
            'type'      => 'options',
            'sortable'  => false,
            'options'   => Mage::helper('abandonment')->getReminderLables()
        ));

        $this->addColumn('abandonment_updated_at', array(
            'header'      => Mage::helper('abandonment')->__('Reminder Last Update'),
            'type'        => 'datetime',
            'index'       => 'abandonment.updated_at',
            'sortable'    => false
        ));

        $this->addColumn('abandonment_subscribed', array(
            'header'      => Mage::helper('abandonment')->__('Subscribed to abandoned cart reminders'),
            'type'        => 'options',
            'index'       => 'abandonment.customer_abandonment_subscribed',
            'options'   => array(
                1  => Mage::helper('core')->__('Yes'),
                0  => Mage::helper('core')->__('No'),
            ),
            'filter'      => false,
            'sortable'    => false
        ));

        $this->addColumn('abandonment_coupon_code', array(
            'header'      => Mage::helper('abandonment')->__('Reminder Coupon'),
            'width'       => '80px',
            'index'       => 'abandonment.coupon_code',
            'sortable'    => false
        ));

        $this->addExportType('*/*/exportAbandonedCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportAbandonedExcel', Mage::helper('reports')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    /**
     * Allow to find any abandoned carts that haven't had any reminder
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_addColumnFilterToCollection()
     */
    protected function _addColumnFilterToCollection($column)
    {
        $field = ( $column->getFilterIndex() ) ? $column->getFilterIndex() : $column->getIndex();

        if ($field == 'abandonment.template'
            && $column->getFilter()
            && $column->getFilter()->getValue() == Emv_CartAlert_Constants::NONE_FLAG
        ) {
            $this->getCollection()->addFieldToFilter(
                array('abandonment.template', 'abandonment.template'),
                array(array('null' => 0), array('eq' => ""))
            );
            return $this;
        }

        parent::_addColumnFilterToCollection($column);
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareMassaction()
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('quotes');

        $this->getMassactionBlock()->addItem('first_reminder', array(
             'label'    => Mage::helper('abandonment')->__('Test First Reminder'),
             'url'      => $this->getUrl('*/*/testReminder' , array('template' => Emv_CartAlert_Constants::FIRST_ALERT_FLAG)),
             'confirm'  => Mage::helper('customer')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('second_reminder', array(
             'label'    => Mage::helper('abandonment')->__('Test Second Reminder'),
             'url'      => $this->getUrl('*/*/testReminder', array('template' => Emv_CartAlert_Constants::SECOND_ALERT_FLAG)),
             'confirm'  => Mage::helper('customer')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('third_reminder', array(
             'label'    => Mage::helper('abandonment')->__('Test Third Reminder'),
             'url'      => $this->getUrl('*/*/testReminder', array('template' => Emv_CartAlert_Constants::THIRD_ALERT_FLAG)),
             'confirm'  => Mage::helper('customer')->__('Are you sure?')
        ));
        return $this;
    }

    /**
     * Get row class - determine the row class according to their subscription information to reminder notification
     *
     * @param Varien_Object $emvEmt
     * @return string
     */
    public function getRowClass(Varien_Object $row)
    {
        $class = "";
        if ($row->getData('abandonment.customer_abandonment_subscribed') == 0) {
            $class= "invalid";
        }
        return $class;
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::getRowUrl()
     */
    public function getRowUrl($row)
    {
        if ($row->getCustomerId()) {
            return $this->getUrl('adminhtml/customer/edit', array('id' => $row->getCustomerId(), 'active_tab'=>'cart'));
        } else {
            return $this->getUrl('*/*/displayQuote', array('quote' => $row->getId()));
        }

        return '#';
    }
}