<?php
/**
 * EmailVision resending queue message resource model
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Resending_Queue_Message extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
        $this->_init('emvemt/resending_queue_message', 'id');
    }
}