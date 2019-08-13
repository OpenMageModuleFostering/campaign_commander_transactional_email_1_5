<?php
/**
 * Batch Member service - Handle Member Data
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_Service_BatchMember extends Emv_Core_Model_Service_BatchMember
{
    /**
     * XML config path for performance config
     */
    const XML_PATH_PERFORMANCE = 'emvcore/batchmember/performance';

    /**
     * The default limit of subscribers to get
     */
    const PAGE_SIZE = 10000;

    /**
     * Waiting time between each status checking call
     */
    const WAITING_TIME = 60;

    /**
     * @var boolean
     */
    protected $_foldersAreChecked = false;

    /**
     * @var string
     */
    protected $_currentTime = null;

    // Subscribers collection management vars
    protected $_pageSize;
    protected $_pageCount;
    protected $_curPage;

    // Files and upload data
    protected $_uploadId;
    protected $_errors = array();
    protected $_fileNamesToUpload = array();
    protected $_fileNamesUploaded = array();

    /**
     * Mapped headers
     * @var array
     */
    protected $_mappedHeaders = array();

    /**
     * Subscriber data list
     * @var array
     */
    protected $_subscriberDataList = array();

    /**
     * List of subscriber ids per file
     * @var array
     */
    protected $_writtenSubscribersIds = array();

    /**
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
        $this->_pageSize = Mage::getStoreConfig(self::XML_PATH_PERFORMANCE);
        // avoid division by zero
        if (!$this->_pageSize) {
            $this->_pageSize = self::PAGE_SIZE;
        }

        // everything should be in GMT
        $this->_currentTime = Mage::getModel('core/date')->gmtDate('Ymd_H-i-s');

        return parent::_construct();
    }

    /**
     * Prepare customer csv files for upload
     */
    public function prepareCustomerCsvFiles()
    {
        $mappedHeaders = $this->prepareAndGetMappedHeaders();

        $subscribers = $this->getSubscriberCollection();
        $totalSubscribersCount = $subscribers->getSize();
        $this->_pageCount = ceil($subscribers->getSize() / $this->_pageSize);

        if ($subscribers->getSize() > 0) {
            // Add all customer and address attributes that have been mapped into collection
            Mage::helper('emvdatasync/service')->addCustomerAndAddressAttributes($subscribers);

            // Get all stores
            $stores = Mage::app()->getStores(true);

            // Get mapped EmailVision entity id
            $entityId = strtoupper(Mage::helper('emvdatasync')->getMappedEntityId());

            // Get api service corresponding to current account
            $service = $this->getApiService($this->getAccount());

            for ($this->_curPage = 1; $this->_curPage <= $this->_pageCount; $this->_curPage++) {
                // Define current page
                $subscribers->clear()
                    ->setPageSize($this->_pageSize)
                    ->setCurPage($this->_curPage);

                // Add a row foreach customer
                $preparedData = array();
                foreach ($subscribers as $subscriber) {
                    // Replace customer email by suscriber one
                    $subscriber->setData('email', $subscriber->getSubscriberEmail());

                    // Prepare and add a customer's data row
                    $subscriberData = array();

                    // retreive all maped attribute values from subscriber, build them into array
                    $entityFieldsToSelect = Mage::helper('emvdatasync/service')->prepareAndGetMappedCustomerAttributes();
                    foreach ($entityFieldsToSelect as $attribute) {
                        $emailVisionKey = strtoupper($attribute->getEmailVisionKey());

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
                                $fieldValue = $stores[$fieldValue]->getName();
                            }
                        }

                        $subscriberData[$emailVisionKey] = $fieldValue;
                    }

                    // entity id field
                    $subscriberData[$entityId] = $subscriber->getId();

                    // If is unjoined
                    $unjoinedDate = '';
                    if ($subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                        if ($subscriber->getData('date_unjoin')) {
                            // date time should be in EmailVision format
                            $unjoinedDate = Mage::helper('emvdatasync/service')
                                ->getEmailVisionDate($subscriber->getData('date_unjoin'));
                        }
                    }
                    $subscriberData[strtoupper(Emv_Core_Model_Service_Member::FIELD_UNJOIN)] = $unjoinedDate;
                    $preparedData[] = array(
                        'id' => $subscriber->getId(),
                        'fields' => $subscriberData
                    );

                    // add subscriber data into array
                    $tempSubscriber = array(
                        'id' => $subscriber->getId(),
                        'rejoined' => false
                    );
                    if ($subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                        if ($subscriber->getData('date_unjoin') != '' && $subscriber->getData('date_unjoin') != null) {
                            $tempSubscriber['rejoined'] = true;
                        }
                    }
                    $this->_subscriberDataList[$subscriber->getId()] = $tempSubscriber;
                }// end foreach subscribers

                try {
                    $csvFileData = $service->createCsvFiles(
                        $this,
                        array_keys($mappedHeaders),
                        $preparedData
                    );

                    foreach ($csvFileData as $data) {
                        // member list for each file
                        $this->_writtenSubscribersIds[$data['file']['filename']] = $data['list_member'];
                        $this->_fileNamesToUpload[] = $data['file'];
                    }
                } catch (Exception $e) {
                    Mage::logException($e);

                    $this->_errors[] = 'Exception while building csv file, with message:        ' . $e->getMessage();
                }
            } // end foreach page
        }
    }

    /**
     * Get merge criteria for batch member api requests
     *
     * @return array
     */
    public function getMergeCriteriaForBatchMember()
    {
        if (Mage::helper('emvdatasync')->getEmailEnabled()) {
            $mergeCriteria = array(strtoupper(Emv_Core_Model_Service_Member::FIELD_EMAIL));
        } else {
            $mergeCriteria = array(strtoupper(Mage::helper('emvdatasync')->getMappedEntityId()));
        }
        return $mergeCriteria;
    }

    /**
     * Mass export customers using Batch member api
     * @return boolean
     */
    public function massExportCustomers()
    {
        // create csv files
        $this->prepareCustomerCsvFiles();

        $mergeCriteria = $this->getMergeCriteriaForBatchMember();

        // return
        $uploadDone = true;

        $exportedFileTime = Mage::helper('emvdatasync')->getFormattedGmtDateTime();

        try {
            // Get api service corresponding to current account
            $service = $this->getApiService($this->getAccount());
            foreach ($this->_fileNamesToUpload as $data) {
                $mappedHeaders = $this->prepareAndGetMappedHeaders();

                $this->_uploadId = $service->sendFileToWebservice(
                    $data,
                    $mappedHeaders,
                    $mergeCriteria,
                    false
                );

                // only checking upload status if we can have upload id
                if ($this->_uploadId != false) {
                    $uploadProcessing = true;
                    // wait until SmartFocus platform completes processing uploaded file
                    // check status's upload for an interval self::WAITING_TIME
                    do {
                        sleep(self::WAITING_TIME);
                        // get status's upload
                        $uploadStatus = $service->getUploadStatus($this->_uploadId, false);

                        if (
                            in_array(
                                $uploadStatus['status'],
                                EmailVision_Api_BatchMemberService::getAccomplishedStatuses()
                            )
                        ) {
                            $uploadProcessing = false;
                            if (
                                $uploadStatus['status'] == EmailVision_Api_BatchMemberService::getStatusOkWithoutError()
                            ) {
                                $this->_fileNamesUploaded[] = $data['filename'];
                            } else {
                                $uploadDone = false;
                                $this->_errors[] = 'Error on while calling mass_export, uploading file '
                                    . $data['filename'] . ", details returned from EmailVision:        " . $uploadStatus['details'];
                            }
                        }
                    } while ($uploadProcessing);

                    // move uploaded file
                    $this->moveUploadedFile($data);
                }
            }
        } catch (Exception $e) {
            $uploadDone = false;
            Mage::logException($e);

            $this->_errors[] = "Exception while calling mass_export in file uploading process, with message:        "
                . $e->getMessage();
        }

        // Update all subscribersUpdateDate for files uploaded with success
        $this->massSetMemberLastUpdateDate($exportedFileTime);

        if ($service) {
            try {
                $service->closeApiConnection();
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_errors[] = "Exception while closing SmartFocus service with message :         "
                    . $e->getMessage();
            }
        }

        return $uploadDone;
    }

    /**
     * @param string $index
     * @param array $memberData
     * @return array
     *  - filename
     *  - path => the absolute path to the file
     *  - time => time is used to create file name
     */
    public function prepareFileName($index, $memberData = array())
    {
        // If flag is setted to false, check have not been done for current action
        if (!$this->_foldersAreChecked) {
            // Check whether dirs exist, if not create them
            $helper = Mage::helper('emvdatasync');
            $helper->checkAndCreatFolder();

            $this->_foldersAreChecked = true;
        }

        // Preparing filename
        $filename = 'customers' . $this->_currentTime . '_part' . $index . '.csv';

        // Preparing file path
        $path = Mage::getBaseDir('export') . DS . 'emailvision' . DS . $filename;
        return array('filename' => $filename, 'path' => $path, 'time' => $this->_currentTime);
    }

    /**
     * Set member last update date for all updated subscribers
     */
    public function massSetMemberLastUpdateDate()
    {
        $subscribersIds = array();
        $rejoinedIds    = array();

        // Cases: mass export will have a _fileNamesUploaded set, member export will have _subsciberIds
        if (!empty($this->_fileNamesUploaded)) {
            foreach ($this->_fileNamesUploaded as $fileName) {
                if (isset($this->_writtenSubscribersIds[$fileName])) {
                    foreach ($this->_writtenSubscribersIds[$fileName] as $subscriberId) {
                        $subscribersIds[] = $subscriberId;
                        if (
                            isset($this->_subscriberDataList[$subscriberId])
                            && $this->_subscriberDataList[$subscriberId]['rejoined'] == true
                        ) {
                            $rejoinedIds[] = $subscriberId;
                        }

                    }
                }
            }

            try {
                $errorMessage = Mage::helper('emvdatasync/service')->massSetMemberLastUpdateDate($subscribersIds, $rejoinedIds);
                if ($errorMessage !== true) {
                    $this->_errors[] = $errorMessage;
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * @param array $file
     * @return boolean
     */
    public function moveUploadedFile($file)
    {
        return @rename(
            $file['path'],
            Mage::getBaseDir('export'). DS . 'emailvision' . DS . 'uploaded' . DS . $file['filename']
        );
    }

    /**
     * @return Mage_Newsletter_Model_Resource_Subscriber_Collection
     */
    public function getSubscriberCollection()
    {
        /* @var $subscribers Mage_Newsletter_Model_Resource_Subscriber_Collection */
        // Get all subscribers that have data modifed since the last sync as well as the unsubscribed ones
        $subscribers = Mage::getModel('newsletter/subscriber')->getCollection();

        $fieldMemberLastUpdate = Emv_DataSync_Helper_Service::FIELD_MEMBER_LAST_UPDATE;
        $fieldUnjoin = Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN;
        $fieldDataLastUpdate = Emv_DataSync_Helper_Service::FIELD_DATA_LAST_UPDATE;

        $select = $subscribers->getSelect();
        // in the case of member_last_update is not null
        // - data_last_update_date > member_last_update => member data has been changed
        // - main_table.date_unjoin > member_last_update => member has just unjoined
        $notNullMemberCond = "main_table.{$fieldMemberLastUpdate} IS NOT NULL"
            . " AND ( main_table.{$fieldDataLastUpdate} > main_table.{$fieldMemberLastUpdate}"
            . "    OR main_table.{$fieldUnjoin} > main_table.{$fieldMemberLastUpdate}"
            . ' )';
        $select->where($notNullMemberCond);
        $select->orWhere("main_table.{$fieldMemberLastUpdate} IS NULL");

        return $subscribers;
    }

    /**
     * @return array
     */
    public function prepareAndGetMappedHeaders()
    {
        if ($this->_mappedHeaders == null) {
            $this->_mappedHeaders = array();

            // retreive all maped attribute values from subscriber, build them into array
            $entityFieldsToSelect = Mage::helper('emvdatasync/service')->prepareAndGetMappedCustomerAttributes();
            foreach ($entityFieldsToSelect as $attribute) {
                $emailVisionKey = $attribute->getEmailVisionKey();
                $emailVisionKey = strtoupper($emailVisionKey);

                $this->_mappedHeaders[strtoupper($emailVisionKey)] = array(
                    'to_replace' => true
                );
            }

            // the entity id can not be replaced
            $entityIdField = Mage::helper('emvdatasync')->getMappedEntityId();
            $this->_mappedHeaders[strtoupper($entityIdField)] = array(
                'to_replace' => false
            );

            $this->_mappedHeaders[strtoupper(Emv_Core_Model_Service_Member::FIELD_UNJOIN)] = array(
                'to_replace' => true
            );
        }

        return $this->_mappedHeaders;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }
}