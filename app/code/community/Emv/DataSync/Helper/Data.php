<?php
/**
 * Data helper for Data Sync module
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_EMAILVISION_FIELDS             = 'emvdatasync/customer_mapping/emailvision_fields';
    const XML_PATH_MAPPED_ENTITY_ID               = 'emvdatasync/customer_mapping/emailvision_entity_id';
    const XML_PATH_EMAIL_SYNC                     = 'emvdatasync/customer_mapping/email_enabled';

    const XML_PATH_ACCOUNT_FOR_MEMBER             = 'emvdatasync/apimember/account';
    const XML_PATH_ACCOUNT_FOR_BATCH_MEMBER       = 'emvdatasync/batchmember/account';
    const XML_PATH_CUSTOMER_MAPPING_ATTRIBUTES    = 'emvdatasync/customer_mapping/attributes';

    /**
     * Is created folder for export data
     * @var Boolean
     */
    protected $_createdFolder = false;

    /**
     * Lock file name pattern
     */
    const LOCK_FILE_NAME_PATTERN = 'cron_process.lock';

    /**
     * Check and create need folders
     *
     * @return boolean
     */
    public function checkAndCreatFolder()
    {
        if ($this->_createdFolder == false) {
            $mageFile = new Varien_Io_File();
            $mageFile->checkAndCreateFolder(Mage::getBaseDir('export'));
            $mageFile->checkAndCreateFolder(Mage::getBaseDir('export'). DS . 'emailvision');
            $mageFile->checkAndCreateFolder(Mage::getBaseDir('export'). DS . 'emailvision' . DS . 'uploaded');
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
        $config = Mage::getModel('core/config');
        $config->saveConfig(
            self::XML_PATH_EMAILVISION_FIELDS, Mage::helper('core')->jsonEncode($fields)
        );
        return $this;
    }

    /**
     * @return array
     */
    public function getEmailVisionFieldsFromConfig()
    {
        $fields = array();
        $encodedString = Mage::getStoreConfig(self::XML_PATH_EMAILVISION_FIELDS);
        if ($encodedString) {
            $fields = Mage::helper('core')->jsonDecode($encodedString);
        }
        return $fields;
    }

    /**
     * Save mapped EmailVision Entity Id
     *
     * @param string $field
     * @return Emv_DataSync_Helper_Data
     */
    public function saveMappedEntityId($field)
    {
        $config = Mage::getModel('core/config');
        $config->saveConfig(self::XML_PATH_MAPPED_ENTITY_ID, $field);
        return $this;
    }

    /**
     * @return string
     */
    public function getMappedEntityId()
    {
        return Mage::getStoreConfig(self::XML_PATH_MAPPED_ENTITY_ID);
    }

    /**
     * @param string $type
     * @param string $storeId
     * @return Emv_Core_Model_Account
     */
    public function getEmvAccountForStore($type = "member", $storeId = null)
    {
        $path = self::XML_PATH_ACCOUNT_FOR_MEMBER;
        if ($type == 'batch') {
            $path = self::XML_PATH_ACCOUNT_FOR_BATCH_MEMBER;
        }

        $accountId = Mage::getStoreConfig($path, $storeId);
        $account = Mage::getModel('emvcore/account');
        $account->load($accountId);

        return $account;
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
}