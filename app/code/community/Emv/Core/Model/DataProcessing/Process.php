<?php
/**
 * Process Model
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_DataProcessing_Process extends Mage_Core_Model_Abstract
{
    /**
     * State constants
     */
    const STATE_NEW = 0;
    const STATE_PROCESSING = 3;
    const STATE_FAILED = 5;
    const STATE_SUCCESS = 10;

    /**
     * Working dir constant
     */
    const DATAPROCESSING_DIR = 'dataprocessing';

    /**
     * Type Constant
     */
    const TYPE_DATA_SYNC = 'data_sync';

    /**
     * Process log
     * @var Emv_Core_Model_DataProcessing_Process_Log
     */
    protected $_log = null;

    /**
     * Get base directory for the module
     *
     * @return string
     */
    public static function getBaseDir()
    {
        $path = Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
            . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
            . DS . self::DATAPROCESSING_DIR;

        $args = array(
            'path' => $path,
        );
        $mageFile = new Varien_Io_File();
        $mageFile->setAllowCreateFolders(true);
        $mageFile->open($args);

        return $path;
    }

    /**
     * Retrieve relative path by given file name
     *
     * @param string $fileName file name
     *
     * @return string
     */
    public static function getRelativePath($fileName)
    {
        $baseDir = self::getBaseDir();
        if (substr($fileName, 0, strlen($baseDir)) == $baseDir) {
            $fileName = substr($fileName, strlen($baseDir) - strlen($fileName));
        }
        return $fileName;
    }

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emvcore/dataProcessing_process');
        $this->setState(self::STATE_NEW);
        $this->setCreatedAt(Mage::getModel('core/date')->gmtDate());
    }

    /**
     * Update status of the process
     *
     * @param int $status
     *
     * @return void
     */
    public function updateStatus($status)
    {
        $this->setStatus($status);
        $this->setUpdatedAt(Mage::getModel('core/date')->gmtDate());
        $this->save();
    }

    /**
     * Change state of the process
     *
     * @param int $state
     *
     * @return void
     */
    public function changeState($state)
    {
        if (in_array($state, array_keys($this->getStatesArray()))) {
            $this->setState($state);
            $this->setUpdatedAt(Mage::getModel('core/date')->gmtDate());
            $this->save();
        }
    }

    /**
     * Run process
     *
     * @return void
     */
    public function run()
    {
        $this->setState(self::STATE_PROCESSING);
        $this->setStatus(0);
        $this->save();
    }

    /**
     * Finalize process
     *
     * @param Exception $exception
     * @param int       $state
     *
     * @return void
     */
    public function finalize(Exception $exception = null, $state = null)
    {
        if (is_null($state) || !in_array($state, array_keys($this->getStatesArray()))) {
            if (is_null($exception)) {
                $this->setState(self::STATE_SUCCESS);
            } else {
                $this->setState(self::STATE_FAILED);
            }
        } else {
            $this->setState($state);
        }
        $this->setTerminatedAt(Mage::getModel('core/date')->gmtDate());
        $this->setStatus(100);
        $this->save();
    }

    /**
     * Kill process in case of exception
     *
     * @return void
     */
    public function kill()
    {
        $this->setState(self::STATE_FAILED);
        $this->setTerminatedAt(Mage::getModel('core/date')->gmtDate());
        $this->setStatus(100);
        $this->save();
    }

    /**
     * Get states array
     *
     * @return array
     */
    public function getStatesArray()
    {
        return array(
            self::STATE_NEW => Mage::helper('emvcore')->__('New'),
            self::STATE_PROCESSING => Mage::helper('emvcore')->__('Processing'),
            self::STATE_FAILED => Mage::helper('emvcore')->__('Failed'),
            self::STATE_SUCCESS => Mage::helper('emvcore')->__('Success')
        );
    }

    /**
     * Get base process dir
     *
     * @return string
     */
    public function getProcessDir()
    {
        $processDir = self::getBaseDir() . DS . $this->getId();
        if (!is_dir($processDir)) {
            mkdir($processDir);
        }
        return $processDir;
    }

    /**
     * Get log file name
     *
     * @return string
     */
    public function getLogFileName()
    {
        return $this->getProcessDir().DS.'log.txt';
    }

    /**
     * Set log object
     *
     * @param Emv_Core_Model_DataProcessing_Process_Log $log
     *
     * @return void
     */
    protected function _setLog(Emv_Core_Model_DataProcessing_Process_Log $log)
    {
        $this->_log = $log;
    }

    /**
     * Get report log object
     *
     * @return Emv_Core_Model_DataProcessing_Process_Log
     */
    public function getLog()
    {
        return $this->_log;
    }

    /**
     * Get log content
     *
     * @return string
     */
    public function getLogContent()
    {
        return file_get_contents($this->getLogFileName());
    }

    /**
     * Init log object
     *
     * @param string $className
     *
     * @return void
     * @throws Emv_Core_Model_DataProcessing_Exception
     */
    public function initLog($className = null)
    {
        if (is_null($className)) {
            $className = 'emvcore/dataProcessing_process_log';
        }
        $log = Mage::getModel($className);
        if (!($log instanceof Emv_Core_Model_DataProcessing_Process_Log)) {
            throw new Emv_Core_Model_DataProcessing_Exception('Can not initialize a log');
        }
        $logFileName = $this->getLogFileName();
        file_put_contents($logFileName, '');
        $log->init($logFileName);
        $this->_setLog($log);
    }

    /**
     * Check if log data can be retrieved
     *
     * @return boolean
     */
    public function checkLogData()
    {
        return is_readable($this->getLogFileName());
    }

    /**
     * Delete process with related resources
     *
     * @return void
     * @see Mage_Core_Model_Abstract::delete()
     */
    public function delete()
    {
        try {
            $resources = array(
                $this->getLogFileName(),
                $this->getProcessDir()
            );

            foreach ($resources as $resource) {
                if (file_exists($resource)) {
                    if (is_dir($resource)) {
                        rmdir($resource);
                    } else {
                        unlink($resource);
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException(
                Mage::helper('emvcore')->__(
                    'Unable to delete resources related to the process'
                )
            );
        }

        parent::delete();
    }

    /**
     * Set output information
     * @param array $output
     * @return Emv_Core_Model_DataProcessing_Process
     */
    public function setOutputInformation(array $output)
    {
        foreach ($output as $dataInfo) {
            $this->addOutputInformation($output);
        }
        return $this;
    }

    /**
     * @param array $output
     * @throws Emv_Core_Model_DataProcessing_Exception
     * @return Emv_Core_Model_DataProcessing_Process
     */
    public function addOutputInformation(array $output)
    {
        if (!isset($output['filename']) || !isset($output['path']) || !isset($output['label']) ) {
            throw new Emv_Core_Model_DataProcessing_Exception('Your output information is not correct');
        }

        if (!isset($this->_data['output_information'])) {
            $this->_data['output_information'] = array();
        }

        $this->_data['output_information'][] = $output;

        return $this;
    }
}