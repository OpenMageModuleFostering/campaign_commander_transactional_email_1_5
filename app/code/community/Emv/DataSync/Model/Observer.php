<?php
/**
 * Observe subscriber save to set its last update date and call the api method to update it's changes
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
/* @var $customer Mage_Customer_Model_Customer */
/* @var $customerAddress Mage_Customer_Model_Address */
/* @var $subscriber Mage_Newsletter_Model_Subscriber */
/* @var $emailVisionApi Auguria_EmailVision_Model_Emailvision_Api */
/* @var $dateModel Mage_Core_Model_Date */
class Emv_DataSync_Model_Observer
{
    const DEFAULT_STORE_CODE = 'admin';
    const XML_PATH_EMAILVISION_OBSERVER_ENABLED = 'emvdatasync/apimember/enabled';

    protected $_instanciationTime;

    protected $_customerHasDataChanged = array();
    protected $_subscriberSaved        = array();

    /**
     * Customer newsletter subscriber
     *
     * @var array
     */
    protected $_customerNewsletter = array();

    /**
     * Constructor, register $this instanciation datetime
     */
    public function __construct()
    {
        // date time will be in GMT timezone
        $this->_instanciationTime = Mage::helper('emvdatasync')->getFormattedGmtDateTime(null);
    }

    /**
     * Method triggered on subscriber delete. Will always unjoin customer
     *
     * @param Varien_Event_Observer $observer
     */
    public function onSubscriberDelete(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfigFlag(self::XML_PATH_EMAILVISION_OBSERVER_ENABLED)) {
            $subscriber = $observer->getSubscriber();
            // Direct unjoin (subscriber will be deleted on customer delete)
            if ($subscriber->getId()) {
                try {
                    $account = Mage::helper('emvdatasync')->getEmvAccountForStore();
                    $service = Mage::getModel('emvdatasync/service_member');
                    $service->setAccount($account);
                    $service->unjoinOneSubscriber($subscriber);
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * Method triggered on customer_address save. Reset the data last update date to now if hasDataChanges and
     * if a corresponding subscriber exists
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCustomerAddressSave(Varien_Event_Observer $observer)
    {
        // If address is a billing one and has data changes, else nothing to do (avoid loading customer and subscriber)
        $customerAddress = $observer->getCustomerAddress();
        if ($customerAddress->getIsDefaultBilling() && $customerAddress->hasDataChanges()) {
            $customer   = $customerAddress->getCustomer();
            $subscriber = $this->getSubscriberFromCustomer($customer);

            // If a subscriber is linked to this address' customer, update last data changes date
            if (
                $subscriber && $subscriber->getId()
                && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED
            ) {
                $this->setCustomerHasDataChanged($subscriber->getId());
            }
        }
    }

    /**
     * @param string $subscriberId
     * @return Emv_DataSync_Model_Observer
     */
    public function setCustomerHasDataChanged($subscriberId)
    {
         $this->_customerHasDataChanged[$subscriberId] = true;
         return $this;
    }

    /**
     * @param string $subscriberId
     * @return boolean
     */
    public function customerHasDataChanged($subscriberId)
    {
         return isset($this->_customerHasDataChanged[$subscriberId])
             ? $this->_customerHasDataChanged[$subscriberId] : false;
    }

    /**
     * Get subscriber from customer
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Newsletter_Model_Subscriber | boolean
     */
    public function getSubscriberFromCustomer(Mage_Customer_Model_Customer $customer)
    {
        $customerId = $customer->getId();

        if ($customerId) {
            if (!isset($this->_customerNewsletter[$customerId])) {
                $this->_customerNewsletter[$customerId] =
                    Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
            }

            return $this->_customerNewsletter[$customerId];
        }

        return false;
    }

    /**
     * Method triggered on customer save. Reset the data last update date to now if hasDataChanges and if a
     * corresponding subscriber exists
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCustomerSave(Varien_Event_Observer $observer)
    {
        $customer   = $observer->getCustomer();
        $subscriber = $this->getSubscriberFromCustomer($customer);

        if ($subscriber && $subscriber->getId()) {
            // Prevent multiple calls on a Mage::app instance
            if (!$this->subscriberSaved($subscriber->getId())) {
                if (
                    $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED
                    && $customer->hasDataChanges()
                ) {
                    $this->setCustomerHasDataChanged($subscriber->getId());
                }
            }
        }
    }

    /**
     * @param string $subscriberId
     * @return Emv_DataSync_Model_Observer
     */
    public function setSubscriberSaved($subscriberId)
    {
        $this->_subscriberSaved[$subscriberId] = true;
        return $this;
    }

    /**
     * @param string $subscriberId
     * @return boolean
     */
    public function subscriberSaved($subscriberId)
    {
        return isset($this->_subscriberSaved[$subscriberId]) ? $this->_subscriberSaved[$subscriberId] : false;
    }

    /**
     * Method called on subscriber save. Call submethods if required. New subscribers will
     * always have the return of getIsStatusChanged method setted to 1.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onSubscriberSave(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getSubscriber();
        if (!$subscriber->getId()) {
            return false;
        }

        // Prevent multiple calls on a Mage::app instance
        if (!$this->subscriberSaved($subscriber->getId())) {
            if (
                $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED
                && ($this->customerHasDataChanged($subscriber->getId()) || $this->isSubscriberStatusChanged($subscriber))
            ) {
                $this->_updateDataLastUpdateDate($subscriber);
            } elseif (
                $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED
                && $this->isSubscriberStatusChanged($subscriber)
            ) {
                $this->updateUnjoinDate($subscriber, false);

                // A rare case: when unsubscribing a customer in back office with updates
                if ($this->customerHasDataChanged($subscriber->getId())) {
                    $this->_updateDataLastUpdateDate($subscriber);
                }
            }
        }
    }

    /**
     * Check whether newsletter subscriber status has been changed
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return boolean
     */
    public function isSubscriberStatusChanged(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        $changed = false;
        if ($subscriber->getSubscriberStatus() != $subscriber->getOrigData('subscriber_status')) {
            $changed = true;
        }

        return $changed;
    }

    /**
     * Update date_unjoin to able filtering on subscribers collections
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param boolean                          $isRejoining
     */
    protected function updateUnjoinDate(Mage_Newsletter_Model_Subscriber $subscriber, $isRejoining = false)
    {
        // Prevent infinite loops and multiple calls on a Mage::app instance
        $this->setSubscriberSaved($subscriber->getId());

        $dateUnjoin = $this->_instanciationTime;
        if ($isRejoining) {
            $dateUnjoin = null;
        }

        $subscriber->setData('date_unjoin', $dateUnjoin);
        $subscriber->save();
    }

    /**
     * Update data_last_update_date to able filtering on subscribers collections
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     */
    protected function _updateDataLastUpdateDate(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        // Prevent infinite loops and multiple calls on a Mage::app instance
        $this->setSubscriberSaved($subscriber->getId());

        $subscriber->setData('data_last_update_date', $this->_instanciationTime);
        $subscriber->save();
    }
}

