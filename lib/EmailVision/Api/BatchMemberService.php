<?php
/**
 * Batch Member Service Api - Data member updates by flat files
 *
 * @category    EmailVision
 * @package     EmailVision_Api
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 * @copyright Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class EmailVision_Api_BatchMemberService extends EmailVision_Api_Common
{
    /**
     * Default prepare file name method
     */
    const PREPARE_FILE_NAME_METHOD = 'prepareFileName';

    /**
     * CSV field delimiter and enclosure
     */
    const CSV_FIELD_DELIMITER = ',';
    const CSV_FIELD_ENCLOSURE = '"';

    /**
     * Max file size in byte
     */
    const MAX_FILESIZE = 50000000;
    const MAX_ROW = 150000;

    /**
     * File index
     * @var int
     */
    protected $_fileIndex = 0;

    /**
     * Accomplished statuses
     * @var array
     */
    public static $_accomplishedStatuses = array('ERROR','FAILURE','DONE', 'DONE WITH ERROR(S)');

    /**
     * Status OK
     * @var string
     */
    public static $_statusOk = 'DONE';

    /**
     * Get all accomplished statuses for an upload
     *
     * @return array
     */
    public static function getAccomplishedStatuses()
    {
        return self::$_accomplishedStatuses;
    }

    /**
     * Get status OK without error for an upload
     *
     * @return string
     */
    public static function getStatusOkWithoutError()
    {
        return self::$_statusOk;
    }

    /**
     * Create csv files for a given list of members
     *  - you need
     *
     * @param Object $caller
     * @param array $headers
     * @param array $members
     * @param string $delimiter
     * @param string $enclosure
     * @return array
     *   - file : array
     *     + filename
     *     + path
     *     + time
     *   - list_member
     */
    public function createCsvFiles(
        $caller,
        array $headers,
        array $members,
        $delimiter = self::CSV_FIELD_DELIMITER,
        $enclosure = self::CSV_FIELD_ENCLOSURE
    )
    {
        $generatedCsvData = array();

        if (!method_exists($caller, self::PREPARE_FILE_NAME_METHOD)) {
            $this->throwEmailVisionException('The caller needs to have ' . self::PREPARE_FILE_NAME_METHOD);
        } else {
            $result = $this->checkData($headers, $members);
            if ($result) {
                $rowCount = 0;
                $csvTool = new EmailVision_Tools_File_Csv();
                $fileData = null;
                // file handler to write
                $fileHandler = null;
                $fileSizeInBytes = 0;

                $listMember = array();
                // the number of given members
                $totalMemberCount = count($members);
                try {
                    foreach ($members as $memberData) {
                        if (!$fileHandler) {
                            $this->_fileIndex++;
                            $fileData = call_user_func(
                                array($caller, self::PREPARE_FILE_NAME_METHOD),
                                $this->_fileIndex,
                                $memberData
                            );
                            $fileHandler = $this->getFileHandler($fileData['path']);

                            // write header fields into our file
                            $tmpLength = $csvTool->fputcsv($fileHandler, $headers, $delimiter, $enclosure);
                            if ($tmpLength) {
                                $fileSizeInBytes = $tmpLength;
                            }
                            $rowCount++;
                        }

                        $id = $memberData['id'];
                        $fields = $memberData['fields'];

                        $tmpLength = $csvTool->fputcsv($fileHandler, $fields, $delimiter, $enclosure);
                        if ($tmpLength) {
                            $fileSizeInBytes += $tmpLength;
                        }
                        $rowCount++;
                        $listMember[] = $id;

                        // If filesize approach max uploadable (48Mb), close file and set a new one on next turn
                        if (
                            $fileSizeInBytes > self::MAX_FILESIZE
                            || --$totalMemberCount == 0
                            || $rowCount >= self::MAX_ROW
                        ) {
                            fclose($fileHandler);
                            $fileHandler = null;
                            $generatedCsvData[] = array('file' => $fileData, 'list_member' => $listMember);
                            $rowCount = 0;
                        }
                    }
                } catch (Exception $e) {
                    $this->throwEmailVisionException($e);
                }
            }
        }

        return $generatedCsvData;
    }

    /**
     * @param array $headers
     * @param array $members
     * @return boolean
     */
    public function checkData(array $headers, array $members)
    {
        $result = true;
        if (count($headers) == 0) {
            $this->throwEmailVisionException('Please supply a list of header fields!');
        }
        if (count($members) && count($members[0]['fields']) != count($headers)) {
            $this->throwEmailVisionException('Your members do not have the same fields as the header ones!');
        }
        return $result;
    }

    /**
     * Get file handler for a given file path
     * @param string $filePath
     * @return resource
     */
    public function getFileHandler($filePath)
    {
        $streamHandler = @fopen($filePath, 'w');
        if ($streamHandler === false) {
            $this->throwEmailVisionException('Error write to file ' . $filePath);
        }
        return $streamHandler;
    }

    /**
     * Prepare upload parameters and send file to webservice
     *
     * @param array $fileData
     * @param array $mappingFields
     * @param array $mergeCriteria
     * @param string $closeApi
     * @param string $skipFirstLine
     * @param string $separator
     * @return boolean
     */
    public function sendFileToWebservice(
        array $fileData,
        array $mappingFields,
        $mergeCriteria = array(),
        $closeApi = true,
        $skipFirstLine = true,
        $separator = self::CSV_FIELD_DELIMITER
    )
    {
        $fileContent = false;
        if (isset($fileData['path'])) {
            $fileContent = file_get_contents($fileData['path']);
        }

        if ($fileContent === false) {
            $this->throwEmailVisionException('Can not send file to Batch Member service. File content is empty');
        }

        $uploadId = false;
        try {
            $uploadParameters = array();
            $uploadParameters['token']                        = $this->openApiConnection();
            $uploadParameters['mergeUpload']['fileName']      = $fileData['filename'];
            $uploadParameters['mergeUpload']['fileEncoding']  = "UTF-8";
            $uploadParameters['mergeUpload']['separator']     = $separator;
            $uploadParameters['mergeUpload']['autoMapping']   = false;
            $uploadParameters['mergeUpload']['skipFirstLine'] = $skipFirstLine;
            $uploadParameters['mergeUpload']['dateFormat']    = 'MM/DD/YYYY HH24:MI:SS';
            $uploadParameters['mergeUpload']['criteria']      = implode(',', $mergeCriteria);

            $uploadParameters['file']                         = $fileContent;

            // prepare mapping fields
            $colNum = 0;
            $uploadParameters['mergeUpload']['mapping']       = array();
            foreach ($mappingFields as $fieldName => $data) {
                $uploadParameters['mergeUpload']['mapping'][] = array(
                    'colNum'    => ++$colNum,
                    'fieldName' => strtoupper($fieldName),
                    'toReplace' => $data['to_replace']
                );
            }

            $wsResult = $this->soapCall('uploadFileMerge', $uploadParameters);
            if (property_exists($wsResult, 'return')) {
                $uploadId = $wsResult->return;
            }
        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $uploadId;
    }

    /**
     * This method provides the current status of an upload job.
     * Pending status
     *    STORAGE: Upload file has been successfully uploaded and saved
     *    VALIDATED: Upload file has been successfully validated
     *    QUEUED: Upload has been queued for processing
     *    IMPORTING: Database import is starting
     * Error status
     *    ERROR: File validation has failed
     *    FAILURE: Database import has failed
     * Final status
     *    DONE: Upload is complete
     *    DONE WITH ERROR(S): Upload is complete but there were some errors

     * @param string $uploadId
     * @param string $closeApi
     * @return boolean | string
     */
    public function getUploadStatus($uploadId, $closeApi = true)
    {
       $status = false;
        try {
            $params = array(
                'token'    => $this->openApiConnection(),
                'uploadId' => $uploadId
            );

            $wsResult = $this->soapCall('getUploadStatus', $params);
            if (property_exists($wsResult, 'return')) {
                $status = array(
                    'status' => '',
                    'details' => ''
                );
                if (property_exists($wsResult->return, 'status')) {
                    $status['status'] = $wsResult->return->status;
                }
                if (property_exists($wsResult->return, 'details')) {
                    $status['details'] = $wsResult->return->details;
                }
            }
        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $status;
    }

    /**
     * Get log file for an upload
     *
     * @param string $uploadId
     * @param string $closeApi
     * @return boolean -false | string
     */
    public function getLogFileForUpload($uploadId, $closeApi = true)
    {
        $logFile = false;
        try {
            $params = array(
                'token'    => $this->openApiConnection(),
                'uploadId' => $uploadId
            );

            $wsResult = $this->soapCall('getLogFile', $params);
            if (property_exists($wsResult, 'return')) {
                $logFile = $wsResult->return;
            }
        } catch (Exception $e) {
            if (stripos($e->getMessage(), 'READ_FILE_FAILED') === false) {
                $this->decorateException($e);
                $this->throwEmailVisionException($e);
            }
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $logFile;
    }

    /**
     * @param string $uploadId
     * @param string $closeApi
     * @return boolean - false | string - the file content
     */
    public function getBadFileForUpload($uploadId, $closeApi = true)
    {
        $badFile = false;
        try {
            $params = array(
                'token'    => $this->openApiConnection(),
                'uploadId' => $uploadId
            );

            $wsResult = $this->soapCall('getBadFile', $params);
            if (property_exists($wsResult, 'return')) {
                $badFile = $wsResult->return;
            }
        } catch (Exception $e) {
            if (stripos($e->getMessage(), 'READ_FILE_FAILED') === false) {
                $this->decorateException($e);
                $this->throwEmailVisionException($e);
            }
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $badFile;
    }

    /**
     * (non-PHPdoc)
     * @see EmailVision_Api_Common::decorateException()
     */
    public function decorateException(Exception $e)
    {
        parent::decorateException($e);

        if ($e instanceof EmailVision_Api_Exception) {
            $messageException = $e->getMessage();
            $message = '';

            if (stripos($messageException, 'Too many uploads are still pending') !== false) {
                $e->setCode(EmailVision_Api_Exception::EXCEED_UPLOAD_LIMIT);
            }
            if (stripos($messageException, 'Upload file is too big') !== false) {
                $e->setCode(EmailVision_Api_Exception::EXCEED_UPLOAD_FILE_SIZE);
            }
            if (stripos($messageException, 'INVALID_PARAMETERS') !== false) {
                $e->setCode(EmailVision_Api_Exception::INVALID_PARAMETER_UPLOAD);
            }
            if (stripos($messageException, 'GET_FLATUPLOAD_FAILED') !== false) {
                $e->setCode(EmailVision_Api_Exception::INVALID_UPLOAD_ID);
                $message = 'Your upload id does not exist on server!';
            }

            if ($message) {
                $message .= ' - Details from server return : ' . $messageException;
                $e->setMessage($message);
            }
        }
    }
}