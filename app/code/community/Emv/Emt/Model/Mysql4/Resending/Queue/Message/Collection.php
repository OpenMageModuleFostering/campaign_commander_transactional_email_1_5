<?php
/**
 * EmailVision resending queue message collection resource model
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Resending_Queue_Message_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Db_Collection_Abstract::_construct()
     */
    protected function _construct()
    {
        $this->_init('emvemt/resending_queue_message');
    }

    /**
     * Get list of messages that have not been sent
     *
     * @return Emv_Emt_Model_Mysql4_Resending_Queue_Message_Collection
     */
    public function getListNotSent()
    {
        $this->getSelect()
         ->where('main_table.sent_sucess = ?', Emv_Emt_Model_Resending_Queue_Message::IS_NOT_SENT)
         ->orWhere('main_table.sent_sucess IS NULL')
         ->where('main_table.number_attempts IS NULL OR main_table.number_attempts < ?', Emv_Emt_Model_Resending_Queue_Message::MAX_ATTEMPT)
         ->order('main_table.id');

         return $this;
    }
}