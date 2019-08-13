<?php
/**
 * Order Flag Resource Model - contains the reminder information for a given order
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Mysql4_Orderflag extends Mage_Core_Model_Mysql4_Abstract {

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('abandonment/order_flag', 'id');
    }

    /**
     * @param string $orderId
     * @return array
     */
    public function loadByOrderId($orderId)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
                    ->from($this->getMainTable())
                    ->where('entity_id=:entity_id');

        $binds = array(
            'entity_id' => $orderId
        );

        $result = $adapter->fetchRow($select, $binds);

        return count($result) ? $result : Array();
    }

    /**
     * Get the number of converted carts for a given reminder (1 - first_alert, 2 - second_alert, 3 - third alert)
     *
     * @param string $reminderId
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int | boolean - false
     */
    public function getNbConvertedCarts($reminderId, $from = null, $to = null)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), "COUNT(*)")
            ->where("flag = ?", Mage::helper('abandonment')->getReminderFlagFromId($reminderId));

        if ($from !== null) {
            $select->where('flag_date >= ?', $from);
        }
        if ($to !== null) {
            $select->where('flag_date <= ?', $to);
        }

        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Get the number of converted carts for the first reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int | boolean - false
     */
    public function getNbFirstReminderConvertedCarts($from = null, $to = null)
    {
        return $this->getNbConvertedCarts(Emv_CartAlert_Constants::FIRST_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get the number of converted carts for the second reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int | boolean - false
     */
    public function getNbSecondReminderConvertedCarts($from = null, $to = null)
    {
        return $this->getNbConvertedCarts(Emv_CartAlert_Constants::SECOND_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get the number of converted carts for the third reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int | boolean - false
     */
    public function getNbThirdReminderConvertedCarts($from = null, $to = null)
    {
        return $this->getNbConvertedCarts(Emv_CartAlert_Constants::THIRD_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get total amount's converted carts for a specific reminder ( 1 - first_alert, 2 - second_alert, 3 - third alert)
     *
     * @param string $reminderId
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return float | boolean - false
     */
    public function getTotalFlagedOrderAmount($reminderId, $from = null, $to = null)
    {
        $select = $this->_getReadAdapter()->select()
            ->from(array('flag' => $this->getMainTable()), array())
            ->join(
                    array('order' => $this->getTable('sales/order')),
                    'order.entity_id = flag.entity_id',
                    array('SUM(base_grand_total)')
                )
            ->where("flag.flag = ?", Mage::helper('abandonment')->getReminderFlagFromId($reminderId));

        if ($from !== null) {
            $select->where('flag_date >= ?', $from);
        }
        if ($to !== null) {
            $select->where('flag_date <= ?', $to);
        }

        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Get total amount's converted carts for the first reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return float | boolean - false
     */
    public function getSumAmountFirstReminderConvertedCarts($from = null, $to = null)
    {
        return $this->getTotalFlagedOrderAmount(Emv_CartAlert_Constants::FIRST_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get total amount's converted carts for the second reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return float | boolean - false
     */
    public function getSumAmountSecondReminderConvertedCarts($from = null, $to = null)
    {
        return $this->getTotalFlagedOrderAmount(Emv_CartAlert_Constants::SECOND_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get total amount's converted carts for the third reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return float | boolean - false
     */
    public function getSumAmountThirdReminderConvertedCarts($from = null, $to = null)
    {
        return $this->getTotalFlagedOrderAmount(Emv_CartAlert_Constants::THIRD_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get total amount's converted carts for all reminders
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return float | boolean - false
     */
    public function getSumAmountConvertedCarts($from = null, $to = null)
    {
        return  $this->getSumAmountFirstReminderConvertedCarts($from, $to)
                + $this->getSumAmountSecondReminderConvertedCarts($from, $to)
                + $this->getSumAmountThirdReminderConvertedCarts($from, $to);
    }

    /**
     * Get the number of converted carts for all reminders
     *
     * @param string $from
     * @param string $to
     * @return number
     */
    public function getSumNbConvertedCarts($from = null, $to = null)
    {
        return  $this->getNbFirstReminderConvertedCarts($from, $to)
                + $this->getNbSecondReminderConvertedCarts($from, $to)
                + $this->getNbThirdReminderConvertedCarts($from, $to);
    }
}

