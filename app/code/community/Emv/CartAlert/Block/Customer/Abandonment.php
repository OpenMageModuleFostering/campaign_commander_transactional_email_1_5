<?php
/**
 * Abandonment Cart Reminder Subscription Block for customer
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Block_Customer_Abandonment extends Mage_Customer_Block_Account_Dashboard // Mage_Core_Block_Template
{

    /**
     * Constructor - set template file
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('customer/form/newsletter.phtml');
    }

    /**
     * Check if user is subscribed to abandonment cart reminder
     * @return unknown
     */
    public function getIsSubscribed()
    {
        $isSubscribed = Mage::getSingleton('customer/session')->getCustomer()
            ->getAbandonmentSubscribed();

        return $isSubscribed;
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Block_Abstract::getAction()
     */
    public function getAction()
    {
        return $this->getUrl('*/*/save');
    }

}
