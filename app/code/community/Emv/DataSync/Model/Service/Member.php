<?php
/**
 * Member service - Handle Member Data
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_Service_Member extends Emv_Core_Model_Service_Member
{
    protected $_errors = array();

    /**
     * This method will get subscribers that require data update to EmailVision platform
     * and manage the api calls in order to insert or update the data
     *
     * @param EmailVision_Api_MemberService $service
     * @param array $storeIds
     * @param string $currentStore
     * @param boolean $closeApi
     * @return Array - An array contains the subscribers ids updated with success
     */
    public function exportSubscribers(EmailVision_Api_MemberService $service, array $storeIds = array(), $currentStore = null)
    {
        $size = false;
        $updatedSubscribers = array();
        try {
            // Subscribers that never have been sync || that have been update more recently than their last sync
            $subscribers = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToFilter(
                    'main_table.subscriber_status',
                    array(
                        array('eq' => Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
                    )
                );

            // only get subscirbers that have been scheduled
            $subscribers->addFieldToFilter(
                'main_table.' . Emv_DataSync_Helper_Service::FIELD_QUEUED,
                Emv_DataSync_Helper_Service::SCHEDULED_VALUE
            );

            if (count($storeIds)) {
                $subscribers->addFieldToFilter('main_table.store_id', array('in' => $storeIds));
            }

            $size = $subscribers->getSize();
            if ($size) {
                // Add all customer and address attributes that have been mapped into collection
                Mage::getSingleton('emvdatasync/attributeProcessing_config')
                    ->prepareSubscriberCollection($subscribers, $currentStore);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_errors[] = 'Exception while retreiving data for subscribers to insert or update with message:        ' . $e->getMessage();
        }

        if ($size) {
            foreach ($subscribers as $subscriber) {
                try {
                    $ok = $this->_insertOrUpdateMember($subscriber, $service, $currentStore);
                    if ($ok) {
                        $updatedSubscribers[] = $subscriber->getId();
                    }
                } catch (Exception $e) {
                    Mage::logException($e);

                    $this->_errors[] = 'Exception while preparing data to call insertOrUpdateMemberByObj, for subscriber '
                        . $subscriber->getId() . ' with message:        ' . $e->getMessage();
                }
            }
        }

        return $updatedSubscribers;
    }

    /**
     * Get merge criteria for member api.
     * It can be email or entity id
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return array
     */
    public function getMergeCriteriaForMember(Mage_Newsletter_Model_Subscriber $subscriber, $storeId = null)
    {
        if (Mage::helper('emvdatasync')->getEmailEnabled($storeId)) {
            $mergeCriteria = array(strtoupper(Emv_Core_Model_Service_Member::FIELD_EMAIL) => $subscriber->getEmail());
        } else {
            $mergeCriteria = array(
                strtoupper(Mage::helper('emvdatasync')->getMappedEntityId($storeId)) => $subscriber->getId()
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
    protected function _insertOrUpdateMember(
        Mage_Newsletter_Model_Subscriber $subscriber,
        EmailVision_Api_MemberService $service,
        $storeId = null,
        $closeApi = false
    )
    {
        // Replace customer email by suscriber one
        $subscriber->setData('email', $subscriber->getSubscriberEmail());

        $params = Mage::getSingleton('emvdatasync/attributeProcessing_config')
            ->getSubscriberData($subscriber, $storeId);

        // make api call with all prepared array (mapped attributes and criteria)
        try {
            $uploadId = null;
            $uploadId = $service->insertOrUpdateMemberByObj(
                $this->getMergeCriteriaForMember($subscriber, $storeId),
                $params,
                $closeApi
            );
        } catch (Exception $e) {
            Mage::logException($e);

            $this->_errors[] = 'Exception while calling insertOrUpdateMemberByObj for subscriber '
                . $subscriber->getId() . ' with message:        ' . $e->getMessage();
        }

        return $uploadId;
    }

    /**
     * Unjoin all  unsubscribed members from SmartFocus platform
     *
     * @param EmailVision_Api_MemberService $service
     * @param array $storeIds
     * @param string $currentStore
     * @param boolean $closeApi
     * @return Array - An array containing the subscribers ids unjoined with success
     */
    public function unjoinSubscribers(EmailVision_Api_MemberService $service,
        array $storeIds = array(),
        $currentStore = null
    )
    {
        $unjoinedSubscribers = array();
        try {
            // Subscribers that have unjoined more recently than their last sync
            $subscribers = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToFilter(
                    'main_table.subscriber_status',
                    array(
                        array('eq' => Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED),
                    )
                );
            // only get subscirbers that have been scheduled
            $subscribers->addFieldToFilter(
                'main_table.' . Emv_DataSync_Helper_Service::FIELD_QUEUED,
                Emv_DataSync_Helper_Service::SCHEDULED_VALUE
            );

            if (count($storeIds)) {
                $subscribers->addFieldToFilter('main_table.store_id', array('in' => $storeIds));
            }

        } catch (Exception $e) {
            Mage::logException($e);
            $this->_errors[] = 'Exception while retreiving data for subscribers to unjoin with message:        ' . $e->getMessage();
        }

        foreach ($subscribers as $subscriber) {
            try {
                $this->unjoinOneSubscriber($subscriber,$service, $currentStore, false);

                $unjoinedSubscribers[] = $subscriber->getId();
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_errors[] = 'Exception while preparing data to call unjoin, for subscriber '
                    . $subscriber->getId() . " with message:        " . $e->getMessage();
            }
        }

        return $unjoinedSubscribers;
    }

    /**
     * Unjoin member from EmailVision platform
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param EmailVision_Api_MemberService $service
     * @param string $currentStore
     * @param boolean $closeApi
     * @return Emv_DataSync_Model_Service_Member
     */
    public function unjoinOneSubscriber(Mage_Newsletter_Model_Subscriber $subscriber,
        EmailVision_Api_MemberService $service,
        $currentStore = null,
        $closeApi = true
    )
    {
        if ($subscriber->getId()) {
            $service->unjoinMember('object', $this->getMergeCriteriaForMember($subscriber, $currentStore), $closeApi);
        }

        return $this;
    }

    /**
     * Rejoin subscribers to EmailVision platform
     *  (subscribers that change their status from unsubscribed to subscribe)
     * @param EmailVision_Api_MemberService $service
     * @param array $storeIds
     * @param boolean $closeApi
     * @return Array - An array containing the subscribers ids rejoined with success
     */
    public function rejoinSubscribers(EmailVision_Api_MemberService $service,
        array $storeIds = array(),
        $closeApi = false
    )
    {
        $rejoinedSubscribers = array();
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

            // only get subscirbers that have been scheduled
            $subscribers->addFieldToFilter(
                'main_table.' . Emv_DataSync_Helper_Service::FIELD_QUEUED,
                Emv_DataSync_Helper_Service::SCHEDULED_VALUE
            );

            if (count($storeIds)) {
                $subscribers->addFieldToFilter('main_table.store_id', array('in' => $storeIds));
            }

        } catch (Exception $e) {
            Mage::logException($e);
            $this->_errors[] = 'Exception while retreiving data for subscribers to rejoin with message:        ' . $e->getMessage();
        }

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
                    $rejoinedSubscribers[]  = $subscriber->getId();
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_errors[] = 'Exception while preparing data to call rejoin, for subscriber '
                    . $subscriber->getId() . ' with message:        ' . $e->getMessage();
            }
        }

        return $rejoinedSubscribers;
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
    public function massSetMemberLastUpdateDate(
        array $updatedSubscribers,
        array $unjoinedSubscribers,
        array $rejoinedSubscribers
    )
    {
        $subscribersIds = array();
        foreach ($updatedSubscribers as $subscriberId) {
            $subscribersIds[] = $subscriberId;
        }
        foreach ($unjoinedSubscribers as $subscriberId) {
            $subscribersIds[] = $subscriberId;
        }
        foreach ($rejoinedSubscribers as $subscriberId) {
            $subscribersIds[] = $subscriberId;
        }
        $subscribersIds = array_unique($subscribersIds);

        try {
            $errorMessage = Mage::helper('emvdatasync/service')->massSetMemberLastUpdateDate(
                $subscribersIds,
                $rejoinedSubscribers
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
        $accounts = Mage::helper('emvdatasync')->getActiveEmvAccountsForStore(Emv_DataSync_Helper_Data::TYPE_MEMBER);

        foreach ($accounts as $accountId => $accountData)
        {
            $storeIds = $accountData['stores'];
            $account  = $accountData['model'];
            $service  = false;
            $currentStore = $storeIds[0];

            try {
                // Get api service corresponding to current account
                $service = $this->getApiService($account, $storeIds[0]);
            } catch (Exception $e) {
                $this->_errors[] = "The account {$account->getName()} is invalid : " . $e->getMessage();
            }

            if ($service) {
                $updatedSubscribers  = $this->exportSubscribers($service, $storeIds, $currentStore);
                $unjoinedSubscribers = $this->unjoinSubscribers($service, $storeIds, $currentStore);
                $rejoinedSubscribers = $this->rejoinSubscribers($service, $storeIds);

                // Update memberLastUpdateDate
                $this->massSetMemberLastUpdateDate($updatedSubscribers, $unjoinedSubscribers, $rejoinedSubscribers);

                try {
                    $service->closeApiConnection();
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_errors[] = "Exception while closing SmartFocus service for account {{$account->getName()}} with message :         "
                        . $e->getMessage();
                }
            }
        }
    }
}