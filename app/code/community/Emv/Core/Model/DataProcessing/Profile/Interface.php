<?php
/**
 * Data Processing Profile interface
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
interface Emv_Core_Model_DataProcessing_Profile_Interface
{
    /**
     * Intialize Process
     * @return Emv_Core_Model_DataProcessing_Process
     */
    public function initProcess();

    /**
     * Initialize Profile
     * @param Emv_Core_Model_DataProcessing_Process $process
     */
    public function init(Emv_Core_Model_DataProcessing_Process $process = null);

    /**
     * Get input data has been set up
     * @return array
     */
    public function getInputData();

    /**
     * Set input data for profile
     * @param array $input
     */
    public function setInputData(array $input);

    /**
     * Get associated process
     * @return null | Emv_Core_Model_DataProcessing_Process
     */
    public function getProcess();

    /**
     * Run Profile
     */
    public function run();
}