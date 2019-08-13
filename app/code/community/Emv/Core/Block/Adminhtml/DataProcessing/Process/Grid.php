<?php
/**
 * Data Processing Process Grid
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_DataProcessing_Process_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     *
     * Set main configuration of grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('dataProcessingGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Prepare collection for grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceSingleton('emvcore/dataProcessing_process_collection');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Define grid columns
     *
     * @return void
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => Mage::helper('newsletter')->__('ID'),
            'index'     => 'id',
            'width'     => '40',
        ));

        $this->addColumn('type', array(
            'header'    => Mage::helper('newsletter')->__('Type'),
            'index'     => 'type',
            'type'      => 'options',
            'options'   => array(
                Emv_Core_Model_DataProcessing_Process::TYPE_DATA_SYNC => Mage::helper('emvcore')->__('Data Sync')
            )
        ));

        $this->addColumn('title', array(
            'header'    => Mage::helper('emvcore')->__('Title'),
            'index'     => 'title'
        ));

        $this->addColumn('state', array(
            'header'    => Mage::helper('emvcore')->__('State'),
            'index'     => 'state',
            'width'     => '80px',
            'type'      => 'options',
            'options'   => array(
                Emv_Core_Model_DataProcessing_Process::STATE_NEW => Mage::helper('emvcore')->__('New'),
                Emv_Core_Model_DataProcessing_Process::STATE_PROCESSING => Mage::helper('emvcore')->__('Processing'),
                Emv_Core_Model_DataProcessing_Process::STATE_FAILED => Mage::helper('emvcore')->__('Failed'),
                Emv_Core_Model_DataProcessing_Process::STATE_SUCCESS => Mage::helper('emvcore')->__('Success'),
            )
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('emvcore')->__('Status (%)'),
            'index'     => 'status',
            'width'     => '40px',
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('reports')->__('Created At'),
            'type'      => 'datetime',
            'align'     => 'center',
            'index'     => 'created_at',
        ));
        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('reports')->__('Updated At'),
            'type'      => 'datetime',
            'align'     => 'center',
            'index'     => 'updated_at',
        ));
        $this->addColumn('terminated_at', array(
            'header'    => Mage::helper('emvcore')->__('Terminated At'),
            'type'      => 'datetime',
            'align'     => 'center',
            'index'     => 'terminated_at',
        ));

        $this->addColumn('output', array(
            'index'    => 'output_information',
            'filter'   => false,
            'sortable' => false,
            'renderer' => 'emvcore/adminhtml_dataProcessing_process_grid_column_renderer_output',
            'header'    => Mage::helper('emvcore')->__('Output'),
        ));

        $this->addColumn(
            'links',
            array(
                'header'   => Mage::helper('emvcore')->__('Action'),
                'type'     => 'text',
                'width'   => '100px',
                'filter'   => false,
                'sortable' => false,
                'renderer' => 'emvcore/adminhtml_dataProcessing_process_grid_column_renderer_links'
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Prepare mass action for grid
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Newsletter_Subscriber_Grid::_prepareMassaction()
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->addItem('remove_queue', array(
            'label'        => Mage::helper('newsletter')->__('Delete'),
            'url'          => $this->getUrl('*/*/massDelete'),
            'confirm' => $this->__('Are you sure?')
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
        if ($row->getData('state') == Emv_Core_Model_DataProcessing_Process::STATE_FAILED) {
            $class= "invalid";
        }
        if ($row->getData('state') == Emv_Core_Model_DataProcessing_Process::STATE_PROCESSING) {
            $class= "on-progress";
        }
        return $class;
    }
}