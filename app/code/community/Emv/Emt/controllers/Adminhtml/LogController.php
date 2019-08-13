<?php
/**
 * Sending Log controller
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Adminhtml_LogController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Inialize action
     */
    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('emailvision/emvemt');
        $this->_title(Mage::helper('emvemt')->__('SmartFocus'))
            ->_title(Mage::helper('emvemt')->__('Email Sending Logs'));
    }
    /**
     * Index action - display a grid of all email sending log
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('emvemt/adminhtml_log'));
        $this->renderLayout();
    }

    /**
     * Mass delete action
     */
    public function massDeleteAction()
    {
        $logIds = $this->getRequest()->getParam('log');
        if (!is_array($logIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emvemt')->__('Please select email sending log(s) !'));
        } else {
            try {
                $log = Mage::getModel('emvemt/log');
                foreach ($logIds as $id) {
                    $log->setId($id)
                        ->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were deleted.', count($logIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    public function getErrorAction()
    {
        $id = $this->getRequest()->getParam('id');
        $log = Mage::getModel('emvemt/log');
        $log->load($id);

        $content = $log->getError();
        if ($content) {
            $this->_prepareDownloadResponse('error-log.txt', $content);
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('emvemt')->__('Not found error log !'));
            $this->_redirect('*/*/index');
        }
    }

    /**
     * Check if having a correct permission
     *
     * @return boolean
     */
    protected function _isallowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/emvemt/log');
    }
}