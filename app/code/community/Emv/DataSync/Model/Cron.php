<?php
/**
 * Local cron model to call massUpdate method
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
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
        $exportDone = false;

        if (!Mage::getStoreConfigFlag(self::XML_PATH_EMAILVISION_BATCH_ENABLED)) {
            return $this;
        }

        $this->_errors = array();

        $startRunTime = $helper->getFormattedGmtDateTime();

        $processErrors = array();
        $createdLock = false;
        try {
            // check and create lock
            if (!$helper->checkLockFile()) {
                $service = Mage::getModel('emvdatasync/service_batchMember');
                $account = Mage::helper('emvdatasync')->getEmvAccountForStore('batch');
                $service->setAccount($account);

                // create lock file => do not allow several process at the same time
                $helper->createLockFile('Batch member synchronization cron process running at GMT timezone ' . $startRunTime);
                $createdLock = true;

                $exportDone = $service->massExportCustomers();

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

        // if some errors occur, we should inform the client
        if (count($this->_errors)) {
            $this->_sendCustomerExportErrors();
        }

        if ($exportDone) {
            $config = Mage::getModel('core/config');
            $config->saveConfig(
                'emvdatasync/last_successful_synchronization/customers',
                Mage::helper('emvdatasync')->getFormattedGmtDateTime()
            );
            $config->removeCache();
        }

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

        $processErrors = array();
        $createdLock = false;
        try {
            // check and create lock
            if (!$helper->checkLockFile()) {
                $service = Mage::getModel('emvdatasync/service_member');
                $account = Mage::helper('emvdatasync')->getEmvAccountForStore();
                $service->setAccount($account);

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
            $this->_errors[] = 'Errors occured while running memberExport (memberApi cron), at GMT timezone ' . $startRunTime;
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

    /**
     * Cron method to clean files moved in uploaded folder after exporting customers
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

        $this->_errors = array();

        try {
            // Check folders in case this method is called before any export
            Mage::helper('emvdatasync')->checkAndCreatFolder();

            // remove uploaded file
            $dirHandler = @opendir(Mage::getBaseDir('export'). DS . 'emailvision' . DS . 'uploaded');
            while($filename = readdir($dirHandler)) {
                // Delete any file in uploaded folder
                if ($filename != '.' && $filename != '..') {
                    unlink(Mage::getBaseDir('export'). DS . 'emailvision' . DS . 'uploaded'. DS . $filename);
                }
            }
            @closedir($dirHandler);

            // remove emailvision export folder
            $dirHandler = @opendir(Mage::getBaseDir('export'). DS . 'emailvision' );
            while($filename = readdir($dirHandler)) {
                $path = Mage::getBaseDir('export'). DS . 'emailvision' . DS . $filename;
                // Delete any file in uploaded folder
                if ($filename != '.' && $filename != '..' && is_file($path)) {
                    unlink($path);
                }
            }
            @closedir($dirHandler);
        } catch (Exception $e) {
            $this->_errors[] = 'Errors occured while removing exported files (cleanEmailVisionFiles cron), at GMT timezone '
                . $startRunTime;
            $this->_errors[] = $e->getMessage();
            $this->_sendCustomerExportErrors();
        }

        return $this;
    }
}