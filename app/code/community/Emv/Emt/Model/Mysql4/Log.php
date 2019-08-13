<?php
/**
 * Log Resource Model
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Log extends Mage_Core_Model_Mysql4_Abstract
{
    /* (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    protected function _construct()
    {
        $this->_init('emvemt/log', 'id');
    }

    /**
     * Clean logs before the time limit
     *
     * @param int $timeLimit
     * @return Emv_Emt_Model_Mysql4_Log
     */
    public function cleanLogs($timeLimit)
    {
        $writeAdapter    = $this->_getWriteAdapter();

        $cleaningTimstamp = Mage::getModel('core/date')->gmtTimestamp() - $timeLimit;
        $cleaningTimstamp = Varien_Date::formatDate($cleaningTimstamp, true);

        $condition = array('created_at < ?' => $cleaningTimstamp);
        // clean sending logs
        $writeAdapter->delete($this->getMainTable(), $condition);
        // clean resending queue
        $writeAdapter->delete($this->getTable('emvemt/resending_queue_message'), $condition);
        return $this;
    }

    /**
     * Get total number of logs
     *
     * @return int
     */
    public function getNbTotal()
    {
        $select  = $this->_getReadAdapter()->select();
        $select->from($this->getMainTable())->columns('COUNT(*)');
        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Delete logs
     *
     * @param array $logIds
     * @return Emv_Emt_Model_Mysql4_Log
     */
    public function deleteLogs(array $logIds)
    {
        if (count($logIds)) {
            $condition = array('id IN (?)' => $logIds);

            $writeAdapter   = $this->_getWriteAdapter();
            $writeAdapter->delete($this->getMainTable(), $condition);
        }
        return $this;
    }
}