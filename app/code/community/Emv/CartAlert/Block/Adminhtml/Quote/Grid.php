<?php
/**
 * Quote Product Information Grid Block
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Block_Adminhtml_Quote_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Prepare grid
     *
     * @return void
     */
    protected function _prepareGrid()
    {
        $this->setId('smartfocus_cart_grid');
        $quote = Mage::registry('smartfocus_quote');

        if ($quote) {
            $this->setStoreId($quote->getStoreId());
        }
        parent::_prepareGrid();
    }

    /**
     * Prepare collection
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     */
    protected function _prepareCollection()
    {
        $quote = Mage::registry('smartfocus_quote');
        if ($quote) {
            $collection = $quote->getItemsCollection(false);
        } else {
            $collection = new Varien_Data_Collection();
        }

        $collection->addFieldToFilter('parent_item_id', array('null' => true));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::_prepareColumns()
     */
    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => Mage::helper('catalog')->__('Product ID'),
            'index'     => 'product_id',
            'width'     => '100px',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Product Name'),
            'index'     => 'name',
            'renderer'  => 'adminhtml/customer_edit_tab_view_grid_renderer_item'
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('reports')->__('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => '150px',
        ));
        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('reports')->__('Updated At'),
            'index'     => 'updated_at',
            'type'      => 'datetime',
            'width'     => '150px',
        ));

        $this->addColumn('sku', array(
            'header'    => Mage::helper('catalog')->__('SKU'),
            'index'     => 'sku',
            'width'     => '100px',
        ));

        $this->addColumn('qty', array(
            'header'    => Mage::helper('catalog')->__('Qty'),
            'index'     => 'qty',
            'type'      => 'number',
            'width'     => '60px',
        ));

        $this->addColumn('price', array(
            'header'        => Mage::helper('catalog')->__('Price'),
            'index'         => 'price',
            'type'          => 'currency',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE,
                $this->getStoreId()),
        ));

        $this->addColumn('total', array(
            'header'        => Mage::helper('sales')->__('Total'),
            'index'         => 'row_total',
            'type'          => 'currency',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE,
                $this->getStoreId()),
        ));

        return parent::_prepareColumns();
    }

    /**
     * The link to product page
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Grid::getRowUrl()
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/catalog_product/edit', array('id' => $row->getProductId()));
    }
}