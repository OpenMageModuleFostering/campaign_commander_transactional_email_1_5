<?php
/**
 * Purchase Information Attribute handler
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_AttributeProcessing_Handler_PurchaseInformation
    extends Emv_DataSync_Model_AttributeProcessing_Handler_Newsletter
{
    const PREFIX_PURCHASE_FIELD  = 'purchase';

    /**
     * Table name
     *
     * @var string
     */
    protected $_tableName          = 'emvdatasync/purchase_info';

    /**
     * Prohibited fields (in the attribute mapping list)
     * @var array
     */
    protected $_prohibitedFields   = array('id', 'customer_id');

    /**
     * Field Labels
     *
     * @var array
     */
    protected $_fieldLabels = array(
            'email'                      => 'Used Email Addresses',
            'order_list'                 => 'List of Order #' ,

            'created_at'                 => 'Created At',
            'updated_at'                 => 'Updated At' ,

            'base_currency_code'         => 'Base Currency Code',

            'total_order'                => 'Total Orders Purchased',

            'total_ordered_item_qty'     => 'Total Items Purchased',
            'avg_item_qty'               => 'Average Items Purchased per Order',
            'order_amount_total'         => 'Total Order Amount Spent',
            'avg_order_amount_total'     => 'Average Order Amount Spent per Purchase',
            'discount_amount_total'      => 'Total Discount Amount',
            'avg_discount_amount_total'  => 'Average Discount Amount per Purchase',
            'shipping_amount_total'      => 'Total Shipping Amount',
            'avg_shipping_amount_total'  => 'Average Shipping Amount per Purchase',

            'min_order_amount_total'     => 'Minimum Purchase Total',
            'max_order_amount_total'     => 'Maximum Purchase Total',
            'first_order_date'           => 'First Purchase',
            'last_order_date'            => 'Last Purchase',
            'min_total_ordered_item_qty' => 'Minimum Items Purchased',
            'max_total_ordered_item_qty' => 'Maximum Items Purchased',

            'shipping_list'              => 'Shipping Methods',
            'payment_methods'            => 'Payment Methods',
            'coupon_list'                => 'Used Coupons',
            'nb_order_having_discount'   => 'Total Purchases With Discount'
    );

    /**
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::toOptionArray()
     */
    public function toOptionArray()
    {
        if (is_null($this->_optionCustomerAttributes)) {
            $this->_optionCustomerAttributes = array();

            if ($this->getTableDefinition() && is_array($this->getTableDefinition())) {
                foreach ($this->getTableDefinition() as $columnName => $columnDefintion) {
                    if (!in_array($columnName, $this->_prohibitedFields)) {
                        if (!isset($this->_optionCustomerAttributes['purchase_information'])) {
                            $this->_optionCustomerAttributes['purchase_information'] = array(
                                'value' => array(),
                                'label' => Mage::helper('emvdatasync')->__('Purchase Information')
                            );
                        }
                        $finalAttributeId = Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::buildAttributeName(
                                self::PREFIX_PURCHASE_FIELD,
                                $columnName
                            );

                        $label = $columnName;
                        if (isset($this->_fieldLabels[$label])) {
                            $label = $this->_fieldLabels[$label];
                        }
                        $this->_optionCustomerAttributes['purchase_information']['value'][$finalAttributeId] = 'Purchase - ' . $label;
                    }
                }
            }

        }

        return $this->_optionCustomerAttributes;
    }

    /**
     * (non-PHPdoc)
     * @see Emv_DataSync_Model_AttributeProcessing_Handler_Abstract::canHandle()
     */
    public function canHandle($attributeData, $storeId)
    {
        $ok = false;
        if (Mage::getSingleton('emvdatasync/service_dataProcess')->enabledPurchaseInformation()) {
            if (isset($attributeData['prefix'])) {
                $allowedPrefix = array(self::PREFIX_PURCHASE_FIELD);
                if (in_array($attributeData['prefix'], $allowedPrefix)) {
                    $ok = true;
                }
            }
        }

        return $ok;
    }

    /**
     * Prepare subscriber collection according to mapped attribute list
     * Generic method needs to be implemented in Child class.
     *
     * @param Varien_Data_Collection_Db $collection
     * @param array $attributeList
     * @param string $storeId
     * @return Varien_Data_Collection_Db
     */
    public function prepareSubscriberCollection(
        Varien_Data_Collection_Db $collection,
        array $attributeList,
        $storeId = null
    )
    {
        $tableName = Mage::getSingleton('core/resource')->getTableName($this->_tableName);

        $collection->getSelect()
            ->joinLeft(
                array('purchase' => $tableName),
                'purchase.customer_id = main_table.customer_id'
            )
            ->where('
                main_table.date_last_purchase IS NULL
                OR (
                    purchase.updated_at > main_table.date_last_purchase
                )
            ')
        ;

        return $collection;
    }
}