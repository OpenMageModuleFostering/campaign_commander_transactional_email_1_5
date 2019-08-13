<?php
/**
 * Member service - Handle Member Data
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_Service_Member extends Emv_Core_Model_Service_Member
{
    protected $_subscribersIdsUpdated = array();
    protected $_subscribersIdsUnjoined = array();
    protected $_subscribersIdsRejoined = array();

    protected $_errors = array();

    /**
     * This method will get subscribers that require data update to EmailVision platform
     * and manage the api calls in order to insert or update the data
     *
     * @return Array $_subscribersIdsUpdated    An array contains the subscribers ids updated with success
     */
    public function exportSubscribers()
    {
        $size = false;
        try {
            // Subscribers that never have been sync || that have been update more recently than their last sync
            $subscribers = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToFilter('main_table.' . Emv_DataSync_Helper_Service::FIELD_MEMBER_LAST_UPDATE,
                   array(
                       array('null'=> true),
                       array('lt'=> new Zend_Db_Expr('main_table.' . Emv_DataSync_Helper_Service::FIELD_DATA_LAST_UPDATE))
                   )
                )
                ->addFieldToFilter(
                    'main_table.subscriber_status',
                    array(
                        array('eq' => Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
                    )
                );
            $size = $subscribers->getSize();
            if ($size) {
                // Add all customer and address attributes that have been mapped into collection
                Mage::helper('emvdatasync/service')->addCustomerAndAddressAttributes($subscribers);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_errors[] = 'Exception while retreiving data for subscribers to insert or update with message:        ' . $e->getMessage();
        }

        if ($size) {
            foreach ($subscribers as $subscriber) {
                try {
                    $this->_insertOrUpdateMember($subscriber);
                } catch (Exception $e) {
                    Mage::logException($e);

                    $this->_errors[] = 'Exception while preparing data to call insertOrUpdateMemberByObj, for subscriber '
                        . $subscriber->getId() . ' with message:        ' . $e->getMessage();
                }
            }
        }

        return $this->_subscribersIdsUpdated;
    }

    /**
     * Get merge criteria for member api.
     * It can be email or entity id
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return array
     */
    public function getMergeCriteriaForMember(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        if (Mage::helper('emvdatasync')->getEmailEnabled()) {
            $mergeCriteria = array(strtoupper(Emv_Core_Model_Service_Member::FIELD_EMAIL) => $subscriber->getEmail());
        } else {
            $mergeCriteria = array(
                strtoupper(Mage::helper('emvdatasync')->getMappedEntityId()) => $subscriber->getId()
            );
        }
        return $mergeCriteria;
    }


    /**
     * Method to call insert or update on subscriber passed as param
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber    Subscriber to insert
     * @param boolean $closeApi
     *
     * @return string | boolean $result    The soap call result
     */
    protected function _insertOrUpdateMember(Mage_Newsletter_Model_Subscriber $subscriber, $closeApi = false)
    {
        $params = array();

        // Replace customer email by suscriber one
        $subscriber->setData('email', $subscriber->getSubscriberEmail());

        // retreive all maped attribute values from subscriber, build them into array
        $entityFieldsToSelect = Mage::helper('emvdatasync/service')->prepareAndGetMappedCustomerAttributes();
        if ($entityFieldsToSelect && $entityFieldsToSelect->count() > 0) {
            // get list of all available stores
            $stores = Mage::app()->getStores(true);

            foreach($entityFieldsToSelect as $attribute) {
                $emailVisionKey = $attribute->getEmailVisionKey();
                $emailVisionKey = strtoupper($emailVisionKey);

                $fieldCode  = ($attribute->getFinalAttributeCode())
                    ? $attribute->getFinalAttributeCode() : $attribute->getAttributeCode();

                $fieldValue = '';

                if ($attribute->getFrontendInput() == 'date') {
                    if ($subscriber->getData($fieldCode)) {
                        // date time should be in EmailVision format
                        $fieldValue = Mage::helper('emvdatasync/service')
                            ->getEmailVisionDate($subscriber->getData($fieldCode));
                    }
                } else {
                    $fieldValue = $subscriber->getData($fieldCode);
                    if ($fieldCode == 'store_id' && isset($stores[$fieldValue])) {
                        // get store code
                        $fieldValue = $stores[$fieldValue]->getCode();
                    }
                }

                $params[$emailVisionKey] = $fieldValue;
            }

            // entity id
            $params[strtoupper(Mage::helper('emvdatasync')->getMappedEntityId())] = $subscriber->getId();
        }

        // make api call with all prepared array (mapped attributes and criteria)
        try {
            $uploadId = null;
            $service = $this->getApiService($this->getAccount());
            $uploadId = $service->insertOrUpdateMemberByObj(
                $this->getMergeCriteriaForMember($subscriber),
                $params,
                $closeApi
            );

            $this->_subscribersIdsUpdated[] = $subscriber->getId();
        } catch (Exception $e) {
            Mage::logException($e);

            $this->_errors[] = 'Exception while calling insertOrUpdateMemberByObj for subscriber '
                . $subscriber->getId() . ' with message:        ' . $e->getMessage();
        }

        return $uploadId;
    }

    /**
     * Unjoin all  unsubscribed members from EmailVision platform
     *
     * @param boolean $closeApi
     * @return Array $_subscribersIdsUnjoined    An array containing the subscribers ids unjoined with success
     */
    public function unjoinSubscribers()
    {
        try {
            // Subscribers that have unjoined more recently than their last sync
            $subscribers = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter(
                'main_table.' . Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN,
                array(
                    array('gt' => new Zend_Db_Expr('main_table.' . Emv_DataSync_Helper_Service::FIELD_MEMBER_LAST_UPDATE))
                )
            )
            ->addFieldToFilter(
                'main_table.subscriber_status',
                array(
                    array('eq' => Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED),
                )
            );
            $subscribers->load();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_errors[] = 'Exception while retreiving data for subscribers to unjoin with message:        ' . $e->getMessage();
        }

        foreach ($subscribers as $subscriber) {
            try {
                $this->unjoinOneSubscriber($subscriber, false);

                $this->_subscribersIdsUnjoined[] = $subscriber->getId();
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_errors[] = 'Exception while preparing data to call unjoin, for subscriber '
                    . $subscriber->getId() . " with message:        " . $e->getMessage();
            }
        }

        return $this->_subscribersIdsUnjoined;
    }

    /**
     * Unjoin member from EmailVision platform
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param boolean $closeApi
     * @return Emv_DataSync_Model_Service_Member
     */
    public function unjoinOneSubscriber(Mage_Newsletter_Model_Subscriber $subscriber, $closeApi = true)
    {
        if ($subscriber->getId()) {
            $service = $this->getApiService($this->getAccount());
            $service->unjoinMember('object', $this->getMergeCriteriaForMember($subscriber), $closeApi);
        }

        return $this;
    }

    /**
     * Rejoin subscribers to EmailVision platform
     *  (subscribers that change their status from unsubscribed to subscribe)
     *
     * @param boolean $closeApi
     * @return Array $_subscribersIdsRejoined    An array containing the subscribers ids rejoined with success
     */
    public function rejoinSubscribers($closeApi = false)
    {
        try {
            // Subscribers which have last sync more recently than unjoin && that are subscribed
            $subscribers = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter(
                'main_table.' . Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN, array(
                    array('lteq'=> new Zend_Db_Expr('main_table.' .  Emv_DataSync_Helper_Service::FIELD_DATA_LAST_UPDATE)),
            ))
            ->addFieldToFilter(
                'main_table.subscriber_status', array(
                    array('eq'=> Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED),
            ));

            $subscribers->load();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_errors[] = 'Exception while retreiving data for subscribers to rejoin with message:        ' . $e->getMessage();
        }

        $service = $this->getApiService($this->getAccount());

        foreach ($subscribers as $subscriber) {
            try {
                $memberId = false;

                $list = $service->getListMembersByObj(
                    $this->getMergeCriteriaForMember($subscriber),
                    $closeApi
                );
                if (count($list) == 1) {
                    foreach ($list[0] as $key => $value) {
                        if ($key == Emv_Core_Model_Service_Member::FIELD_MEMBER_ID) {
                            $memberId = $value;
                        }
                    }
                }
                if ($memberId) {
                    $service->rejoinMember('id', $memberId, $closeApi);
                    $this->_subscribersIdsRejoined[]  = $subscriber->getId();
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_errors[] = 'Exception while preparing data to call rejoin, for subscriber '
                    . $subscriber->getId() . ' with message:        ' . $e->getMessage();
            }
        }

        return $this->_subscribersIdsRejoined;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Method to set massively the custom member_last_update_date after a member or a batchmember export
     */
    public function massSetMemberLastUpdateDate()
    {
        $subscribersIds = array();
        foreach ($this->_subscribersIdsUpdated as $subscriberId) {
            $subscribersIds[] = $subscriberId;
        }
        foreach ($this->_subscribersIdsUnjoined as $subscriberId) {
            $subscribersIds[] = $subscriberId;
        }
        foreach ($this->_subscribersIdsRejoined as $subscriberId) {
            $subscribersIds[] = $subscriberId;
        }
        $subscribersIds = array_unique($subscribersIds);

        try {
            $errorMessage = Mage::helper('emvdatasync/service')->massSetMemberLastUpdateDate(
                $subscribersIds,
                $this->_subscribersIdsRejoined
            );
            if ($errorMessage !== true) {
                $this->_errors[] = $errorMessage;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Trigger all process to update subscriber data (insert update, unjoin and rejoin)
     */
    public function triggerExport()
    {
        $this->exportSubscribers();
        $this->unjoinSubscribers();
        $this->rejoinSubscribers();

        // Update memberLastUpdateDate
        $this->massSetMemberLastUpdateDate();

        try {
            $this->getApiService($this->getAccount())->closeApiConnection();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_errors[] = "Exception while closing SmartFocus service with message :         "
                . $e->getMessage();
        }
    }
}