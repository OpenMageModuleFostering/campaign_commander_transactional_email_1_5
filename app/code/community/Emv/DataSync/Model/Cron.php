<?php
/**
 * Local cron model to call massUpdate method
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Cron extends Mage_Core_Model_Abstract
{
    const XML_PATH_EMAIL_EMAILVISION_TEMPLATE     = 'emvdatasync/general/error_email_template';
    const XML_PATH_EMAIL_EMAILVISION_SENDER       = 'emvdatasync/general/error_email_sender';
    const XML_PATH_EMAIL_EMAILVISION_RECIPIENT    = 'emvdatasync/general/error_email_recipient';

    const XML_PATH_EMAILVISION_BATCH_ENABLED      = 'emvdatasync/batchmember/enabled';
    const XML_PATH_EMAILVISION_APIMEMBER_ENABLED  = 'emvdatasync/apimember/enabled';
    const XML_PATH_EMAILVISION_CLEAN_ENABLED      = 'emvdatasync/export_file_cleaning/enabled';

    /**
     * Error messages
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Send customer export errors
     *
     * @return Emv_DataSync_Model_Cron
     */
    public function _sendCustomerExportErrors()
    {
        if (!$this->_errors) {
            return $this;
        }
        if (!Mage::getStoreConfig(self::XML_PATH_EMAIL_EMAILVISION_RECIPIENT)) {
            return $this;
        }

        try {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);

            /* @var $emailTemplate Emv_Emt_Model_Mage_Core_Email_Template */
            $emailTemplate = Mage::getModel('core/email_template');

            /* @var $emailTemplate Mage_Core_Model_Email_Template */
            $emailTemplate->setDesignConfig(array('area' => 'backend'))
                ->sendTransactional(
                    Mage::getStoreConfig(self::XML_PATH_EMAIL_EMAILVISION_TEMPLATE),
                    Mage::getStoreConfig(self::XML_PATH_EMAIL_EMAILVISION_SENDER),
                    Mage::getStoreConfig(self::XML_PATH_EMAIL_EMAILVISION_RECIPIENT),
                    null,
                    array('errors' => implode("<br>", $this->_errors))
            );

            $translate->setTranslateInline(true);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Cron method to call a mass customers export with apiBatchMember
     *
     * @return Emv_DataSync_Model_Cron
     */
    public function batchMemberExport()
    {
        $helper = Mage::helper('emvdatasync');

        if (!Mage::getStoreConfigFlag(self::XML_PATH_EMAILVISION_BATCH_ENABLED)) {
            return $this;
        }

        $this->_errors = array();

        $startRunTime = $helper->getFormattedGmtDateTime();
        // !!! it's very important to set a custom error handler in order to remove lock file in case of fatal error
        Mage::helper('emvcore')->setSmartFocusErrorHandler();

        $processErrors = array();
        $createdLock = false;
        try {
            // check and create lock
            if (!$helper->checkLockFile()) {
                $service = Mage::getModel('emvdatasync/service_batchMember');

                // create lock file => do not allow several process at the same time
                $helper->createLockFile('Batch member synchronization cron process running at GMT timezone ' . $startRunTime);
                $createdLock = true;

                $service->init();
                $service->run();

                // If non-blocking errors occurs
                $processErrors = $service->getErrors();
                if (!empty($processErrors)) {
                    throw(new Exception('Some non-blocking errors occured, check below for more details:'));
                }
            }
        } catch (Exception $e) {
            $this->_errors[] = 'Errors occured while running Scheduled Member Export (batchmemberApi cron), at GMT timezone '
                . $startRunTime;
            $this->_errors[] = $e->getMessage();

            foreach ($processErrors as $apiError) {
                $this->_errors[] = $apiError;
            }
        }

        // if lock is created, need to delete it
        if ($createdLock) {
            try {
                $helper->removeLockFile();
            } catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
            }
        }

        // reset to Magento error handler
        Mage::helper('emvcore')->resetErrorHandler();

        // if some errors occur, we should inform the client
        if (count($this->_errors)) {
            $this->_sendCustomerExportErrors();
        }

        $config = Mage::getModel('core/config');
        $config->saveConfig(
            'emvdatasync/last_successful_synchronization/customers',
            Mage::helper('emvdatasync')->getFormattedGmtDateTime()
        );
        $config->removeCache();

        return $this;
    }

    /**
     * Cron method to call a memberApi export. Uses some collection and added fields to export the observer updated
     *
     * @return Emv_DataSync_Model_Cron
     */
    public function memberExport()
    {
        $helper = Mage::helper('emvdatasync');
        if (!Mage::getStoreConfigFlag(self::XML_PATH_EMAILVISION_APIMEMBER_ENABLED)) {
            return $this;
        }

        $this->_errors = array();

        // gmt date time
        $startRunTime = $helper->getFormattedGmtDateTime();
        // !!! it's very important to set a custom error handler in order to remove lock file in case of fatal error
        Mage::helper('emvcore')->setSmartFocusErrorHandler();

        $processErrors = array();
        $createdLock = false;
        try {
            // check and create lock
            if (!$helper->checkLockFile()) {
                $service = Mage::getModel('emvdatasync/service_member');

                // create lock file => do not allow several process at the same time
                $helper->createLockFile('Member synchronization cron process running at GMT timezone ' . $startRunTime);
                $createdLock = true;

                // Api calls
                $service->triggerExport();

                // If non-blocking errors occurs
                $processErrors = $service->getErrors();
                if (!empty($processErrors)) {
                    throw (new Exception('Some non-blocking errors occured, check below for more details:'));
                }
            }
        } catch (Exception $e) {
            $this->_errors[] = 'Errors occured while running memberExport (memberApi cron), at GMT timezone '
                . $startRunTime;
            $this->_errors[] = $e->getMessage();
            foreach ($processErrors as $error) {
                $this->_errors[] = $error;
            }
        }

        // if lock is created, need to delete it
        if ($createdLock) {
            try {
                $helper->removeLockFile();
            } catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
            }
        }

        Mage::helper('emvcore')->resetErrorHandler();
        // if some errors occur, we should inform the client
        if (count($this->_errors)) {
            $this->_sendCustomerExportErrors();
        }

        return $this;
    }

    /**
     * Cron method to clean files moved in uploaded folder after exporting customers,
     *
     * @return Emv_DataSync_Model_Cron
     */
    public function cleanEmailVisionFiles()
    {
        if (!Mage::getStoreConfigFlag(self::XML_PATH_EMAILVISION_CLEAN_ENABLED)) {
            return $this;
        }

        // everything is in GMT date time
        $startRunTime = Mage::helper('emvdatasync')->getFormattedGmtDateTime();
        $createdLock = false;
        $helper = Mage::helper('emvdatasync');

        $this->_errors = array();
        try {
            // Check folders in case this method is called before any export
            $helper->checkAndCreatFolder();

            // check and create lock
            if (!$helper->checkLockFile()) {
                // create lock file => do not allow several process at the same time
                $helper->createLockFile('Cleaning file cron process running at GMT timezone ' . $startRunTime);
                $createdLock = true;

                // open uploaded folder
                $dirHandler = @opendir(
                    Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                    . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
                    . DS . Emv_Core_Helper_Data::UPLOADED_FILE_DIR
                );
                while ($filename = readdir($dirHandler)) {
                    // Delete any file in uploaded folder
                    if ($filename != '.' && $filename != '..') {
                        @unlink(
                            Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                            . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
                            . DS . Emv_Core_Helper_Data::UPLOADED_FILE_DIR
                            . DS . $filename
                        );
                    }
                }
                @closedir($dirHandler);

                // open export folder
                $dirHandler = @opendir(
                    Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                    . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
                );
                while ($filename = readdir($dirHandler)) {
                    $path = Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                        . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
                        . DS . $filename
                    ;
                    // Delete any file in exporte folder
                    if ($filename != '.' && $filename != '..' && is_file($path)) {
                        @unlink($path);
                    }
                }
                @closedir($dirHandler);
            }
        } catch (Exception $e) {
            $this->_errors[] = 'Errors occured while removing exported files (cleanEmailVisionFiles cron), at GMT timezone '
                . $startRunTime;
            $this->_errors[] = $e->getMessage();
        }

        // if lock is created, need to delete it
        if ($createdLock) {
            try {
                $helper->removeLockFile();
            } catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
            }
        }

        // if some errors occur, we should inform the client
        if (count($this->_errors)) {
            $this->_sendCustomerExportErrors();
        }

        return $this;
    }

    /**
     * Cron method to proceed purchase information
     *
     * @return Emv_DataSync_Model_Cron
     */
    public function startPurchaseProcess()
    {
        // everything is in GMT date time
        $startRunTime = Mage::helper('emvdatasync')->getFormattedGmtDateTime();
        $createdLock = false;
        $helper = Mage::helper('emvdatasync');

        $processErrors = array();
        $this->_errors = array();
        try {
            // Check folders in case this method is called before any export
            $helper->checkAndCreatFolder();

            // check and create lock
            if (!$helper->checkLockFile()) {
                // create lock file => do not allow several process at the same time
                $helper->createLockFile('Purchase information cron process running at GMT timezone ' . $startRunTime);
                $createdLock = true;

                $processErrors = Mage::getModel('emvdatasync/service_dataProcess')->prepareList();
                if (!empty($processErrors)) {
                    throw (new Exception('Check below for more details:'));
                }
            }
        } catch (Exception $e) {
            $this->_errors[] = 'Errors occured while preparing purchase information, at GMT timezone '
                . $startRunTime;
            $this->_errors[] = $e->getMessage();
            foreach ($processErrors as $error) {
                $this->_errors[] = $error;
            }
        }

        // if lock is created, need to delete it
        if ($createdLock) {
            try {
                $helper->removeLockFile();
            } catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
            }
        }

        // if some errors occur, we should inform the client
        if (count($this->_errors)) {
            $this->_sendCustomerExportErrors();
        }

        return $this;
    }
}