<?php
/**
 * Sending Log controller
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Adminhtml_RescheduleController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Inialize action
     */
    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('emailvision/emvemt');
        $this->_title(Mage::helper('emvemt')->__('SmartFocus'))
            ->_title(Mage::helper('emvemt')->__('Re-scheduled Email List'));
    }
    /**
     * Index action - display a grid of rescheduled emails
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('emvemt/adminhtml_resending'));
        $this->renderLayout();
    }

    /**
     * Mass delete action
     */
    public function massDeleteAction()
    {
        $resendingIds = $this->getRequest()->getParam('resending_message');
        if (!is_array($resendingIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emvemt')->__('Please select record(s) !'));
        } else {
            try {
                $resendingMessage = Mage::getModel('emvemt/resending_queue_message');
                foreach ($resendingIds as $id) {
                    $resendingMessage->setId($id)
                        ->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were deleted.', count($resendingIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Check if having a correct permission
     *
     * @return boolean
     */
    protected function _isallowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/emvemt/reschedule');
    }
}