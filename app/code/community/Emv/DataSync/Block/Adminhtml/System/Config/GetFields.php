<?php
/**
 * Block for the get fields button in back-office configuration
 *
 * @category   Emv
 * @package    Emv_DataSync
 * @copyright  Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Block_Adminhtml_System_Config_GetFields extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself
     *
     * @return Emv_DataSync_Block_Adminhtml_System_Config_GetFields
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('smartfocus/datasync/system/config/getfields.phtml');
        }
        return $this;
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get field url
     *
     * @return string
     */
    public function getGetFieldsUrl()
    {
        $website = $this->getRequest()->getParam('website');
        $store   = $this->getRequest()->getParam('store');
        $url = Mage::getSingleton('adminhtml/url')->getUrl(
                'emv_datasync/dataSync/getMemberFields',
                array('website'=>$website, 'store'=>$store)
            );

        return $url;
    }
}
