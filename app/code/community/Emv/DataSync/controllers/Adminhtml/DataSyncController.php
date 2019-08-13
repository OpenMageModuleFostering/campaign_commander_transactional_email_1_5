<?php
/**
 * Data Sync Controller
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Adminhtml_DataSyncController extends Mage_Adminhtml_Controller_Action
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Controller_Varien_Action::_construct()
     */
    public function _construct()
    {
        // Define module dependent translate
        $this->setUsedModuleName('Emv_DataSync');
    }

    /**
     * Subscriber Queue Grid
     */
    public function indexAction()
    {
        $this->_title($this->__('SmartFocus'))->_title($this->__('Subscriber Export'));

        if ($this->getRequest()->getParam('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->loadLayout();

        $this->_setActiveMenu('emailvision/datasync');

        $this->_addContent(
            $this->getLayout()->createBlock('emvdatasync/adminhtml_newsletter_subscriber','subscriber')
        );

        $this->renderLayout();
    }

    /**
     * Subscriber Queue Grid action for ajax request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('emvdatasync/adminhtml_newsletter_subscriber_grid')->toHtml()
        );
    }

    /**
     * Export subscribers grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'subscribers.csv';
        $content    = $this->getLayout()->createBlock('emvdatasync/adminhtml_newsletter_subscriber_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export subscribers grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'subscribers.xml';
        $content    = $this->getLayout()->createBlock('emvdatasync/adminhtml_newsletter_subscriber_grid')
            ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Schedule or Remove subscriber list from export queue action
     */
    public function massQueueAction()
    {
        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds) || count($subscribersIds) == 0) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
        } else {
            try {
                $scheduled = $this->getRequest()->getParam('scheduled');
                if (
                    $scheduled != Emv_DataSync_Helper_Service::SCHEDULED_VALUE
                    && $scheduled != Emv_DataSync_Helper_Service::NOT_SCHEDULED_VALUE
                ) {
                    $scheduled = Emv_DataSync_Helper_Service::SCHEDULED_VALUE;
                }

                Mage::helper('emvdatasync')->massScheduleSubscriber($subscribersIds, $scheduled);
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were updated', count($subscribersIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Mass dry test - creating csv files for the given subscribers list
     * Allows to download the file(s) if they are readable else display the error message and forward to queue grid
     */
    public function massDryTestAction()
    {
        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds) || count($subscribersIds) == 0) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
        } else {
            try {
                 $service = Mage::getModel('emvdatasync/service_batchMember');
                 $output = $service->massExportCustomers($subscribersIds, true);

                // If non-blocking errors occurs
                $processErrors = $service->getErrors();
                if (!empty($processErrors)) {
                    foreach ($processErrors as $error) {
                        Mage::getSingleton('adminhtml/session')->addError($error);
                    }
                } else {
                    $listFileToZip = array();
                    foreach ($output as $accountId => $fileData) {
                        foreach ($fileData['treated_files'] as $file) {
                            if (is_readable($file['path'])) {
                                $listFileToZip[] = array($file['path'], $file['filename']);
                            }
                        }
                    }

                    if (count($listFileToZip)) {
                        $downloadFileName = 'export_files_'.rand().'.zip';
                        $zipFilePath = Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                            . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR . DS . Emv_Core_Helper_Data::TEMPORARY_DIR;
                        Mage_System_Dirs::mkdirStrict($zipFilePath);

                        $zipFileName      = $zipFilePath . DS . $downloadFileName;
                        $zip              = new ZipArchive();
                        if ($zip->open($zipFileName, ZIPARCHIVE::CREATE) === true) {
                            /**
                             * Manage list of files to zip
                             */
                            foreach ($listFileToZip as $file) {
                                if (is_readable($file[0])) {
                                    $zip->addFile($file[0], $file[1]);
                                }
                            }

                            $zip->close();

                            $content = array(
                                'type'  => 'filename',
                                'value' => $zipFileName,
                                'rm'    => true,
                            );
                            return $this->_prepareDownloadResponse($downloadFileName, $content, 'application/zip');
                        } else {
                            Mage::throwException(Mage::helper('emvdatasync')->__('Error while creating archive'));
                        }
                    } else {
                        Mage::getSingleton('adminhtml/session')->addNotice(
                            Mage::helper('emvdatasync')->__('No file is generated')
                        );
                    }
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Make a test of synchronization
     */
    public function sendTestAction()
    {
        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds) || count($subscribersIds) == 0) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
        } else {
            $allowed = (int)Mage::getStoreConfig(Emv_DataSync_Helper_Data::XML_PATH_ALLOWED_NUMBER_TEST);
            if ($allowed < count($subscribersIds)) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('emvdatasync')->__(
                        'Your list (%s members) exceeds the allowed limit (%s)!',
                        count($subscribersIds),
                        $allowed
                    )
                );
            } else {
                $helper = Mage::helper('emvdatasync');
                $createdLock = false;
                $startRunTime = $helper->getFormattedGmtDateTime();

                try {
                    // check and create lock
                    if (!$helper->checkLockFile()) {
                        // create lock file => do not allow several process at the same time
                        $helper->createLockFile('Batch member synchronization cron process running at GMT timezone '
                            . $startRunTime
                        );
                        $service = Mage::getModel('emvdatasync/service_batchMember');
                        $createdLock = true;

                        $service->init();
                        $service->setInputData(array('test_mode' => true, 'member_ids' => $subscribersIds));
                        $service->run();

                        $gridLink = sprintf(
                                '<a href="%s">%s</a>',
                                $this->getUrl('emv_core/dataProcessing'),
                                Mage::helper('emvcore')->__('Process grid')
                        );
                        Mage::getSingleton('adminhtml/session')->addNotice(
                            Mage::helper('emvdatasync')->__(
                                'Your synchronization (%s subscriber(s)) has been triggered. Please look at process %s (%s)',
                                count($subscribersIds),
                                $service->getProcess()->getId(),
                                $gridLink
                            )
                        );

                        // If non-blocking errors occurs
                        $processErrors = $service->getErrors();
                        if (!empty($processErrors)) {
                            $url = Mage::helper('emvcore')->getLogUrlForProcess($service->getProcess());
                            $logLink = sprintf('<a href="%s">%s</a>', $url, Mage::helper('emvcore')->__('Click here'));
                            Mage::getSingleton('adminhtml/session')->addError(
                                Mage::helper('emvdatasync')->__(
                                    'Some non-blocking errors occured, check log (%s) for more details!',
                                    $logLink
                                )
                            );
                        }
                    } else {
                        Mage::getSingleton('adminhtml/session')->addError(
                            Mage::helper('newsletter')->__('Another Data Sync has been running! Please wait until it finishes!')
                        );
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }

                // if lock is created, need to delete it
                if ($createdLock) {
                    try {
                        $helper->removeLockFile();
                    } catch (Exception $e) {
                        // don't do anything
                    }
                }
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Get EmailVision fields from api object
     */
    public function getMemberFieldsAction()
    {
        $accountId = $this->getRequest()->getParam('account_id', null);
        if (!$accountId) {
            $accountId = Mage::helper('emvcore')->getAdminScopedConfig(
                Emv_DataSync_Helper_Data::XML_PATH_ACCOUNT_FOR_MEMBER
            );
        }

        $preparedFields = array();

        $account = Mage::getModel('emvcore/account');
        $account->load($accountId);
        // get account id for
        if ($account->getId()) {
            try {
                $service = Mage::getModel('emvcore/service_member');
                $service->setAccount($account);
                $fields = $service->getEmailVisionFields();

                $notAllowed = array(
                    Emv_Core_Model_Service_Member::FIELD_CLIENT_ID,
                    Emv_Core_Model_Service_Member::FIELD_MEMBER_ID,
                    Emv_Core_Model_Service_Member::FIELD_UNJOIN
                );
                foreach ($fields as $data) {
                    if (!in_array($data['name'], $notAllowed)) {
                        $preparedFields[] = $data;
                    }
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emvdatasync')->__($e->getMessage()));
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('emvdatasync')
                    ->__('Please select an account for "Triggered Exports". Access to Member service is required')
            );
        }

        if (count($preparedFields)) {
            Mage::helper('emvdatasync')->saveEmailVisionFieldsInConfig($preparedFields);

            $config = Mage::getModel('core/config');
            $config->removeCache();

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('emvdatasync')->__('Retrieved successfully SmartFocus member fields')
            );
        }

        $this->_redirect(
            'adminhtml/system_config/edit/section/emvdatasync/',
            array('_current' => array('section', 'website', 'store'))
        );
    }

    /**
     * Check if having a correct permission
     *
     * @return boolean
     */
    protected function _isallowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/datasync/queue');
    }

}

