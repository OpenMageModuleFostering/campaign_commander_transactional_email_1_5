<?php
/**
 * Block included in abandoned carts reminder e-mails
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Block_Cart_Items extends Mage_Sales_Block_Items_Abstract
{
    /**
     * Constructor - set template file
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('smartfocus' . DS . 'abandonment' . DS . 'reminder' . DS . 'items.phtml');
    }
}