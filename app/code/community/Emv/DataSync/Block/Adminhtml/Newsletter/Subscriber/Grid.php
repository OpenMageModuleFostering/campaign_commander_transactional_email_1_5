<?php
/**
 * Block to manage the subscriber queue
 *
 * @category   Emv
 * @package    Emv_DataSync
 * @author     Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright  Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Block_Adminhtml_Newsletter_Subscriber_Grid extends Mage_Adminhtml_Block_Newsletter_Subscriber_Grid
{
    /**
     * Constructor
     *
     * Set main configuration of grid
     */
    public function __construct()
    {
        Mage_Adminhtml_Block_Widget_Grid::__construct();
        $this->setId('subscriberGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('queued', 'desc');
    }

    /**
     * Prepare collection for grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceSingleton('newsletter/subscriber_collection');
        /* @var $collection Mage_Newsletter_Model_Mysql4_Subscriber_Collection */
        $collection
            ->showCustomerInfo(true)
            ->addSubscriberTypeField()
            ->showStoreInfo();

        if (Mage::getSingleton('emvdatasync/service_dataProcess')->enabledPurchaseInformation()) {
            $expression = sprintf("IF(%s, %s, %s)", 'purchase.updated_at > main_table.date_last_purchase', 1, 0);
            $checkSql = new Zend_Db_Expr($expression);

            $tableName = Mage::getSingleton('core/resource')->getTableName('emvdatasync/purchase_info');

            $collection->getSelect()
                ->joinLeft(
                    array('purchase' => $tableName),
                    'purchase.customer_id = main_table.customer_id',
                    array('purchase_validity' => $checkSql)
                )
            ;
            $collection->addFilterToMap('purchase_validity', $checkSql);
        }

        $this->setCollection($collection);

        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
        return $this;
    }

    /**
     * Prepare columns for grid
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Newsletter_Subscriber_Grid::_prepareColumns()
     */
    protected function _prepareColumns()
    {
        $this->addColumn('subscriber_id', array(
            'header'    => Mage::helper('newsletter')->__('ID'),
            'index'     => 'subscriber_id',
            'width'     => '10px',
            'type'  => 'number'
        ));

        $this->addColumn('queued', array(
            'header'    => Mage::helper('emvdatasync')->__('Scheduled'),
            'width'     => '10',
            'index'     => 'queued',
            'default'   => Mage::helper('core')->__('No'),
            'type'    => 'options',
            'options'   => array(
                1  => Mage::helper('core')->__('Yes'),
                0  => Mage::helper('core')->__('No'),
            ),
        ));

        $this->addColumn('email', array(
            'header'    => Mage::helper('newsletter')->__('Email'),
            'index'     => 'subscriber_email',
            'width'     => '30',
        ));

        $this->addColumn('type', array(
            'header'    => Mage::helper('newsletter')->__('Type'),
            'index'     => 'type',
            'type'      => 'options',
            'options'   => array(
                1  => Mage::helper('newsletter')->__('Guest'),
                2  => Mage::helper('newsletter')->__('Customer')
            ),
            'width'     => '30',
        ));

        $this->addColumn('firstname', array(
            'header'    => Mage::helper('newsletter')->__('Customer First Name'),
            'index'     => 'customer_firstname',
            'default'   =>    '----'
        ));

        $this->addColumn('lastname', array(
            'header'    => Mage::helper('newsletter')->__('Customer Last Name'),
            'index'     => 'customer_lastname',
            'default'   =>    '----'
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('newsletter')->__('Status'),
            'index'     => 'subscriber_status',
            'type'      => 'options',
            'options'   => array(
                Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE   => Mage::helper('newsletter')->__('Not Activated'),
                Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED   => Mage::helper('newsletter')->__('Subscribed'),
                Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED => Mage::helper('newsletter')->__('Unsubscribed'),
                Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED => Mage::helper('newsletter')->__('Unconfirmed'),
            )
        ));

        $this->addColumn('member_last_update_date', array(
            'header'    => Mage::helper('emvdatasync')->__('Last Sync'),
            'type'      => 'datetime',
            'width'     => '120',
            'align'     => 'center',
            'index'     => Emv_DataSync_Helper_Service::FIELD_MEMBER_LAST_UPDATE,
        ));
        $this->addColumn('data_last_update_date', array(
            'header'    => Mage::helper('emvdatasync')->__('Last Data Update'),
            'type'      => 'datetime',
            'width'     => '120',
            'align'     => 'center',
            'index'     => Emv_DataSync_Helper_Service::FIELD_DATA_LAST_UPDATE,
        ));

        $this->addColumn('date_unjoin', array(
            'header'    => Mage::helper('emvdatasync')->__('Last Unsubscription'),
            'type'      => 'datetime',
            'width'     => '120',
            'align'     => 'center',
            'index'     => Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN,
        ));

        $this->addColumn('store', array(
            'header'    => Mage::helper('newsletter')->__('Store View'),
            'index'     => 'store_id',
            'type'      => 'options',
            'options'   => $this->_getStoreOptions()
        ));
        $this->addColumn('group', array(
            'header'    => Mage::helper('newsletter')->__('Store'),
            'index'     => 'group_id',
            'type'      => 'options',
            'options'   => $this->_getStoreGroupOptions()
        ));

        $this->addColumn('website', array(
            'header'    => Mage::helper('newsletter')->__('Website'),
            'index'     => 'website_id',
            'type'      => 'options',
            'options'   => $this->_getWebsiteOptions()
        ));

        if (Mage::getSingleton('emvdatasync/service_dataProcess')->enabledPurchaseInformation()) {
            $this->addColumn(
                'links',
                array(
                    'header'   => Mage::helper('emvcore')->__('Purchase History'),
                    'width'    => '100px',
                    'type'     => 'options',
                    'sortable' => false,
                    'index'    => 'purchase_validity',
                    'options'  => array(
                        1 => Mage::helper('emvdatasync')->__('Yes'),
                        0 => Mage::helper('emvdatasync')->__('No')
                    )
                )
            );
        }

        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('Excel XML'));
        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }

    /**
     * Prepare mass action for grid
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Newsletter_Subscriber_Grid::_prepareMassaction()
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('subscriber_id');
        $this->getMassactionBlock()->setFormFieldName('subscriber');

        $this->getMassactionBlock()->addItem('queue', array(
             'label'        => Mage::helper('emvdatasync')->__('Schedule'),
            'confirm'       => $this->__('Are you sure?'),
             'url'          => $this->getUrl(
                 '*/*/massQueue',
                 array(
                     'scheduled' => Emv_DataSync_Helper_Service::SCHEDULED_VALUE)
                 )
             )
        );

        $this->getMassactionBlock()->addItem('remove_queue', array(
             'label'        => Mage::helper('newsletter')->__('Stop'),
            'confirm'       => $this->__('Are you sure?'),
             'url'          => $this->getUrl(
                 '*/*/massQueue',
                 array(
                     'scheduled' => Emv_DataSync_Helper_Service::NOT_SCHEDULED_VALUE)
                 )
             )
        );
        $this->getMassactionBlock()->addItem('dry_test',
            array(
                'label'        => Mage::helper('emvdatasync')->__('Get Csv File(s)'),
                'url'          => $this->getUrl('*/*/massDryTest')
            )
        );
        $this->getMassactionBlock()->addItem('send_test',
            array(
                'label'        => Mage::helper('emvdatasync')->__('Manual Sync'),
                'confirm'       => $this->__('Are you sure?'),
                'url'          => $this->getUrl('*/*/sendTest')
            )
        );
        return $this;
    }

    /**
     * Get row class - determine the row class according to state information
     *
     * @param Varien_Object $emvEmt
     * @return string
     */
    public function getRowClass(Varien_Object $row)
    {
        $class = "";
        if ($row->getData('purchase_validity') == 1) {
            $class= "on-progress";
        }
        return $class;
    }
}
