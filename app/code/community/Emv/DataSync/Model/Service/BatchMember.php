<?php
/**
 * Batch Member service - Handle Member Data
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_DataSync_Model_Service_BatchMember extends Emv_Core_Model_Service_BatchMember
    implements Emv_Core_Model_DataProcessing_Profile_Interface
{
    /**
     * XML config path for performance config
     */
    const XML_PATH_PERFORMANCE = 'emvdatasync/batchmember/performance';

    /**
     * The default limit of subscribers to get
     */
    const PAGE_SIZE = 10000;

    /**
     * Waiting time (seconds) between each status checking call
     */
    const WAITING_TIME = 40;

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

    // Files and upload data
    protected $_errors = array();

    protected $_exportFilePrefix = '';

    /**
     * Mapped headers
     * @var array
     */
    protected $_mappedHeaders = array();

    /**
     * Input data for service
     * @var array
     */
    protected $_inputData = array();

    /**
     * Profile type
     * @var string
     */
    public static $type = Emv_Core_Model_DataProcessing_Process::TYPE_DATA_SYNC;

    /**
     * Profile title
     * @var string
     */
    protected $_title = 'Batch Member Data Synchronization';

    /**
     * Current process
     * @var Emv_Core_Model_DataProcessing_Process
     */
    protected $_process = null;

    /**
     * Process class name
     * @var string
     */
    protected $_processClassName = 'emvcore/dataProcessing_process';

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

        return parent::_construct();
    }

    /**
     * Get default profile process
     *
     * @return Emv_Core_Model_DataProcessing_Process $process
     */
    public function initProcess()
    {
        $process = Mage::getModel($this->_processClassName);
        $process->setTitle($this->_title);
        $process->setType(self::$type);

        $process->save();
        return $process;
    }

    /**
     * Init profile
     *
     * @param Emv_Core_Model_DataProcessing_Process $process
     *
     * @return void
     * @throws Exception
     */
    public function init(Emv_Core_Model_DataProcessing_Process $process = null)
    {
        if (is_null($process)) {
            $process = $this->initProcess();
        }

        $process->initLog();
        $this->_process = $process;
    }

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_DataProcessing_Profile_Interface::getInputData()
     */
    public function getInputData()
    {
        return $this->_inputData;
    }

    /**
     * (non-PHPdoc)
     * @see Emv_Core_Model_DataProcessing_Profile_Interface::setInputData()
     */
    public function setInputData(array $input)
    {
        $this->_inputData = $input;
        return $this;
    }

    /**
     * Get Associated Process
     * (non-PHPdoc)
     * @see Emv_Core_Model_DataProcessing_Profile_Interface::getProcess()
     */
    public function getProcess()
    {
        return $this->_process;
    }

    /**
     * Run the process of mass subscriber export
     * - if member_ids is defined and an array, only the subscribers in this list are synced
     * - if dry_mode is defined and contains true, we only create the csv files, no sync will be done at SmartFocus
     * - if test_mode is defined, we run the process in test mode
     *
     * (non-PHPdoc)
     * @see Emv_Core_Model_DataProcessing_Profile_Interface::run()
     */
    public function run()
    {
        $inputData = $this->getInputData();

        $memberIds = array();
        if(isset($inputData['member_ids']) && is_array($inputData['member_ids'])) {
            $memberIds = $inputData['member_ids'];
        }

        $dryMode = false;
        if (isset($inputData['dry_mode'])) {
            $dryMode = $inputData['dry_mode'];
        }

        $testMode = false;
        if (isset($inputData['test_mode'])) {
            $testMode = $inputData['test_mode'];
        }

        $this->massExportCustomers($memberIds, $dryMode, $testMode);

    }

    /**
     * Export customers, by default if member id list is empty, we only take the scheduled members
     *
     * @param array $memberIds - which subscribers/members are allowed to sync
     * @param boolean $dryMode - only allows to create csv file(s), no sync will be triggered
     * @param boolean $testMode - run the export in test mode
     * @param array - sorted by account id
     *    for each account, we have :
     *        - treated_files : all the files (path, name, label) have been created
     *        - uploaded_subscribers : the number of subscribers that have been correctly synced
     */
    public function massExportCustomers(array $memberIds = array(), $dryMode = false, $testMode = false)
    {
        // Set new title
        if ($this->getProcess()) {
            if ($dryMode) {
                $this->getProcess()->setTitle($this->_title . ' - Dry Mode');
            }
            if ($testMode) {
                $this->getProcess()->setTitle($this->_title . ' - Manual Sync');
            }
            // run process
            $this->getProcess()->run();
        }

        // get all active account for batch member service
        $accounts = Mage::helper('emvdatasync')->getActiveEmvAccountsForStore(Emv_DataSync_Helper_Data::TYPE_BATCH);

        // prepare final export info array
        $exportInfo    = array();
        $this->_errors = array();

        $percentForEachAccount = 100;
        if (count($accounts)) {
            $percentForEachAccount = 100 / count($accounts);
        }

        // the current percentage of the process
        $currentPercent = 0;

        $this->_updateProcess('Start Process');
        foreach ($accounts as $accountId => $accountData)
        {
            $storeIds = $accountData['stores'];
            $account  = $accountData['model'];
            $service  = false;
            $currentStore = $storeIds[0];
            $totalTreatedSubscribers = array(
                'treated_subscribers' => array(),
                'rejoined_subscribers' => array(),
            );

            $exportInfo[$accountId] = array(
                'treated_files'        => array(),
                'uploaded_subscribers' => 0,
            );

            $this->_updateProcess('Start proceeding account "' . $account->getName() . '"');

            try {
                // Get api service corresponding to current account
                $service = $this->getApiService($account, $storeIds[0]);
            } catch (Exception $e) {
                $this->_errors[] = "The account {$account->getName()} is invalid : " . $e->getMessage();
            }

            if (!$service) {
                $currentPercent = $currentPercent + $percentForEachAccount;
            } else {
                // only get subscribers from the associated stores
                $subscribers = $subscribers = $this->getSubscriberCollection($memberIds, $storeIds);
                $currentPercent = $currentPercent + (0.03 * $percentForEachAccount);
                $this->_updateProcess('Prepare Subscriber Collection', Zend_Log::INFO, $currentPercent);

                // Add all customer and address attributes that have been mapped into collection
                Mage::getSingleton('emvdatasync/attributeProcessing_config')
                        ->prepareSubscriberCollection($subscribers, $currentStore);
                $currentPercent = $currentPercent + (0.07 * $percentForEachAccount);
                $this->_updateProcess('Add mapping attributes into collection', Zend_Log::INFO, $currentPercent);

                // calculate the number of pages to export
                $pageCount = ceil($subscribers->getSize() / $this->_pageSize);
                $this->_updateProcess(
                    'Collection contains ' . (int)$subscribers->getSize() . ' subscribers'
                        . ' divided in ' . $pageCount . ' pages (' . $this->_pageSize . ' members/page)'
                );

                if ($subscribers->getSize() == 0) {
                    $currentPercent = $currentPercent + (0.97 * $percentForEachAccount);
                } else {

                    // everything should be in GMT
                    $this->_currentTime      = Mage::getModel('core/date')->gmtDate('Ymd_H-i-s');
                    // make a new file prefix
                    $this->_exportFilePrefix = 'magento_account_' . $account->getId() . '_';

                    $percentForEachPage = 0.9 * $percentForEachAccount / $pageCount;
                    for ($curPage = 1; $curPage <= $pageCount; $curPage++) {
                        $this->_updateProcess('Start Proceeding Page ' . $curPage);

                        // Define current page
                        $subscribers->clear()
                            ->setPageSize($this->_pageSize)
                            ->setCurPage($curPage);
                        $this->_updateProcess('Get subscriber list');

                        $this->_updateProcess('Creating csv files');
                        // prepare csv files
                        $arrayReturn   = $this->prepareCustomerCsvFiles($subscribers, $currentStore, $service);
                        $this->_errors = array_merge($arrayReturn['errors'], $this->_errors);

                        // update process for creating file step
                        $currentPercent = $currentPercent + (0.2 * $percentForEachPage);
                        $this->_updateProcessWhileCreatingFiles($arrayReturn, $currentPercent);

                        if ($dryMode) {
                            $currentPercent = $currentPercent + (0.8 * $percentForEachPage);

                            $this->_prepareOutputInfo(
                                $exportInfo[$accountId],
                                array(),
                                $arrayReturn
                            );
                            $this->_updateProcess('Prepare final export information');

                        } else {
                            $this->_updateProcess('Uploading csv files');
                            // send file and get status upload
                            $uploadInfo  = $this->sendFileAndGetStatusUpload(
                                    $arrayReturn['created_files'],
                                    $currentStore,
                                    $service
                                );
                            $this->_errors = array_merge($uploadInfo['errors'], $this->_errors);

                            // update process for uploading file step
                            $currentPercent = $currentPercent + (0.7 * $percentForEachPage);
                            $this->_updateProcess('Uploading files is done.', Zend_Log::INFO, $currentPercent);
                            $this->_updateProcessWhileUploadingFiles(
                                $uploadInfo,
                                $arrayReturn['writtent_subscriber_ids']
                            );

                            // move proceeded files to new placements
                            $this->treatProceededFiles($uploadInfo);
                            // update process for uploading file step
                            $currentPercent = $currentPercent + (0.1 * $percentForEachPage);
                            $this->_updateProcess('Treat uploaded files ', Zend_Log::INFO, $currentPercent);

                            // prepare treated subscriber list
                            $treatedList = $this->getTreatedSubscribers (
                                $uploadInfo['uploaded_files'],
                                $arrayReturn['writtent_subscriber_ids'],
                                $arrayReturn['subscriber_data_list']
                            );
                            $totalTreatedSubscribers['treated_subscribers']  = array_merge(
                                $treatedList['treated_subscribers'],
                                $totalTreatedSubscribers['treated_subscribers']
                            );
                            $totalTreatedSubscribers['rejoined_subscribers'] = array_merge(
                                $treatedList['rejoined_subscribers'],
                                $totalTreatedSubscribers['rejoined_subscribers']
                            );

                            // prepare out put information
                            $this->_prepareOutputInfo(
                                $exportInfo[$accountId],
                                $uploadInfo,
                                $arrayReturn
                            );
                            $this->_updateProcess('Prepare final export information');

                            unset($uploadInfo);
                         }

                        unset($arrayReturn);
                        $this->_updateProcess('End Proceeding Page ' . $curPage, Zend_Log::INFO, $currentPercent);
                    }

                    // clear all fetched data
                    $subscribers->getSelect()->reset();
                    $subscribers->resetData()->clear();
                    unset($subscribers);
                    Mage::getSingleton('emvdatasync/attributeProcessing_config')
                        ->cleanMappedCustomerAttributes($currentStore);
                    $this->_updateProcess('Clean proceeded data');
                } // subscriber collection is not empty

                if (count($totalTreatedSubscribers['treated_subscribers'])) {
                    $errors = $this->massSetMemberLastUpdateDate(
                        $totalTreatedSubscribers['treated_subscribers'],
                        $totalTreatedSubscribers['rejoined_subscribers']
                    );
                    $this->_errors = array_merge($errors, $this->_errors);

                    $this->_updateProcess('Flag uploaded subscribers');
                    if (count($errors)) {
                        $error = "Process has encoutered the following errors when flagging uploaded subsscribers : \n"
                            . implode("\n", $errors);
                        $this->_updateProcess($error);
                    }
                }

                unset($totalTreatedSubscribers);

                try {
                    $service->closeApiConnection();
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_errors[] = "Exception while closing SmartFocus service for account {{$account->getName()}} with message :         "
                        . $e->getMessage();
                }
            } // end if service

            $this->_updateProcess('Total of uploaded files : ' . count($exportInfo[$accountId]['treated_files']));
            $this->_updateProcess('Total of uploaded subscribers : ' . $exportInfo[$accountId]['uploaded_subscribers']);
            $this->_updateProcess('End proceeding account "' . $account->getName() . '"', Zend_Log::INFO, $currentPercent);
            $this->_updateProcess('*************************');
        }

        // add output information into process and finalize it
        if ($this->getProcess()) {
            foreach ($exportInfo as $infoByAccount) {
                foreach ($infoByAccount['treated_files'] as $fileOutput) {
                    $this->getProcess()->addOutputInformation($fileOutput);
                }
            }
            $this->getProcess()->finalize();
        }
        $this->_updateProcess('End Process', Zend_Log::INFO, 100);

        return $exportInfo;
    }

    /**
     * Update process with a new message at give level, or/and update status percentage
     * @param string $logMessage
     * @param int $level (@see Zend_Log for more detail)
     * @param int $statusPercent
     */
    protected function _updateProcess($logMessage, $level = Zend_Log::INFO, $statusPercent = 0)
    {
        if ($this->getProcess()) {
            $this->getProcess()->getLog()->log($logMessage, $level);
            if ($statusPercent > 0) {
                $this->getProcess()->updateStatus($statusPercent);
            }
        }
    }

    /**
     * Update process for creating file step
     *
     * @param array $createdData
     *  - created_files : all file data will be stored in this array, each file will have the following information :
     *        - filename
     *        - path
     *        - time
     *  - writtent_subscriber_ids
     *
     * @param int $currentPercent - the current percentage
     */
    protected function _updateProcessWhileCreatingFiles($createdData, $currentPercent)
    {
        $list = '';
        if (count($createdData['created_files'])) {
            $list = ' List of created files : ';
            foreach ($createdData['created_files'] as $fileData) {
                $list .= $fileData['filename'];
            }
        }
        $this->_updateProcess(
            count($createdData['created_files']) . ' csv files have been created.' . $list,
            Zend_Log::INFO,
            $currentPercent
        );
        if (count($createdData['errors'])) {
            $error = "Process has encoutered the following errors when creating files : \n"
                . implode("\n", $createdData['errors']);
            $this->_updateProcess($error, Zend_Log::ERR);
        }
    }

    /**
     * Update process for uploading file step
     *
     * @param array $uploadInfo
     * @param array $writtenSubscribers
     */
    protected function _updateProcessWhileUploadingFiles($uploadInfo, $writtenSubscribers)
    {
        if (count($uploadInfo['uploaded_files'])) {
            $uploadedFilesStatus = 'List of correctly uploaded files :';
            foreach ($uploadInfo['uploaded_files'] as $fileData) {
                $uploadedFilesStatus .= $fileData['file_data']['filename'];
                if (
                    isset($writtenSubscribers[$fileData['file_data']['filename']])
                ) {
                    $uploadedFilesStatus .= '('
                        .count($writtenSubscribers[$fileData['file_data']['filename']]).' members)';
                }
                $uploadedFilesStatus .= ' ';
            }
            $this->_updateProcess($uploadedFilesStatus);
        }

        if (count($uploadInfo['invalid_files'])) {
            $uploadedFilesStatus = 'List of invalid files :';
            foreach ($uploadInfo['invalid_files'] as $fileData) {
                $uploadedFilesStatus .= $fileData['file_data']['filename'];
                if (
                    isset($writtenSubscribers[$fileData['file_data']['filename']])
                ) {
                    $uploadedFilesStatus .= '('
                        .count($writtenSubscribers[$fileData['file_data']['filename']]).')';
                }
            }
            $this->_updateProcess($uploadedFilesStatus);
        }

        if (count($uploadInfo['errors'])) {
            $error = "Process has encoutered the following errors when uploading files : \n"
                . implode("\n", $uploadInfo['errors']);
            $this->_updateProcess($error, Zend_Log::ERR);
        }
    }

    /**
     * Prepare output information (file name, lable, path) for the process
     *
     * @param reference to array $exportInfo
     * @param array $uploadInfo
     * @param array $createdFileInfo
     */
    protected function _prepareOutputInfo(&$exportInfo, array $uploadInfo, array $createdFileInfo)
    {
        if (isset($createdFileInfo['created_files'])) {
            foreach($createdFileInfo['created_files'] as $fileData) {
                $exportInfo['treated_files'][$fileData['filename']] = array(
                    'path'     => $fileData['path'],
                    'label'    => $fileData['filename'],
                    'filename' => $fileData['filename']
                );
            }
        }

        if (isset($uploadInfo['uploaded_files'])) {
            foreach ($uploadInfo['uploaded_files'] as $fileData) {
                $fileName = $fileData['file_data']['filename'];
                if (isset($exportInfo['treated_files'][$fileName])) {
                    $exportInfo['treated_files'][$fileName] = array(
                        'path'     => $fileData['file_data']['path'],
                        'label'    => $fileName . ' (correctly synced)',
                        'filename' => $fileName
                    );

                    if (
                        isset($createdFileInfo['writtent_subscriber_ids'])
                        && isset($createdFileInfo['writtent_subscriber_ids'][$fileName])
                    ) {
                        $exportInfo['uploaded_subscribers'] += count(
                            $createdFileInfo['writtent_subscriber_ids'][$fileName]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param Varien_Data_Collection_Db $subscribers
     * @param string $storeId
     * @param EmailVision_Api_BatchMemberService $service
     * @return array
     *     subscriber_data_list => list of subscriber data
     *     created_files => list of created files
     *     writtent_subscriber_ids => list of subscriber ids by created files
     *     errors => list of errors encountered during sync
     */
    public function prepareCustomerCsvFiles($subscribers, $storeId, EmailVision_Api_BatchMemberService $service)
    {
        // list of return data
        $arrayReturn = array(
            'subscriber_data_list'    => array(),
            'created_files'           => array(),
            'writtent_subscriber_ids' => array(),
            'errors'                  => array()
        );

        // Add a row foreach customer
        $preparedData = array();
        foreach ($subscribers as $subscriber) {
            // Replace customer email by suscriber one
            $subscriber->setData('email', $subscriber->getSubscriberEmail());
            $subscriberData = Mage::getSingleton('emvdatasync/attributeProcessing_config')
                ->getSubscriberData($subscriber, $storeId);

            $preparedData[] = array(
                'id'     => $subscriber->getId(),
                'fields' => $subscriberData
            );

            // add subscriber data into array - this data will allow us to distinguish who rejoined recently
            $tempSubscriber = array(
                'id' => $subscriber->getId(),
                'rejoined' => false
            );
            if ($subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                if (
                    $subscriber->getData(Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN) != ''
                    && $subscriber->getData(Emv_DataSync_Helper_Service::FIELD_DATE_UNJOIN) != null
                ) {
                    $tempSubscriber['rejoined'] = true;
                }
            }

            $arrayReturn['subscriber_data_list'][$subscriber->getId()] = $tempSubscriber;
        }// end foreach subscribers

        // Mapped header array
        $mappedHeaders = $this->prepareAndGetMappedHeaders($storeId);
        try {
            $csvFileData = $service->createCsvFiles(
                $this,
                array_keys($mappedHeaders),
                $preparedData
            );

            // prepare array return from created file data
            foreach ($csvFileData as $key => $data) {
                // member list for each file
                $arrayReturn['writtent_subscriber_ids'][$data['file']['filename']] = $data['list_member'];
                $arrayReturn['created_files'][] = $data['file'];
            }
        } catch (Exception $e) {
            Mage::logException($e);

            $arrayReturn['errors'][] = 'Exception while building csv file, with message:        ' . $e->getMessage();
        }

        return $arrayReturn;
    }

    /**
     * @param array $filesToUpload
     * @param unknown $storeId
     * @param EmailVision_Api_BatchMemberService $service
     * @param string $closeApi
     * @return array
     */
    public function sendFileAndGetStatusUpload(
        array $filesToUpload = array(),
        $storeId,
        EmailVision_Api_BatchMemberService $service,
        $closeApi = true
    )
    {
        $arrayReturn = array(
            'merge_criteria' => $this->getMergeCriteriaForBatchMember($storeId),
            'uploaded_files' => array(),
            'invalid_files'  => array(),
            'errors'         => array()
        );

        // return
        $uploadDone = true;
        $mappedHeaders = $this->prepareAndGetMappedHeaders($storeId);

        try {
            foreach ($filesToUpload as $data) {
                $uploadId = $service->sendFileToWebservice(
                    $data,
                    $mappedHeaders,
                    $arrayReturn['merge_criteria'],
                    false
                );

                $treatingFileArray = array('file_data' => $data, 'upload_id' => $uploadId, 'status' => false);

                // only checking upload status if we can have upload id
                if ($uploadId != false) {
                    $uploadProcessing = true;

                    // wait until SmartFocus platform completes processing uploaded file
                    // check status's upload for an interval self::WAITING_TIME
                    do {
                        sleep(self::WAITING_TIME);
                        // get status's upload
                        $uploadStatus = $service->getUploadStatus($uploadId, false);

                        if (
                            in_array(
                                $uploadStatus['status'],
                                EmailVision_Api_BatchMemberService::getAccomplishedStatuses()
                            )
                        ) {
                            // update uploading status
                            $treatingFileArray['status'] = $uploadStatus['status'];

                            $uploadProcessing = false;
                            if (
                                $uploadStatus['status'] == EmailVision_Api_BatchMemberService::getStatusOkWithoutError()
                            ) {
                                $arrayReturn['uploaded_files'][] = $treatingFileArray;
                            } else {
                                $arrayReturn['invalid_files'][] = $treatingFileArray;

                                $messageError = 'Error on while uploading file ' . $data['filename'];
                                if ($uploadStatus['details']) {
                                    $messageError .= ", details returned from SmartFocus: " . $uploadStatus['details'];
                                }
                                $arrayReturn['errors'][] = $messageError;
                            }
                        }
                    } while ($uploadProcessing);
                } else {
                    $arrayReturn['invalid_files'][] = $treatingFileArray;
                }
            }

            if ($closeApi == true) {
                $service->closeApiConnection();
            }
        } catch (Exception $e) {
            Mage::logException($e);

            $arrayReturn['errors'][] = "Exception during file uploading process, with message:        "
                . $e->getMessage();
        }

        return $arrayReturn;
    }

    /**
     * Move the synced files to new placement, update their paths in the array respectively
     *
     * @param array $proceededFiles
     */
    public function treatProceededFiles(array &$proceededFiles)
    {
        if (isset($proceededFiles['uploaded_files'])) {
            foreach ($proceededFiles['uploaded_files'] as $key => $fileData) {
                if (isset($fileData['file_data']['filename']) && $fileData['file_data']['path']) {
                    $newPath = Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                        . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
                        . DS . Emv_Core_Helper_Data::UPLOADED_FILE_DIR
                        . DS . $fileData['file_data']['filename'];

                    if (@rename($fileData['file_data']['path'],$newPath)) {
                        $proceededFiles['uploaded_files'][$key]['file_data']['path'] = $newPath;
                    }
                }
            }
        }
    }

    /**
     * Get merge criteria for batch member api requests
     * @param int $storeId
     * @return array
     */
    public function getMergeCriteriaForBatchMember($storeId = null)
    {
        if (Mage::helper('emvdatasync')->getEmailEnabled($storeId)) {
            $mergeCriteria = array(strtoupper(Emv_Core_Model_Service_Member::FIELD_EMAIL));
        } else {
            $mergeCriteria = array(strtoupper(Mage::helper('emvdatasync')->getMappedEntityId($storeId)));
        }
        return $mergeCriteria;
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
        $filename = $this->_exportFilePrefix . 'customers' . $this->_currentTime . '_part' . $index . '.csv';

        // Preparing file path
        $path = Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR . DS . $filename;;
        return array('filename' => $filename, 'path' => $path, 'time' => $this->_currentTime);
    }

    /**
     * Get treated subscribers (synced and rejoined)
     * @param array $uploadedFileNames
     * @param array $listSubscriberByFiles
     * @param array $subscriberData
     * @return array
     *     - treated_subscribers : the total number of synced subscribers
     *     - rejoined_subscribers : the list of rejoined subscribers among the synced ones
     */
    public function getTreatedSubscribers(array $uploadedFileNames, array $listSubscriberByFiles, array $subscriberData)
    {
        $arrayReturn = array(
            'treated_subscribers'  => array(),
            'rejoined_subscribers' => array()
        );
        // Cases: mass export will have a _fileNamesUploaded set, member export will have _subsciberIds
        if (!empty($uploadedFileNames) && count($uploadedFileNames)) {
            foreach ($uploadedFileNames as $fileData) {
                if (isset($fileData['file_data']) && isset($fileData['file_data']['filename'])) {
                    $fileName = $fileData['file_data']['filename'];
                    if (isset($listSubscriberByFiles[$fileName])) {
                        foreach ($listSubscriberByFiles[$fileName] as $subscriberId) {
                            $arrayReturn['treated_subscribers'][] = $subscriberId;
                            if (
                                isset($subscriberData[$subscriberId])
                                && $subscriberData[$subscriberId]['rejoined'] == true
                            ) {
                                $arrayReturn['rejoined_subscribers'][] = $subscriberId;
                            }
                        }
                    }
                }
            }
        }

        return $arrayReturn;
    }

    /**
     * Set member last update date for all exported subscribers
     *
     * @param array $uploadedFileNames
     * @param array $listSubscriberByFiles
     * @param array $subscriberData
     * @return array error
     */
    public function massSetMemberLastUpdateDate(array $subscribersIds, array $rejoinedIds)
    {
        $errors = array();
        try {
            $errorMessage = Mage::helper('emvdatasync/service')->massSetMemberLastUpdateDate(
                $subscribersIds,
                $rejoinedIds
            );

            if ($errorMessage !== true) {
                $errors[] = $errorMessage;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $errors;
    }

    /**
     * @param array $memberIds
     * @param array $storeIds
     * @return Mage_Newsletter_Model_Mysql4_Subscriber_Collection
     */
    public function getSubscriberCollection(array $memberIds = array(), array $storeIds = array())
    {
        /* @var $subscribers Mage_Newsletter_Model_Mysql4_Subscriber_Collection */
        $subscribers = Mage::getModel('newsletter/subscriber')->getCollection();
        if (count($memberIds)) {
            $subscribers->addFieldToFilter('subscriber_id', array('in' => $memberIds));
        } else {
            // only get subscirbers that have been scheduled
            $subscribers->addFieldToFilter(
                'main_table.' . Emv_DataSync_Helper_Service::FIELD_QUEUED,
                Emv_DataSync_Helper_Service::SCHEDULED_VALUE
            );
        }

        if (count($storeIds)) {
            $subscribers->addFieldToFilter('main_table.store_id', array('in' => $storeIds));
        }

        return $subscribers;
    }

    /**
     * Prepare and get mapped header array
     *
     * @param int $storeId
     * @return array
     */
    public function prepareAndGetMappedHeaders($storeId = null)
    {
        // by default the key will be store id, else will be set to undefined
        $key = $storeId;
        if ($key === null) {
            $key = 'undefined';
        }

        if (!isset($this->_mappedHeaders[$key])) {
            $this->_mappedHeaders[$key] = array();

            // retreive all maped attribute values from subscriber, build them into array
            $entityFieldsToSelect = Mage::getSingleton('emvdatasync/attributeProcessing_config')
                ->prepareAndGetMappedCustomerAttributes($storeId);
            foreach ($entityFieldsToSelect as $attribute) {
                $emailVisionKey = $attribute->getMappedEmailVisionKey();
                $emailVisionKey = strtoupper($emailVisionKey);

                $this->_mappedHeaders[$key][strtoupper($emailVisionKey)] = array(
                    'to_replace' => true
                );
            }

            // the entity id can not be replaced
            $entityIdField = Mage::helper('emvdatasync')->getMappedEntityId();
            $this->_mappedHeaders[$key][strtoupper($entityIdField)] = array(
                'to_replace' => false
            );

            $this->_mappedHeaders[$key][strtoupper(Emv_Core_Model_Service_Member::FIELD_UNJOIN)] = array(
                'to_replace' => true
            );
        }

        return $this->_mappedHeaders[$key];
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }
}