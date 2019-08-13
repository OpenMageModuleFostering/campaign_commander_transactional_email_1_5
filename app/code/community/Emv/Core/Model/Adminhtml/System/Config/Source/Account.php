<?php
/**
 * Account system config source
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Model_Adminhtml_System_Config_Source_Account
{
    public function toOptionArray()
    {
        $collection = Mage::getResourceModel('emvcore/account_collection');
        $options = array();

        foreach ($collection as $item) {
            $options[] = array(
               'value' => $item->getId(),
               'label' => $item->getName(),
            );
        }

        array_unshift($options, array('value'=>'', 'label'=> Mage::helper('emvcore')->__('--Please Select--')));

        return $options;
    }
}
