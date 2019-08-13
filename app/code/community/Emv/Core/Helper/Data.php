<?php
/**
 * Emv core helper
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Gets the detailed Extension version information
     *
     * @return array
     */
    public static function getVersionInfo()
    {
        return array(
            'major'     => '3',
            'minor'     => '1',
            'revision'  => '0',
            'patch'     => '',
            'stability' => '',
            'number'    => '',
        );
    }

    /**
     * Gets the current Extension version string
     *
     * @return string
     */
    public static function getVersion()
    {
        $i = self::getVersionInfo();
        return trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "")
                        . "-{$i['stability']}{$i['number']}", '.-');
    }

    /**
     * Different constant relative to working directory path
     */
    const BASE_CONTAINER    = 'export';
    const BASE_WORKING_DIR  = 'emailvision';
    const UPLOADED_FILE_DIR = 'uploaded';
    const TEMPORARY_DIR     = 'tmp';
    const LOCK_DIR          = 'locks';

    /**
     * List of accounts
     * @var array
     */
    protected $_accountList = array();

    /**
     * Custom error handler
     */
    const CUSTOM_ERROR_HANDLER = 'smartFocusErrorHandler';

    /**
     * Custom shutdown handler
     */
    const CUSTOM_SHUTDOWN_HANDLER = 'smartFocusShutDownPhpHandler';

    /**
     * Lock file registry name
     */
    const CREATED_LOCK_FILE_REGISTRY = 'smartfocus_lock_file';

    /**
     * Get account for a given id
     *
     * @param string $id
     * @return NULL|Emv_Core_Model_Account
     */
    public function getAccount($id)
    {
        if (!$id) {
            return null;
        }

        if (!isset($this->_accountList[$id])) {
            $account = Mage::getModel('emvcore/account')->load($id);
            $this->_accountList[$id]  = $account;
        }
        return $this->_accountList[$id];
    }

    /**
     * Get lock file path directory
     * @return string
     */
    public static function getLockPathDir()
    {
        return Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
            . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR . DS . Emv_Core_Helper_Data::LOCK_DIR;
    }

    /**
     * GET Lock handler
     *
     * @return Varien_Io_File
     */
    protected function _getLockHandler()
    {
        $args = array(
            'path' => $this->getLockPathDir(),
        );
        $mageFile = new Varien_Io_File();
        $mageFile->setAllowCreateFolders(true);
        $mageFile->open($args);
        return $mageFile;
    }

    /**
     * Create a lock file. Your created lock file path can be found in Emv_Core_Helper_Data::CREATED_LOCK_FILE_REGISTRY
     *
     * @param string $content
     * @return number | boolean - false
     */
    public function createLockFile($fileName, $content = '')
    {
        if (!$content) {
            $content = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s');
        }

        $sucess = $this->_getLockHandler()->write($fileName, $content);
        if ($sucess) {
            $fullpathFileName = self::getLockPathDir()  . DS . $fileName;
            // register the created file name in order to remove after
            Mage::register(self::CREATED_LOCK_FILE_REGISTRY, $fullpathFileName);
        } else {
            Mage::unregister(self::CREATED_LOCK_FILE_REGISTRY);
        }
        return $sucess;
    }

    /**
     * Remove the last lock file from registry
     */
    public function removeLastLockFileFromRegistry()
    {
        $fullPath = Mage::registry(Emv_Core_Helper_Data::CREATED_LOCK_FILE_REGISTRY);
        if ($fullPath) {
            @unlink($fullPath);
        }
        Mage::unregister(Emv_Core_Helper_Data::CREATED_LOCK_FILE_REGISTRY);
    }

    /**
     * Remove a lock file. Empty this registry Emv_Core_Helper_Data::CREATED_LOCK_FILE_REGISTRY
     *
     * @param string $fileName
     * @return boolean
     */
    public function removeLockFile($fileName)
    {
        $sucess = $this->_getLockHandler()->rm($fileName);
        if ($sucess) {
            Mage::unregister(self::CREATED_LOCK_FILE_REGISTRY);
        };
        return $sucess;
    }

    /**
     * Check if lock file exists
     *
     * @param string $fileName
     * @return boolean
     */
    public function checkLockFile($fileName)
    {
        return $this->_getLockHandler()->fileExists($fileName, true);
    }

    /**
     * Set SmartFocus Error Handler
     *
     * @return string previous error handler
     */
    public function setSmartFocusErrorHandler()
    {
        include_once Mage::getBaseDir('code') . DS . 'community'
            . DS . 'Emv' . DS . 'Core' . DS . 'functions.php';

        // set register shutdown function in order to remove lock file
        register_shutdown_function(self::CUSTOM_SHUTDOWN_HANDLER);
        return set_error_handler(self::CUSTOM_ERROR_HANDLER);
    }

    /**
     * Reset Error Handler to Magento default one
     *
     * @return string previous error handler
     */
    public function resetErrorHandler()
    {
        // reset shut down function
        register_shutdown_function(function(){});
        return set_error_handler(Mage_Core_Model_App::DEFAULT_ERROR_HANDLER);
    }

    /**
     * @param string $path
     * @param mixed  $store
     * @param int    $websiteId
     * @return mixed
     */
    public function getAdminScopedConfig($path, $store = null, $websiteId = null)
    {
        if (!is_null($store)) {
            return Mage::getStoreConfig($path, $store);
        } elseif (!is_null($websiteId)) {
            $website = Mage::app()->getWebsite($websiteId);
            return $website->getConfig($path);
        }

        if ($storeCode = Mage::app()->getRequest()->getParam('store')) {
            $store = Mage::app()->getStore($storeCode);
            return $store->getConfig($path);
        } elseif ($websiteCode = Mage::app()->getRequest()->getParam('website')){
            $website = Mage::app()->getWebsite($websiteCode);
            return $website->getConfig($path);
        } else if ($groupCode = Mage::app()->getRequest()->getParam('group')){
            $website = Mage::app()->getGroup($groupCode)->getWebsite();
            return $website->getConfig($path);
        }

        return Mage::getStoreConfig($path);
    }

    /**
     * Get admin scope in order to store config data
     *
     * @return array admin scope
     *  - scope
     *  - scope_id
     *  - scope_code
     */
    public function getAdminScope()
    {
        $scope   = 'default';
        $scopeId = 0;
        $scopeCode = '';
        if ($storeCode = Mage::app()->getRequest()->getParam('store')) {
            $scope   = 'stores';
            $scopeId = (int)Mage::getConfig()->getNode('stores/' . $storeCode . '/system/store/id');
            $scopeCode = $storeCode;
        } elseif ($websiteCode = Mage::app()->getRequest()->getParam('website')) {
            $scope   = 'websites';
            $scopeId = (int)Mage::getConfig()->getNode('websites/' . $websiteCode . '/system/website/id');
            $scopeCode = $websiteCode;
        }

        return array('scope' => $scope, 'scope_id' => $scopeId, 'scope_code' => $scopeCode);
    }

    /**
     * Format number in a given locale
     *
     * @param float $number
     * @param string $locale
     * @param int $precision
     * @return string
     */
    public function formatNumberInLocale($number, $locale = null, $precision = 2)
    {
        if ($locale == null) {
            $locale = Mage::app()->getLocale()->getLocaleCode();
        }

        $format  = Zend_Locale_Data::getContent($locale, 'decimalnumber');
        $number  = Zend_Locale_Format::toNumber(
            $number,
            array(
                'locale'        => $locale,
                'number_format' => $format,
                'precision'     => $precision
            )
        );
        return $number;
    }

    /**
     * Check and get url for a given url type.
     *
     * @param Emv_Core_Model_Account $account
     * @param string $type
     * @throws Mage_Core_Exception if we can't find a corresponding url
     * @return string
     */
    public function checkAndGetUrlForType(Emv_Core_Model_Account $account, $type)
    {
        // get service label
        $labels = Emv_Core_Model_Account::getUrlTypesAndLabels();
        $serviceLabel = $type;
        if (isset($labels[$type])) {
            $serviceLabel = $labels[$type];
        }

        // check url
        $url = $account->getUrlForType($type);
        if (!$url) {
            Mage::throwException(Mage::helper('emvcore')->__('Please define a valid url for %s !', $serviceLabel));
        }

        return $url;
    }

    /**
     * @param Emv_Core_Model_DataProcessing_Process $process
     * @return string
     */
    public function getLogUrlForProcess(Emv_Core_Model_DataProcessing_Process $process)
    {
        return Mage::getModel('adminhtml/url')->getUrl('emv_core/dataProcessing/log', array('id' => $process->getId()));
    }
}