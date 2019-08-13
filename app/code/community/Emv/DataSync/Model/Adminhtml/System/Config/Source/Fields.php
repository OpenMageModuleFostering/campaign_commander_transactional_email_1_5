<?php
/**
 * Source model for customer mapping fields in back office configuration
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Adminhtml_System_Config_Source_Fields
{
    /**
     * Return EmailVision customer mapping fields
     *
     * @return Array $emailVisionFields    An array where keys are codes and values are labels
     */
    public function toOptionArray()
    {
        $emailVisionFields = array();
        $savedFields = Mage::helper('emvdatasync')->getEmailVisionFieldsFromConfig();
        if (!empty($savedFields)) {
            foreach ($savedFields as $field) {
                $emailVisionFields[strtolower($field['name'])] = $field['name'];
            }
        } else {
            $emailVisionFields['empty'] = Mage::helper('emvdatasync')->__('Please synchronize with SmartFocus webservice (see above)');
        }

        return $emailVisionFields;
    }
}
