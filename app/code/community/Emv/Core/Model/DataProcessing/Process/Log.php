<?php
/**
 * Process Log Model
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_DataProcessing_Process_Log
{
    /**
     * Log filename
     * @var string
     */
    protected $_filename;

    /**
     * Log object
     * @var Zend_Log
     */
    private $_log = null;

    /**
     * Init log with a name for a log file
     *
     * @param string $filename
     *
     * @return void
     * @throws Exception
     */
    public function init($filename)
    {
        if (empty($filename) || !is_writeable($filename)) {
            throw new Exception('Log file is empty or not writeable');
        }
        $this->_filename = $filename;
        $format = '%timestamp% %priorityName% : %message%' . PHP_EOL;
        $writer = new Zend_Log_Writer_Stream($this->_filename);
        $writer->setFormatter(new Zend_Log_Formatter_Simple($format));
        $this->_log = new Zend_Log($writer);
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->_filename;
    }

    /**
     * Put log message
     *
     * @param string $message
     * @param int    $level
     *
     * @return void
     */
    public function log($message, $level = null)
    {
        $this->_log->log(print_r($message, 1), $level, $this->_filename);
    }

    /**
     * Put error message
     *
     * @param string $message
     *
     * @return void
     */
    public function error($message)
    {
        $this->log($message, Zend_Log::ERR);
    }

    /**
     * Put warning message
     *
     * @param string $message
     *
     * @return void
     */
    public function warning($message)
    {
        $this->log($message, Zend_Log::WARN);
    }

    /**
     * Put info message
     *
     * @param string $message
     *
     * @return void
     */
    public function info($message)
    {
        $this->log($message, Zend_Log::INFO);
    }
}