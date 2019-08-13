<?php
/**
 * Observe subscriber save to set its last update date and call the api method to update it's changes
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Observer
{
    /**
     * Instanciation Date/time
     * @var string
     */
    protected $_instanciationTime;

    /**
     * List of customers that have data changes - use subscriber id as identifiant
     * @var array
     */
    protected $_customerHasDataChanged = array();

    /**
     * List of subscribers that have been saved
     * @var array
     */
    protected $_subscriberSaved        = array();

    /**
     * List of loaded subscribers (sorted by customers)
     *
     * @var array
     */
    protected $_customerNewsletter = array();

    /**
     * @var array
     */
    protected $_customerHasSavedOrder = array();

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
        if (Mage::getStoreConfigFlag(Emv_DataSync_Helper_Data::XML_PATH_ENABLED_FOR_MEMBER)) {
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
        $customerAddress = $observer->getCustomerAddress();

        // Proceed data if address has data changes
        if ($customerAddress->hasDataChanges()) {
            $customer   = $customerAddress->getCustomer();
            $subscriber = $this->getSubscriberFromCustomer($customer);

            // If a subscriber is linked to this address' customer, update last data changes date
            if ($subscriber && $subscriber->getId()) {
                // Prevent multiple calls on a Mage::app instance
                if (!$this->subscriberSaved($subscriber->getId())) {
                    $this->setCustomerHasDataChanged($subscriber->getId());
                    $this->handleSubscriberInformation($subscriber);
                }
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
                if ($customer->hasDataChanges()) {
                    $this->setCustomerHasDataChanged($subscriber->getId());
                    $this->handleSubscriberInformation($subscriber);
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
     * Method called on subscriber save.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onSubscriberSave(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getSubscriber();

        $this->handleSubscriberInformation($subscriber);
    }

    /**
     * Update the subscriber information according to the changes
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return boolean
     */
    public function handleSubscriberInformation(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        if (!$subscriber->getId()) {
            return false;
        }

        // Prevent multiple calls on a Mage::app instance
        if (!$this->subscriberSaved($subscriber->getId())) {
            // if the subscriber was just created
            if ($subscriber->getCustomerId() && $subscriber->isObjectNew()) {
                try {
                    if (Mage::helper('emvdatasync')->doesCustomerHaveOrder($subscriber->getCustomerId())) {
                        $subscriber->setData(
                            Emv_DataSync_Helper_Service::FIELD_PURCHASE_LAST_UPDATE,
                            $this->_instanciationTime
                        );
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

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
    public function updateUnjoinDate(Mage_Newsletter_Model_Subscriber $subscriber, $isRejoining = false)
    {
        // Prevent infinite loops and multiple calls on a Mage::app instance
        $this->setSubscriberSaved($subscriber->getId());

        $dateUnjoin = $this->_instanciationTime;
        if ($isRejoining) {
            $dateUnjoin = null;
        }

        $subscriber->setData(Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN, $dateUnjoin);
        $subscriber->setData(Emv_DataSync_Helper_Service::FIELD_QUEUED, Emv_DataSync_Helper_Service::SCHEDULED_VALUE);
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

        $subscriber->setData(Emv_DataSync_Helper_Service::FIELD_DATA_LAST_UPDATE, $this->_instanciationTime);
        $subscriber->setData(Emv_DataSync_Helper_Service::FIELD_QUEUED, Emv_DataSync_Helper_Service::SCHEDULED_VALUE);
        $subscriber->save();
    }

    /**
     * Observer on order save after event, update subscriber information according to the changes
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleOrderSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer instanceof Mage_Customer_Model_Customer && $customer->getId()) {
            $subscriber = $this->getSubscriberFromCustomer($customer);
            if ($subscriber && $subscriber->getId() && !isset($this->_customerHasSavedOrder[$subscriber->getId()])) {
                $subscriber->setData(
                    Emv_DataSync_Helper_Service::FIELD_PURCHASE_LAST_UPDATE,
                    $this->_instanciationTime
                );
                $subscriber->setData(
                    Emv_DataSync_Helper_Service::FIELD_QUEUED,
                    Emv_DataSync_Helper_Service::SCHEDULED_VALUE
                );
                $subscriber->save();

                $this->_customerHasSavedOrder[$subscriber->getId()] = true;
            }
        }
    }
}

