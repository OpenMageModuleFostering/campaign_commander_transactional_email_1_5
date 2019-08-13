<?php
/**
 * Guest controller
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_GuestController extends Mage_Core_Controller_Front_Action
{
    /**
     * Current quote ID
     * @var int
     */
    protected $_quoteId = null;

    /**
     *
     * Current quote
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     * Checks if the customer is logged in. In order for this controller to run properly, the
     * customer must be logged out.
     * Causes an error message to be displayed if the customer is logged in.
     *
     * @return boolean
     */
    protected function _isCustomerLoggedIn()
    {
        $loggedIn = Mage::getSingleton('customer/session')->authenticate($this);

        if ($loggedIn) {
            /* @var $session Mage_Checkout_Model_Session */
            $session = Mage::getSingleton('checkout/session');
            $message = Mage::helper('abandonment')->__('The link cannot be used by registered customers.');
            $session->addError($message);
        }

        return $loggedIn;
    }

    /**
     * Checks if the parameters ("quote" and "key") are valid.
     * Stores the quote ID and the quote itself as class properties if the parameters are valid.
     * Causes an error message to be displayed if the parameters are invalid.
     * @return boolean
     */
    protected function _validateParameters()
    {
        /* @var $session Mage_Checkout_Model_Session */
        $session = Mage::getSingleton('checkout/session');
        $quoteId = $this->getRequest()->getParam('quote');
        $hash = $this->getRequest()->getParam('key', '');
        $validHash = false;

        /* @var $quote Mage_Sales_Model_Quote */
        // try to load the quote object from quote id
        $quote = Mage::getModel('sales/quote')->load($quoteId);

        if ($quote->getId()) {
            $this->_quoteId = $quote->getId();
            $this->_quote = $quote;
            // only guest check out is allowed
            if (!$quote->getCustomerId()) {
                $validHash = Mage::helper('core')->validateHash($quote->getCustomerEmail(), $hash);
            }
        }

        if (!$validHash) {
            $message = Mage::helper('abandonment')->__('The link is invalid.');
            $session->addError($message);
        }

        return $validHash;
    }

    /**
     * Unsubscribe the customer from the abandoned carts notification.
     */
    public function unsubscribeAction()
    {
        if ($this->_isCustomerLoggedIn() || (!$this->_validateParameters())) {
            $this->_redirect('checkout/cart/index');
        } else {
            /* @var $helper Emv_CartAlert_Helper_Data */
            $helper = Mage::helper('abandonment');

            /* @var $session Mage_Customer_Model_Session */
            $session = Mage::getSingleton('checkout/session');

            $abandonment = Mage::getModel('abandonment/abandonment')->loadByQuoteId($this->_quote->getId());
            if ($abandonment->getId()) {
                if ($abandonment->getCustomerAbandonmentSubscribed()) {
                    $abandonment->setCustomerAbandonmentSubscribed(0)->save();
                    $message = $helper->__('You were successfully unsubscribed from abandoned carts notifications.');
                    $session->addSuccess($message);
                } else {
                    $message = $helper->__('You are already unsubscribed from abandoned carts notifications.');
                    $session->addError($message);
                }
            }

            Mage::getSingleton('checkout/session')->setQuote($this->_quote);
            Mage::getSingleton('checkout/cart')->setQuote($this->_quote);
            Mage::getSingleton('checkout/session')->setQuoteId($this->_quote->getId());
            $this->_redirect('checkout/cart/index');
        }
    }

    /**
     * After checking the session, and the provided parameters, redirect the user to the cart
     */
    public function cartAction()
    {
        if (!$this->_isCustomerLoggedIn() && ($this->_validateParameters())) {
            Mage::getSingleton('checkout/cart')->setQuote($this->_quote);
            Mage::getSingleton('checkout/session')->setQuote($this->_quote);
            Mage::getSingleton('checkout/session')->setQuoteId($this->_quote->getId());
        }

        $reminderId = $this->getRequest()->getParam('reminder', 1);

        $this->_saveReminderIdInSession($reminderId);

        $this->_redirect('checkout/cart/index');
    }

    /**
     * After checking the session, and the provided parameters, redirect the user to the store
     */
    public function storeAction()
    {
        if (!$this->_isCustomerLoggedIn() && ($this->_validateParameters())) {
            Mage::getSingleton('checkout/cart')->setQuote($this->_quote);
            Mage::getSingleton('checkout/session')->setQuote($this->_quote);
            Mage::getSingleton('checkout/session')->setQuoteId($this->_quote->getId());
        }

        $reminderId = $this->getRequest()->getParam('reminder', 1);

        $this->_saveReminderIdInSession($reminderId);

        $this->_redirect('');
    }

    /**
     * After checking the session, and the provided parameters, redirect the user to the product
     */
    public function productAction()
    {
        if (!$this->_isCustomerLoggedIn() && ($this->_validateParameters())) {
            Mage::getSingleton('checkout/cart')->setQuote($this->_quote);
            Mage::getSingleton('checkout/session')->setQuote($this->_quote);
            Mage::getSingleton('checkout/session')->setQuoteId($this->_quote->getId());
        }

        $reminderId = $this->getRequest()->getParam('reminder', 1);
        $productId = $this->getRequest()->getParam('productid', null);

        if ($productId !== null) {
            $this->_saveReminderIdInSession($reminderId);

            $product = Mage::getModel('catalog/product');
            $product->load($productId);
            $productUrl = Mage::getModel('catalog/product_url');
            $url = $productUrl->getUrlPath($product);

            $this->_redirect($url);
        }
        return;
    }

    /**
     * Save reminder id with current date time in to customer session
     *
     * @param string $reminderId
     * @return Emv_CartAlert_CustomerController
     */
    protected function _saveReminderIdInSession($reminderId)
    {
        $reminderFlag = Mage::helper('abandonment')->getReminderFlagFromId($reminderId);

        // store the reminder flag in the session (first_alert, second_alert ..)
        $session = Mage::getSingleton('customer/session');
        $session->setData('abandonment_flag', $reminderFlag);
        // store the current date time in the session too
        $now = Zend_Date::now();
        $session->setData('abandonment_date', $now->toString('YYYY-MM-dd HH:mm:ss'));
        return $this;
    }
}
