<?php
/**
 * Reminder Statistic Resource Model
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Mysql4_Stats extends Mage_Core_Model_Mysql4_Abstract {

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('abandonment/stats', 'id');
    }

    /**
     * Get the number of sent emails for a given reminder
     *
     * @param string $reminderId
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int
     */
    protected function _getTotalReminderStats($reminderId, $from = null, $to = null)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), "COUNT(*)")
            ->where("reminder_id = ?", $reminderId);

        if ($from !== null) {
            $select->where('send_date >= ?', $from);
        }
        if ($to !== null) {
            $select->where('send_date <= ?', $to);
        }

        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Get the number of sent emails for the first reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int
     */
    public function getTotalFirstReminderSent($from = null, $to = null)
    {
        return $this->_getTotalReminderStats(Emv_CartAlert_Constants::FIRST_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get the number of sent emails for the second reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int
     */
    public function getTotalSecondReminderSent($from = null, $to = null)
    {
        return $this->_getTotalReminderStats(Emv_CartAlert_Constants::SECOND_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get the number of sent emails for the third reminder
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int
     */
    public function getTotalThirdReminderSent($from = null, $to = null)
    {
        return $this->_getTotalReminderStats(Emv_CartAlert_Constants::THIRD_ALERT_REMINDER_ID, $from, $to);
    }

    /**
     * Get the number of sent emails for all kinds of reminders
     *
     * @param string $from - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @param string $to - Varien_Date::DATETIME_INTERNAL_FORMAT
     * @return int
     */
    public function getSumReminderSent($from = null, $to = null)
    {
        return $this->getTotalFirstReminderSent($from, $to)
            + $this->getTotalSecondReminderSent($from, $to)
            + $this->getTotalThirdReminderSent($from, $to);
    }
}

