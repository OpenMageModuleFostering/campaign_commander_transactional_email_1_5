<?php
/**
 * Process Resource Model
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Mysql4_DataProcessing_Process extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Serializeable field: output_information
     *
     * @var array
     */
    protected $_serializableFields = array(
        'output_information' => array(array(), array())
    );

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    protected function _construct()
    {
        $this->_init('emvcore/dataprocessing_process','id');
    }
}