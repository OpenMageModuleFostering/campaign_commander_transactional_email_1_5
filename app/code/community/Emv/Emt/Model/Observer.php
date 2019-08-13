<?php
/**
 * Observer class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Observer
{
    /**
     * Observer on SmartFocus account delete event. Delete temporary email template when deleting SmartFocus account
     * @param Varien_Event_Observer $observer
     */
    public function onAccountDelete(Varien_Event_Observer $observer)
    {
        // if we find an emv account
        if ($observer->getEmvAccount()) {
            // check if this account has an temporary email template
            $existingDefault = Mage::helper('emvemt/emvtemplate')->
                getEmvEmt(Emv_Emt_Model_Emt::MAGENTO_TEMPLATE_ID_FOR_EMV_SEND, $observer->getEmvAccount()->getId());
            if ($existingDefault && $existingDefault->getId()) {
                $existingDefault->delete();
            }
        }
    }
}