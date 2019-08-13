<?php

/**
 * Observer - checks for abandoned carts and sends the data to campaign commander
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Observer extends Mage_Core_Model_Abstract
{
    protected $_testMode = null;

    /**
     * @var Mage_Customer_Model_Entity_Attribute
     */
    protected $_subscribeAttribute = null;

    /**
     * @var array
     */
    protected $_reminderTypes = array('first', 'second', 'third');

    /**
     * @return boolean
     */
    public function getTestMode()
    {
        if ($this->_testMode === null) {
            $this->_testMode = (bool)Mage::getStoreConfig(
                Emv_CartAlert_Constants::XML_PATH_TEST_MODE_ENABLED,
                Mage_Core_Model_App::ADMIN_STORE_ID
            );
        }

        return $this->_testMode;
    }

    /**
     * Get abandonment_subscribed attribute
     * @return  Mage_Customer_Model_Entity_Attribute | null
     */
    protected function _getSubscribeAttribute()
    {
        if (!$this->_subscribeAttribute) {
            // get customers' abandonment_subscribed value
            $this->_subscribeAttribute = Mage::getModel('customer/customer')->getAttribute('abandonment_subscribed');
        }
        return $this->_subscribeAttribute;
    }

    /**
     * Get abandoned quotes to send reminders
     *
     * @param int $limit
     * @param string $type reminder type
     * @return Mage_Sales_Model_Mysql4_Quote_Collection
     */
    protected function _getConcernedQuotes($limit, $type)
    {
        // prepare query for abandoned quotes
        $minHour = Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_DELAY);

        // all date criteria need to be in utc timezone
        $now = Mage::app()->getLocale()->date();

        /* @var $date Zend_Date */
        $from = Mage::app()->getLocale()->utcDate(null, $now);

        if ($this->getTestMode()) {
            $from->subMinute($minHour);
        } else {
            $from->subHour($minHour);
        }
        $from = $from->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        // the expiration date
        $expDate = Mage::app()->getLocale()->utcDate(null, $now);
        $expDate->sub(Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_LIFETIME), Zend_Date::HOUR);
        $expDate = $expDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        // get abandoned quotes
        /* @var $quotes Mage_Sales_Model_Mysql4_Quote_Collection */
        $quotes = Mage::getModel('sales/quote')->getCollection();
        $select = $quotes->getSelect();

        // set the limit
        $select->limit($limit);

        // make a left join to abandonment table get all data
        $select->joinLeft(
            array('a' => $quotes->getTable('abandonment/abandonment')),
            'main_table.entity_id = a.entity_id',
            array(
                'abandonment_id',
                'abandonment_entity_id'                                 => 'a.entity_id',
                'abandonment_template'                                  => 'a.template',
                'abandonment_customer_abandonment_subscribed'           => 'a.customer_abandonment_subscribed',
                'abandonment_customer_id'                               => 'a.customer_id',
                'abandonment_shopping_cart_rule_id'                     => 'a.shopping_cart_rule_id',
                'abandonment_coupon_code'                               => 'a.coupon_code'
            )
        );

        // get customers' abandonment_subscribed value
        $attr = $this->_getSubscribeAttribute();
        $adapter = $quotes->getConnection();
        if ($attr->getAttributeId()) {
            $select->joinLeft(
                array('abandonment_subscribed' => $attr->getBackend()->getTable()),
                $adapter->quoteInto(
                    'main_table.customer_id = abandonment_subscribed.entity_id'
                        . ' AND abandonment_subscribed.attribute_id = ?',
                    $attr->getAttributeId()
                ),
                // by default, we activate abandonment reminder for all clients
                array(
                    'abandonment_subscribed_value'
                        => 'IF(abandonment_subscribed.value IS NULL, 1, abandonment_subscribed.value)'
                )
            );
        }

        // only get the carts which
        //    1. their users allow to receive the reminders
        //    2. are not too late
        //    3. have not received the third reminders yet
        $select
            ->where('
                (
                    main_table.customer_id IS NOT NULL
                    AND (abandonment_subscribed.value = 1 OR abandonment_subscribed.value IS NULL)
                )
                OR (
                    main_table.customer_id IS NULL
                    AND (a.customer_abandonment_subscribed = 1 OR a.customer_abandonment_subscribed IS NULL)
                )
            ')
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::OUTDATED_FLAG)
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::THIRD_ALERT_FLAG)
        ;

        switch ($type) {
            case 'first' :
                $select->where(
                    'a.template IS NULL OR a.template = "" OR a.template = ?',
                    Emv_CartAlert_Constants::TO_BE_PROCESSED_FLAG
                );
                break;
            case 'second' :
                $select->where('a.template = ?', Emv_CartAlert_Constants::FIRST_ALERT_FLAG);
                break;
            case 'third'  :
                $select->where('a.template = ?', Emv_CartAlert_Constants::SECOND_ALERT_FLAG);
                break;
        }

        $quotes
            ->addFieldToFilter('main_table.updated_at', array('to' => $from, 'from' => $expDate))
            ->addFieldToFilter('items_count', array('gt' => 0)) // have at least 1 item
            ->addFieldToFilter('customer_email', array('notnull' => 1)) // a quote with a valid email address
            ->addFieldToFilter('is_active', 1) // only get active quotes
            ->setOrder('updated_at', Varien_Data_Collection::SORT_ORDER_ASC) // sort the carts from older to newer
            ;

        return $quotes;
    }

    /**
     * Process the abandonned carts for a given type reminder
     * @param int $limit
     * @param string $type reminder type (first, second, third)
     */
    public function processAbandonedCartsForType($limit, $type)
    {
        /* @var $helper Emv_CartAlert_Helper_Data */
        $helper = Mage::helper('abandonment');

        $pathConfig;
        switch ($type) {
            case 'first' :
                $pathConfig = Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_ENABLED;
                break;
            case 'second' :
                $pathConfig = Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_ENABLED;
                break;
            case 'third' :
                $pathConfig = Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_ENABLED;
                break;
        }

        // check if this reminder is activated
        if (Mage::getStoreConfig($pathConfig) == 1) {
            // get all quotes that can be sent for this reminder
            $quotes = $this->_getConcernedQuotes($limit, $type);

            /* @var $quote Mage_Sales_Model_Quote */
            foreach ($quotes as $quote) {
                // Set the current store
                Mage::app()->setCurrentStore($quote->getStoreId());

                // flag to indicate if we need to save the abandonment
                $needToSaveAbandonment = false;

                // search abandoned carts for this particular quote
                $abandonment = $this->_prepareAbandonmentObjectFromQuote($quote);

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
                        if ($previousTemplate != $updatedTemplate) {
                            // update the reminder template will be used to send
                            $abandonment->setTemplate($updatedTemplate);

                            // if we change the promotion shopping cart rule, we need to change the coupon code
                            $rule = $helper->getShoppingCartPriceRule();
                            if ($rule->getId() != $abandonment->getShoppingCartRuleId()) {
                                $abandonment->setShoppingCartRuleId($rule->getId());
                                $abandonment->setCouponCode($helper->getCouponCode($rule->getId()));
                            }
                            $needToSaveAbandonment = true;
                        }

                        $helper->prepareAndSendReminder($updatedTemplate, $quote, $abandonment, true);
                    }
                }

                if ($needToSaveAbandonment) {
                    $gmtDate = Mage::getModel('core/date')->gmtDate();
                    $abandonment->setUpdatedAt($gmtDate);
                    $abandonment->save();
                }
            }
        }
    }

    /**
     * Observer to check for abandoned shopping carts
     */
    public function processAbandonedCarts ()
    {
        /* @var $helper Emv_CartAlert_Helper_Data */
        $helper = Mage::helper('abandonment');
        $createdLock = false;

        // save the current store so that this observer doesn't interfere with any other functions
        $currentStore = Mage::app()->getStore()->getStoreId();
        // !!! it's very important to set a custom error handler in order to remove lock file in case of fatal error
        Mage::helper('emvcore')->setSmartFocusErrorHandler();

        try {
            // check if another process is being run now
            if (!$helper->checkLockFile()) {
                // create lock file => do not allow several process at the same time
                $helper->createLockFile();
                $createdLock = true;

                $limit = $helper->getCartLimitForOneRun();
                $activatedTypes = 0;
                if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_ENABLED) == 1) {
                    $activatedTypes++;
                }
                if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_ENABLED) == 1) {
                    $activatedTypes++;
                }
                if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_ENABLED) == 1) {
                    $activatedTypes++;
                }
                $limitPerType = $limit;
                if ($activatedTypes > 0) {
                    $limitPerType = (int)$limit / $activatedTypes;
                }

                foreach($this->_reminderTypes as $type) {
                    $this->processAbandonedCartsForType($limitPerType, $type);
                    // return the store to normal
                    Mage::app()->setCurrentStore($currentStore);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        // if lock is created, need to delete it
        if ($createdLock) {
            try {
                $helper->removeLockFile();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        // reset error handler to Magento one
        Mage::helper('emvcore')->resetErrorHandler();

        // return the store to normal
        Mage::app()->setCurrentStore($currentStore);
    }

    /**
     * Prepare abandonment object from quote retrieved from last query select
     *
     * @param Varien_Object $quote
     * @return Emv_CartAlert_Model_Abandonment
     */
    protected function _prepareAbandonmentObjectFromQuote(Varien_Object $quote)
    {
        $abandonment = Mage::getModel('abandonment/abandonment');
        $abandonment->setData('abandonment_id', $quote->getData('abandonment_id'));
        $abandonment->setData('entity_id', $quote->getData('abandonment_entity_id'));
        $abandonment->setData('template', $quote->getData('abandonment_template'));
        $abandonment->setData('customer_abandonment_subscribed', $quote->getData('abandonment_customer_abandonment_subscribed'));
        $abandonment->setData('customer_id', $quote->getData('abandonment_customer_id'));
        $abandonment->setData('shopping_cart_rule_id', $quote->getData('abandonment_shopping_cart_rule_id'));
        $abandonment->setData('coupon_code', $quote->getData('abandonment_coupon_code'));

        return $abandonment;
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
        $expirationDate->subHour(Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_LIFETIME));

        if ($expirationDate->isLater($lastUpdate)) {
            $updatedTemplate = Emv_CartAlert_Constants::OUTDATED_FLAG;
        } else { // if the template is not yet outdated
            $firstAlertDelay = false;
            $secondAlertDelay = false;
            $thirdAlertDelay = false;

            if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_ENABLED, $store)) {
                $firstAlertDelay = $locale->utcDate(null, $now);
                if ($this->getTestMode()) {
                    $firstAlertDelay->subMinute(
                        Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_DELAY, $store)
                    );
                } else {
                    $firstAlertDelay->subHour(
                        Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_DELAY, $store)
                    );
                }
            }

            if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_ENABLED, $store)) {
                $secondAlertDelay = $locale->utcDate(null, $now);
                if ($this->getTestMode()) {
                    $secondAlertDelay->subMinute(
                        Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_DELAY, $store)
                    );
                } else {
                    $secondAlertDelay->subHour(
                        Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_DELAY, $store)
                    );
                }
            }

            if (Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_ENABLED, $store)) {
                $thirdAlertDelay = $locale->utcDate(null, $now);
                if ($this->getTestMode()) {
                    $thirdAlertDelay->subMinute(
                        Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_DELAY, $store)
                    );
                } else {
                    $thirdAlertDelay->subHour(
                        Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_DELAY, $store)
                    );
                }
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

    /**
     * Handle Quote save after event - reset the reminder template for a registered customer
     * so that he can receive a new reminder
     * @param Varien_Event_Observer $observer
     */
    public function handleQuoteSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Quote */
        $quote = $observer->getEvent()->getQuote();
        if ($quote instanceof Mage_Sales_Model_Quote) {
            $session = Mage::getSingleton('customer/session');
            // if customer is logged in and he has some products inside cart
            if ($session->isLoggedIn() && $quote->getItemsCount() && $quote->getId()) {
                if (!$session->getData('abandonment_reset_flag')) {
                    try {
                        Mage::helper('abandonment')->resetReminderForQuote($quote);
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                    $session->setData('abandonment_reset_flag', 1);
                 }
             }
        }
    }
}
