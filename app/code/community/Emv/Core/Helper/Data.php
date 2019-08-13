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
     * List of accounts
     * @var array
     */
    protected $_accountList = array();

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
        return Mage::getBaseDir('export') . DS . 'emailvision' . DS . 'locks';
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
     * Create a lock file
     *
     * @param string $content
     * @return number | boolean - false
     */
    public function createLockFile($fileName, $content = '')
    {
        if (!$content) {
            $content = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        }

        return $this->_getLockHandler()->write($fileName, $content);
    }

    /**
     * Remove a lock file
     *
     * @param string $fileName
     * @return boolean
     */
    public function removeLockFile($fileName)
    {
        return $this->_getLockHandler()->rm($fileName);
    }

    /**
     * Check if lock file exists
     * @param string $fileName
     * @return boolean
     */
    public function checkLockFile($fileName)
    {
        return $this->_getLockHandler()->fileExists($fileName, true);
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

        $action = Mage::app()->getFrontController()->getAction();
        if ($action instanceOf Mage_Adminhtml_System_ConfigController) {
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
        }

        return Mage::getStoreConfig($path);
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

}