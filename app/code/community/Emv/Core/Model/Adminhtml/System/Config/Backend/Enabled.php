<?php
/**
 * Enabled Backend Model
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_Core_Model_Adminhtml_System_Config_Backend_Enabled extends Mage_Core_Model_Config_Data
{
    /**
     * @var string
     */
    protected $_processName = '';

     /**
     * Allow to enable the service when having a valid account
     *
     * (non-PHPdoc)
     * @see Mage_Core_Model_Abstract::_beforeSave()
     */
    protected function _beforeSave()
    {
        if ($this->getValue()) {
            $account = $this->getFieldsetDataValue('account');
            if (!$account) {
                Mage::throwException(
                    Mage::helper('emvcore')
                        ->__('Could not enable "%s" process ! Please select an account in the list !', $this->_processName)
                );
            }
        }
        parent::_beforeSave();
    }
}
