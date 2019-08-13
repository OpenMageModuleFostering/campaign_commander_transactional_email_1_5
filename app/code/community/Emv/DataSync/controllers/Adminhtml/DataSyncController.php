<?php
/**
 * Data Sync Controller
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Adminhtml_DataSyncController extends Mage_Adminhtml_Controller_Action
{
    /**
     * (non-PHPdoc)
     * @see Mage_Core_Controller_Varien_Action::_construct()
     */
    public function _construct()
    {
        // Define module dependent translate
        $this->setUsedModuleName('Emv_DataSync');
    }

    /**
     * Get EmailVision fields from api object
     */
    public function getMemberFieldsAction()
    {
        $accountId = $this->getRequest()->getParam('account_id', null);
        $preparedFields = array();

        $account = Mage::getModel('emvcore/account');
        if (!$accountId) {
            $account = Mage::helper('emvdatasync')->getEmvAccountForStore();
        } else {
            $account->load($accountId);
        }

        // get account id for
        if ($account->getId()) {
            try {
                $service = Mage::getModel('emvcore/service_member');
                $service->setAccount($account);
                $fields = $service->getEmailVisionFields();

                $notAllowed = array(
                    Emv_Core_Model_Service_Member::FIELD_CLIENT_ID,
                    Emv_Core_Model_Service_Member::FIELD_MEMBER_ID,
                    Emv_Core_Model_Service_Member::FIELD_UNJOIN
                );
                foreach ($fields as $data) {
                    if (!in_array($data['name'], $notAllowed)) {
                        $preparedFields[] = $data;
                    }
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emvdatasync')->__($e->getMessage()));
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('emvdatasync')
                    ->__('Please select an account for "Triggered Exports". Access to Member service is required')
            );
        }

        if (count($preparedFields)) {
            Mage::helper('emvdatasync')->saveEmailVisionFieldsInConfig($preparedFields);
            // SmartFocus fields need to be in lower case
            Mage::helper('emvdatasync')->saveMappedEntityId(
                strtolower(Emv_Core_Model_Service_Member::FIELD_SUBSCRIBER_ID)
            );

            $config = Mage::getModel('core/config');
            $config->removeCache();

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('emvdatasync')->__('Retrieved successfully SmartFocus member fields')
            );
        }

        $this->_redirect('adminhtml/system_config/edit/section/emvdatasync/');
    }
}

