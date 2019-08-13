<?php
/**
 * Abadonment Model
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Abandonment extends Mage_Core_Model_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
        $this->_init('abandonment/abandonment');
    }

    /**
     * Load abandonment cart by quote id
     *
     * @param string $quoteId
     * @return Emv_CartAlert_Model_Abandonment
     */
    public function loadByQuoteId($quoteId)
    {
        $this->setData($this->getResource()->loadByQuoteId($quoteId));
        return $this;
    }
}
