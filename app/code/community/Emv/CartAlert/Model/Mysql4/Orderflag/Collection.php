<?php
/**
 * Converted Cart Collection Model
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Mysql4_Orderflag_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Db_Collection_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('abandonment/orderflag');
    }

    /**
     * Add additional information
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return Emv_CartAlert_Model_Mysql4_Orderflag_Collection
     */
    public function joinConvertedCartDetails($from = null, $to = null)
    {
        $this->getSelect()->join(
                array('order' => $this->getTable('sales/order')),
                'main_table.entity_id = order.entity_id',
                array(
                    'created_at',
                    'customer_email',
                    'customer_firstname',
                    'customer_lastname',
                    'increment_id',

                    'subtotal',
                    'discount_amount',
                    'shipping_amount',
                    'grand_total',
                    'coupon_code',
                    'total_qty_ordered'
                )
            )
        ;

        if ($from !== null) {
            $this->getSelect()->where('main_table.flag_date >= ?', $from);
        }
        if ($to !== null) {
            $this->getSelect()->where('main_table.flag_date <= ?', $to);
        }

        return $this;
    }

    /**
     * Get all order flaged by a reminder flag
     *
     * @param string $reminderFlag
     * @return Emv_CartAlert_Model_Mysql4_Orderflag_Collection
     */
    public function filterByReminderFlag($reminderFlag)
    {
        $this->addFieldToFilter('flag', $reminderFlag);

        return $this;
    }
}

