<?php
/**
 * Abandonment Resource Model
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Mysql4_Abandonment extends Mage_Core_Model_Mysql4_Abstract {

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    public function _construct() {
        $this->_init('abandonment/abandonment', 'abandonment_id');
    }

    /**
     * Get abandonment data for a quote id
     *
     * @param string $quoteId
     * @return array
     */
    public function loadByQuoteId($quoteId)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
                    ->from($this->getMainTable())
                    ->where('entity_id=?', $quoteId);

        $result = $adapter->fetchRow($select);

        return count($result) ? $result : Array();
    }
}

