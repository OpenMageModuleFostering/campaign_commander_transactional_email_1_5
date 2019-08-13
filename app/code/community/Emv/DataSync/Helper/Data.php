<?php
/**
 * Data helper for Data Sync module
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_EMAILVISION_FIELDS             = 'emvdatasync/customer_mapping/emailvision_fields';
    const XML_PATH_MAPPED_ENTITY_ID               = 'emvdatasync/customer_mapping/emailvision_entity_id';
    const XML_PATH_EMAIL_SYNC                     = 'emvdatasync/customer_mapping/email_enabled';

    const XML_PATH_ACCOUNT_FOR_MEMBER             = 'emvdatasync/apimember/account';
    const XML_PATH_ENABLED_FOR_MEMBER             = 'emvdatasync/apimember/enabled';

    const XML_PATH_ACCOUNT_FOR_BATCH_MEMBER       = 'emvdatasync/batchmember/account';
    const XML_PATH_ENABLED_FOR_BATCH_MEMBER       = 'emvdatasync/batchmember/enabled';

    const XML_PATH_CUSTOMER_MAPPING_ATTRIBUTES    = 'emvdatasync/customer_mapping/attributes';
    const XML_PATH_ALLOWED_NUMBER_TEST            = 'emvdatasync/general/test_members';

    const TYPE_BATCH = 'batch';
    const TYPE_MEMBER = 'member';

    /**
     * Is created folder for export data
     * @var Boolean
     */
    protected $_createdFolder = false;

    /**
     * Lock file name pattern
     */
    const LOCK_FILE_NAME_PATTERN = 'data_sync_process';

    /**
     * Array of accounts sorted by stores
     *
     * @var array
     */
    protected $_storesAndAccounts = array();

    /**
     * Check and create need folders
     *
     * @return boolean
     */
    public function checkAndCreatFolder()
    {
        if ($this->_createdFolder == false) {
            $mageFile = new Varien_Io_File();
            // check and create base container dir
            $mageFile->checkAndCreateFolder(Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER));

            // check and create base working dir
            $mageFile->checkAndCreateFolder(
                Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
            );

            // check and create uploaded dir
            $mageFile->checkAndCreateFolder(
                Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
                . DS . Emv_Core_Helper_Data::UPLOADED_FILE_DIR
            );

            $this->_createdFolder = true;
        }
    }

    /**
     * Check if lock file exists
     *
     * @return boolean
     */
    public function checkLockFile()
    {
        return Mage::helper('emvcore')->checkLockFile(self::LOCK_FILE_NAME_PATTERN);
    }

    /**
     * Create a lock file
     *
     * @param string $content
     * @return mutilple <number, boolean>
     */
    public function createLockFile($content = '')
    {
        return Mage::helper('emvcore')->createLockFile(self::LOCK_FILE_NAME_PATTERN, $content);
    }

    /**
     * Remove a lock file
     *
     * @param string $content
     * @return boolean
     */
    public function removeLockFile($content = '')
    {
        return Mage::helper('emvcore')->removeLockFile(self::LOCK_FILE_NAME_PATTERN);
    }

    /**
     * Get formatted date time in the same store timezone
     * in the following format : Y-m-d H:i:s (such as 2013-09-10 14:31:24)
     *
     * @param string
     */
    public function getDateTime($timestamp = null, $storeId = null)
    {
        if ($storeId !== null) {
            $date = Mage::app()->getLocale()->storeDate($storeId, null, true);
            $formatedDate = $date->toString('Y-M-d HH:mm:ss');

            return $formatedDate;
        }

        return Mage::getModel('core/date')->date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Get GMT date time in the following format : Y-m-d H:i:s (such as 2013-09-10 14:31:24)
     *
     * @param string
     */
    public function getFormattedGmtDateTime($timestamp = null)
    {
        return Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s', $timestamp);
    }

    /**
     * @param array $fields
     * @return Emv_DataSync_Helper_Data
     */
    public function saveEmailVisionFieldsInConfig($fields = array())
    {
        $scopeData = Mage::helper('emvcore')->getAdminScope();

        $config = Mage::getModel('core/config');
        $config->saveConfig(
            self::XML_PATH_EMAILVISION_FIELDS,
            Mage::helper('core')->jsonEncode($fields),
            $scopeData['scope'],
            $scopeData['scope_id']
        );

        return $this;
    }

    /**
     * Get SmartFocus fields from configuration
     *
     * @return array
     */
    public function getEmailVisionFieldsFromConfig($fromAdminScopeConfig = true, $storeId = null)
    {
        $fields = array();

        if ($fromAdminScopeConfig) {
            $encodedString = Mage::helper('emvcore')->getAdminScopedConfig(self::XML_PATH_EMAILVISION_FIELDS);
        } else {
            $encodedString = Mage::getStoreConfig(self::XML_PATH_EMAILVISION_FIELDS, $storeId);
        }

        if ($encodedString) {
            // the fields are serialized in JSON format
            $fields = Mage::helper('core')->jsonDecode($encodedString);
        }
        return $fields;
    }

    /**
     * Get mapped entity id
     *
     * @param string $storeId
     * @return string
     */
    public function getMappedEntityId($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_MAPPED_ENTITY_ID, $storeId);
    }

    /**
     * Get only activate account for store (if enabled is set to yes) for a type
     *
     * @param string $type
     * @param string $storeId
     * @return Emv_Core_Model_Account
     */
    public function getEmvAccountForStore($type = self::TYPE_MEMBER, $storeId = null)
    {
        $path = self::XML_PATH_ACCOUNT_FOR_MEMBER;
        $enabledPath = self::XML_PATH_ENABLED_FOR_MEMBER;
        if ($type == 'batch') {
            $path = self::XML_PATH_ACCOUNT_FOR_BATCH_MEMBER;
            $enabledPath = self::XML_PATH_ENABLED_FOR_BATCH_MEMBER;
        }

        $account = Mage::getModel('emvcore/account');
        if (Mage::getStoreConfig($enabledPath, $storeId)) {
            $accountId = Mage::getStoreConfig($path, $storeId);
            $account->load($accountId);
        }

        return $account;
    }

    /**
     * Get all active SmartFocus accounts sorted by stores for a type
     *
     * @param string $type (member or batch)
     * @return array
     */
    public function getActiveEmvAccountsForStore($type = self::TYPE_MEMBER)
    {
        if (!isset($this->_storesAndAccounts[$type])) {
            // init array
            $this->_storesAndAccounts[$type] = array();

            // get XML path
            $path = self::XML_PATH_ACCOUNT_FOR_MEMBER;
            $enabledPath = self::XML_PATH_ENABLED_FOR_MEMBER;
            if ($type == 'batch') {
                $path = self::XML_PATH_ACCOUNT_FOR_BATCH_MEMBER;
                $enabledPath = self::XML_PATH_ENABLED_FOR_BATCH_MEMBER;
            }

            // also get admin store
            $stores = Mage::app()->getStores(true);
            $storeIds = array_keys($stores);
            $treatedAccounts = array();

            foreach ($storeIds as $storeId) {
                // check if this account is enabled
                if (Mage::getStoreConfig($enabledPath, $storeId)) {

                    $accountId = Mage::getStoreConfig($path, $storeId);
                    if (!isset($treatedAccounts[$accountId])) {
                        $accountId = Mage::getStoreConfig($path, $storeId);
                        $account = Mage::getModel('emvcore/account');
                        $account->load($accountId);
                        if ($account->getId() && $account->getId() == $accountId) {
                            $this->_storesAndAccounts[$type][$accountId] = array(
                                    'model' => $account, 'stores' => array($storeId)
                                );

                            $treatedAccounts[$accountId] = true;
                        } else {
                            $treatedAccounts[$accountId] = false;
                        }
                    } else {
                        if ($treatedAccounts[$accountId]) {
                            $this->_storesAndAccounts[$type][$accountId]['stores'][] = $storeId;
                        }
                    }
                }
            }
        }

        return $this->_storesAndAccounts[$type];
    }

    /**
     * Get mapped attributes from config
     *
     * @param string $storeId
     * @return array
     */
    public function getEmvMappedCustomerAttributes($storeId = null)
    {
        return unserialize(Mage::getStoreConfig(self::XML_PATH_CUSTOMER_MAPPING_ATTRIBUTES, $storeId));
    }

    /**
     * Is email used to sync subscribers?
     *
     * @param string $storeId
     * @return boolean
     */
    public function getEmailEnabled($storeId = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_EMAIL_SYNC, $storeId);
    }

    /**
     * Mass schedule a list of subscribers
     *
     * @param array $subscriberIds
     * @param string $scheduled (take 2 values
     *     Emv_DataSync_Helper_Service::SCHEDULED_VALUE,
     *     Emv_DataSync_Helper_Service::NOT_SCHEDULED_VALUE)
     * @return true
     * @throws Mage_Core_Exception
     */
    public function massScheduleSubscriber($subscriberIds, $scheduled = Emv_DataSync_Helper_Service::SCHEDULED_VALUE)
    {
        if (is_array($subscriberIds) && count($subscriberIds)) {
            $resource = Mage::getModel('core/resource');

            $writeConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
            $condition = $writeConnection->quoteInto('IN (?)', $subscriberIds);
            $queuedField = Emv_DataSync_Helper_Service::FIELD_QUEUED;
            $writeConnection->query("
                UPDATE {$resource->getTableName('newsletter/subscriber')}
                SET {$queuedField} = {$scheduled}
                WHERE subscriber_id {$condition}
            ");
        } else {
            Mage::throwException(Mage::helper('newsletter')->__('Please select subscriber(s)'));
        }

        return true;
    }

    /**
     * Set last update purchase data for a given subscriber
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param string $time - GMT time
     */
    public function setLastUpdatePurchaseDate(Mage_Newsletter_Model_Subscriber $subscriber, $time)
    {
        if ($subscriber->getCustomerId() && $subscriber->getId()) {
            $resource = Mage::getModel('core/resource');
            $writeConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
            $setValue = $writeConnection->quoteInto('date_last_purchase = ?', $time);

            try {
                $writeConnection->query("
                    UPDATE {$resource->getTableName('newsletter/subscriber')} subscriber
                    JOIN {$resource->getTableName('sales/order')} flat_order
                        ON subscriber.customer_id = flat_order.customer_id
                    SET {$setValue}
                ");
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Does a given customer have any order ?
     *
     * @param int $customerId
     * @return boolean
     * @throws Exception $e
     */
    public function doesCustomerHaveOrder($customerId)
    {
        $haveOrder = false;
        if ($customerId && (int)$customerId > 0) {
            $resource       = Mage::getModel('core/resource');
            $readConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
            $select = $readConnection->select();

            $select
                ->from(array('main_table' => $resource->getTableName('sales/order')), array('COUNT(*)'))
                ->where("customer_id = ?", $customerId);

            $orderCount = $readConnection->fetchCol($select);
            if (is_array($orderCount) &&  count($orderCount) && $orderCount[0] > 0) {
                $haveOrder = true;
            }
        }

        return $haveOrder;
    }

}