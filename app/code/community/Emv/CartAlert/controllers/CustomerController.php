<?php
/**
 * Customer controller
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_CustomerController extends Mage_Core_Controller_Front_Action
{
    /**
     * Both actions require the customer to be logged in
     * (non-PHPdoc)
     * @see Mage_Core_Controller_Front_Action::preDispatch()
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Unsubscribe the customer from the abandoned carts notification.
     */
    public function unsubscribeAction()
    {
        // No need to test if the customer is logged in: guests are automatically redirected to the login page

        /* @var $helper Emv_CartAlert_Helper_Data */
        $helper = Mage::helper('abandonment');

        /* @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $session->getCustomer();

        /* @var $session Mage_Checkout_Model_Session */
        $session = Mage::getSingleton('checkout/session');
        if (!$customer->getAbandonmentSubscribed()) {
            $message = $helper->__('You are already unsubscribed from abandoned carts notifications.');
            $session->addError($message);
        } else {
            $customer->setAbandonmentSubscribed(0)->save();
            $message = $helper->__('You were successfully unsubscribed from abandoned carts notifications.');
            $session->addSuccess($message);
        }

        $this->_redirect('checkout/cart/index');
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

    /**
     * Redirect the user to the cart
     */
    public function cartAction()
    {
        $reminderId = $this->getRequest()->getParam('reminder', 1);

        $this->_saveReminderIdInSession($reminderId);

        $this->_redirect('checkout/cart/index');
    }

    /**
     * Redirect the user to the store
     */
    public function storeAction()
    {
        $reminderId = $this->getRequest()->getParam('reminder', 1);

        $this->_saveReminderIdInSession($reminderId);

        $this->_redirect('');
    }

    /**
     * Redirect the user to the product
     */
    public function productAction()
    {
        $reminderId = $this->getRequest()->getParam('reminder', 1);
        $productId = $this->getRequest()->getParam('productid', null);

        $this->_saveReminderIdInSession($reminderId);

        if ($productId !== null) {
            $product = Mage::getModel('catalog/product');
            $product->load($productId);
            $productUrl = Mage::getModel('catalog/product_url');
            $url = $productUrl->getUrlPath($product);

            $this->_redirect($url);
            return;
        }

        $this->_redirect('');
    }

    /**
     * Cart Reminder Subscription Management Action
     */
    public function manageAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        if ($block = $this->getLayout()->getBlock('customer_abandonment')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('abandonment')->__('Cart Reminder Subscription'));
        $this->renderLayout();
    }

    /**
     * Cart Reminder Subscription Save Action
     */
    public function saveAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('customer/account/');
        }

        try {
            $subscribed = (boolean)$this->getRequest()->getParam('is_subscribed', false);

            Mage::getSingleton('customer/session')->getCustomer()
                ->setAbandonmentSubscribed($subscribed)
                ->save();

            if ($subscribed) {
                Mage::getSingleton('customer/session')
                    ->addSuccess(Mage::helper('abandonment')->__('The subscription has been saved.'));
            } else {
                Mage::getSingleton('customer/session')
                    ->addSuccess(Mage::helper('abandonment')->__('The subscription has been removed.'));
            }
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')
                ->addError(Mage::helper('abandonment')->__('An error occurred while saving your subscription.'));
        }

        $this->_redirect('customer/account/');
    }
}
