<?php

/**
 * Observer - checks for abandoned carts and sends the data to campaign commander
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Get abandoned quotes to send reminders
     * @return Mage_Sales_Model_Mysql4_Quote_Collection
     */
    protected function _getConcernedQuotes()
    {
        // prepare query for abandoned quotes
        $minHour = Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_DELAY);

        // all date criteria need to be in utc timezone
        $now = Mage::app()->getLocale()->date();

        /* @var $date Zend_Date */
        $from = Mage::app()->getLocale()->utcDate(null, $now);
        $from->subHour($minHour);
        $from = $from->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        // the expiration date
        $expDate = Mage::app()->getLocale()->utcDate(null, $now);
        $expDate->sub(Emv_CartAlert_Constants::OUTDATED_CART_DELAY, Zend_Date::HOUR);
        $expDate = $expDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        // get abandoned quotes
        /* @var $quotes Mage_Sales_Model_Mysql4_Quote_Collection */
        $quotes = Mage::getModel('sales/quote')->getCollection();
        $select = $quotes->getSelect();
        // make a left join to abandonment table in order to know which template has been used
        $select->joinLeft(
            array('a' => $quotes->getTable('abandonment/abandonment')),
            'main_table.entity_id = a.entity_id',
            array('template')
        );

        //get customers' abandonment_subscribed value
        $attr = Mage::getModel('customer/customer')->getAttribute('abandonment_subscribed');
        $adapter = $quotes->getConnection();
        if ($attr->getAttributeId()) {
            $select->joinLeft(
                array('abandonment_subscribed' => $attr->getBackend()->getTable()),
                $adapter->quoteInto(
                    'main_table.customer_id = abandonment_subscribed.entity_id'
                        . ' AND abandonment_subscribed.attribute_id = ?',
                    $attr->getAttributeId()
                ),
                // by default, we activate abandonment reminder subscription for all clients
                array('abandonment_subscribed_value' => 'IF(abandonment_subscribed.value IS NULL, 1, abandonment_subscribed.value)')
            );
        }

        $select->where('a.customer_abandonment_subscribed = ? OR a.customer_abandonment_subscribed IS NULL', 1)
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::OUTDATED_FLAG)
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::THIRD_ALERT_FLAG)
        ;

        $quotes
            ->addFieldToFilter('updated_at', array('to' => $from, 'from' => $expDate))
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('notnull' => 1)) // a quote with a valid email address
            ->addFieldToFilter('is_active', 1) // only get active quotes
            ->setOrder('updated_at')
            ;

        return $quotes;
    }

    /**
     * Observer to check for abandoned shopping carts
     */
    public function processAbandonedCarts ()
    {
        // save the current store so that this observer doesn't interfere with any other functions
        $currentStore = Mage::app()->getStore()->getStoreId();

        // if Shopping Cart Abandonment is enabled
        if(
            Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_ENABLED) == 1
            || Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_ENABLED) == 1
            || Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_ENABLED) == 1
        ) {
            /* @var $helper Emv_CartAlert_Helper_Data */
            $helper = Mage::helper('abandonment');

            $quotes = $this->_getConcernedQuotes();
            /* @var $quote Mage_Sales_Model_Quote */
            foreach ($quotes as $quote) {
                // Set the current store
                Mage::app()->setCurrentStore($quote->getStoreId());

                // flag to indicate if we need to save the abandonment
                $needToSaveAbandonment = false;

                // search abandoned carts for this particular quote
                $abandonment = Mage::getModel('abandonment/abandonment')->loadByQuoteId($quote->getEntityId());

                if (!$abandonment->getId()) {
                    $abandonment->setEntityId($quote->getId())
                        ->setCustomerAbandonmentSubscribed((boolean)$quote->getData('abandonment_subscribed_value'))
                        ->setCustomerId($quote->getCustomerId())
                    ;
                    $needToSaveAbandonment = true;
                }

                // if the quote has not yet been processed for abandonment, set it up for processing
                $previousTemplate = $abandonment->getTemplate();
                if (empty($previousTemplate)) {
                    $previousTemplate = Emv_CartAlert_Constants::TO_BE_PROCESSED_FLAG;
                }

                // only send email if client allows to do that
                if ($quote->getData('abandonment_subscribed_value')) {
                    // get updated template
                    $updatedTemplate = $this->getUpdatedTemplate(
                        $quote,
                        $previousTemplate,
                        $quote->getStoreId()
                   );

                    if ($updatedTemplate != null) {
                        $preparedCustomerName = ($quote->getCustomerPrefix() ? $quote->getCustomerPrefix() . ' ' : '')
                            . $quote->getCustomerFirstname()
                            . ' ' . ($quote->getCustomerMiddlename() ? $quote->getCustomerMiddlename() . ' ' : '')
                            . $quote->getCustomerLastname()
                            . ($quote->getCustomerSuffix() ? ' ' . $quote->getCustomerSuffix() : '')
                            ;
                        $quote->setPreparedCustomerName($preparedCustomerName);

                        // set the unsubscription link (depends on whether the user was logged in)
                        $quote->setUnsubLink($helper->getUnsubscribeLink($quote));
                        // set the cart link (depends on whether the user was logged in)
                        $quote->setCartLink($helper->getCartLink($quote, $updatedTemplate));
                        // set the store link (depends on whether the user was logged in)
                        $quote->setStoreLink($helper->getStoreLink($quote, $updatedTemplate));
                        // set the reminder template will be used to send
                        $quote->setReminderTemplate($updatedTemplate);

                        // set formated grand total
                        $quote->setPreparedGrandToTal(Mage::helper('checkout')->formatPrice($quote->getGrandTotal(), true, true));

                        if ($previousTemplate != $updatedTemplate) {
                            // update the reminder template will be used to send
                            $abandonment->setTemplate($updatedTemplate);

                            $rule = $helper->getShoppingCartPriceRule();
                            if ($rule->getId() != $abandonment->getShoppingCartRuleId()) {
                                $abandonment->setShoppingCartRuleId($rule->getId());
                                $abandonment->setCouponCode($helper->getCouponCode($rule->getId()));
                            }

                            $needToSaveAbandonment = true;
                        }

                        $helper->sendReminder($quote, $quote->getStoreId(), $abandonment);
                    }
                }

                if ($needToSaveAbandonment) {
                    $abandonment->save();
                }
            }
        }

        // return the store to normal
        Mage::app()->setCurrentStore($currentStore);
    }

    /**
     * Indicate which reminder template can be used to send.
     * Return null if we can't send the reminder else the reminder name
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $currentTemplate
     * @param string $store
     * @return NULL | string
     */
    public function getUpdatedTemplate(Mage_Sales_Model_Quote $quote, $currentTemplate, $store = null)
    {
        // By default, we don't allow to send any reminder
        $updatedTemplate = null;
        $locale =  Mage::app()->getLocale();

        // By default, Magento uses UTC as its timezone, so all date criteria need to be in utc timezone
        /* @var $lastUpdate Zend_Date */
        $lastUpdate = $locale->date($quote->getUpdatedAt(), Varien_Date::DATETIME_INTERNAL_FORMAT);
        $lastUpdate = $locale->utcDate(null, $lastUpdate);

        // now
        $now = $locale->date(null, Varien_Date::DATETIME_INTERNAL_FORMAT);

        // expiration date
        $expirationDate = $locale->utcDate(null, $now);
        $expirationDate->subHour(Emv_CartAlert_Constants::OUTDATED_CART_DELAY);

        if ($expirationDate->isLater($lastUpdate)) {
            $updatedTemplate = Emv_CartAlert_Constants::OUTDATED_FLAG;
        } else { // if the template is not yet outdated
            $firstAlertDelay = false;
            $secondAlertDelay = false;
            $thirdAlertDelay = false;

            if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_ENABLED, $store)) {
                $firstAlertDelay = $locale->utcDate(null, $now);
                $firstAlertDelay->subHour(
                    Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_DELAY, $store)
                );
            }

            if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_ENABLED, $store)) {
                $secondAlertDelay = $locale->utcDate(null, $now);
                $secondAlertDelay->subHour(
                    Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_DELAY, $store)
                );
            }

            if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_ENABLED, $store)) {
                $thirdAlertDelay = $locale->utcDate(null, $now);
                $thirdAlertDelay->subHour(
                    Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_DELAY, $store)
                );
            }

            // determine which reminder (first, second, third) needs to be sent
            if (
                $currentTemplate == Emv_CartAlert_Constants::TO_BE_PROCESSED_FLAG
                && $firstAlertDelay
                && $firstAlertDelay->isLater($lastUpdate)
            ) {
                $updatedTemplate = Emv_CartAlert_Constants::FIRST_ALERT_FLAG;
            } else if (
                // only send second reminder when the first one was sent, and the second delay is later than the last update
                $currentTemplate == Emv_CartAlert_Constants::FIRST_ALERT_FLAG
                && $secondAlertDelay
                && $secondAlertDelay->isLater($lastUpdate)
            ) {
                $updatedTemplate = Emv_CartAlert_Constants::SECOND_ALERT_FLAG;
            } else if (
                $currentTemplate == Emv_CartAlert_Constants::SECOND_ALERT_FLAG
                && $thirdAlertDelay
                && $thirdAlertDelay->isLater($lastUpdate)
            ) {
                // only send second reminder when the second one was sent, and the third delay is later than the last update
                $updatedTemplate = Emv_CartAlert_Constants::THIRD_ALERT_FLAG;
            }
        }

        return $updatedTemplate;
    }

    /**
     * Upon saving a customer, synchronise the "subscribed from abandoned cart notifications" attribute with the quote.
     * While Magento natively does it, it only synchronizes the attributes when the customer views the cart.
     * While the customer is redirected to his cart upon unsubscribing, updating the quote, it is not immediately
     * synchronized when done in back-office.
     *
     * event: customer_save_after
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleCustomerSaveAfter (Varien_Event_Observer $observer)
    {
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $observer->getEvent()->getCustomer();
        $isSubscribed = $customer->getAbandonmentSubscribed();

        /* @var $abandonments Emv_CartAlert_Model_Mysql4_Abandonment_Collection */
        $abandonments = Mage::getResourceModel('abandonment/abandonment_collection')
            ->updateCustomerAbadonementSubscribed($customer->getId(), $isSubscribed);

        return $this;
    }

    /**
     * Handle order save after event
     * @param Varien_Event_Observer $observer
     */
    public function handleOrderSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();

        $session = Mage::getSingleton('customer/session');

        if (!$session->getData('abandonment_flag')) {
            return;
        }

        /* @var $orderFlag Emailvision_Abandonment_Model_Orderflag */
        $orderFlag = Mage::getModel('abandonment/orderflag')->loadByOrderId($order->getId());

        if (!$orderFlag->getId()) {
            $orderFlag->setEntityId($order->getId());
            $orderFlag->setFlag($session->getData('abandonment_flag'));
            $orderFlag->setFlagDate($session->getData('abandonment_date'));
            $orderFlag->save();
        }

        $session->unsetData('abandonment_flag');
        $session->unsetData('abandonment_date');
    }
}
