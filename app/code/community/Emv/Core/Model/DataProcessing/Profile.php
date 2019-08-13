<?php
/**
 * Abstract Profile
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
abstract class Emv_Core_Model_DataProcessing_Profile
    implements Emv_Core_Model_DataProcessing_Profile_Interface
{
    /**
     * Profile type
     * @var string
     */
    protected $_type = 'profile';

    /**
     * Profile title
     * @var string
     */
    protected $_title = 'Profile';

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
     * Class name for log
     * @var string
     */
    protected $_logClassName = null;

    protected $_inputData = array();

    protected $_isInitialized = false;

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
        $this->_validateInit($process);
        $process->initLog($this->_logClassName);
        $this->_process = $process;
        $this->_isInitialized = true;
        Mage::dispatchEvent('smartfocus_dataprocessing_profile_after_init', array('profile' => $this));
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
     * (non-PHPdoc)
     * @see Emv_Core_Model_DataProcessing_Profile_Interface::getProcess()
     */
    public function getProcess()
    {
        return $this->_process;
    }

    /**
     * Validate process used to profile init
     *
     * @param Emv_Core_Model_DataProcessing_Process $process
     *
     * @return void
     * @throws Emv_Core_Model_DataProcessing_Exception
     */
    private function _validateInit(Emv_Core_Model_DataProcessing_Process $process = null)
    {
        if ($this->_isInitialized) {
            throw new Emv_Core_Model_DataProcessing_Exception('Profile is already intialized');
        }
        if (is_null($process)) {
            throw new Emv_Core_Model_DataProcessing_Exception('Process can not be null');
        }
        if (!($process instanceof Varien_Object)) {
            throw new Emv_Core_Model_DataProcessing_Exception('Process must be an instance of Varien_Object class');
        }
        if (($process instanceof Emv_Core_Model_DataProcessing_Process) && (!$process->getId())) {
            throw new Emv_Core_Model_DataProcessing_Exception('Process should be registered before profile execution');
        }
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
        $process->setType($this->_type);

        $process->save();
        return $process;
    }

    /**
     * Run profile
     *
     * @return void
     * @throws Emv_Core_Model_DataProcessing_Exception
     */
    public function run()
    {
        Mage::dispatchEvent('smartfocus_dataprocessing_profile_before_run', array('profile' => $this));
        $this->checkLocks();
        try {
            if (!$this->_isInitialized) {
                $this->init($this->getDefaultProcess());
            }
        } catch (Exception $e) {
            $this->_finalize($e);
            throw $e;
        }

        try {
            $this->getProcess()->run();
            $this->_run();
        } catch (Exception $e) {
            $this->_finalize($e);
            Mage::dispatchEvent('smartfocus_dataprocessing_profile_after_failure', array('profile' => $this));
            throw $e;
        }
        $this->_finalize();
        Mage::dispatchEvent('smartfocus_dataprocessing_profile_after_success', array('profile' => $this));
    }

    /**
     * General finalize function
     *
     * @param Exception $exception
     *
     * @return void
     */
    protected function _finalize(Exception $exception = null)
    {
        $this->getProcess()->finalize($exception);
        $this->_afterFinalize($exception);
        Mage::dispatchEvent('smartfocus_dataprocessing_profile_after_finalize', array('profile' => $this));
    }

    /**
     * Custom _finalization function.
     * Allows to add some logic or manage the exception in some special way.
     * Implementation is not required.
     *
     * @param Exception $exception
     *
     * @return void
     */
    protected function _afterFinalize(Exception $exception = null)
    {
    }

    /**
     * Main profile function. Launch a profile
     *
     * @return void
     */
    abstract protected function _run();
}