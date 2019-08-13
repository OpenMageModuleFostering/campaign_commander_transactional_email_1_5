<?php
/**
 * This controller is used to manage all Data Processing Process view (grid list, edit/new form)
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Adminhtml_DataProcessingController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Current process
     * @var Emv_Core_Model_DataProcessing_Process
     */
    protected $_process = null;

    /**
     * Initialise action
     *  - load all layout
     *  - set active menu SmartFocus
     */
    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('emailvision/emvcore');
        // Define module dependent translate
        $this->setUsedModuleName('Emv_Core');
        $this->_title(Mage::helper('emvcore')->__('SmartFocus'))
            ->_title(Mage::helper('emvcore')->__('Data Process'));
    }

    /**
     * Action to display Data Processing Porcess grid
     */
    public function indexAction()
    {
        if ($this->getRequest()->getParam('ajax')) {
            $this->_forward('grid');
            return;
        }

        // save all messages from session
        $this->getLayout()->getMessagesBlock()->setMessages(
            $this->_getSession()->getMessages()
        );

        $this->_initAction();
        $this->_addContent(
            $this->getLayout()->createBlock('emvcore/adminhtml_dataProcessing_process','process')
        );
        $this->renderLayout();
    }

    /**
     * Data Processing Process Grid action for ajax request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('emvcore/adminhtml_dataProcessing_process_grid')->toHtml()
        );
    }

    /**
     * Get output file content
     *
     * @return void
     */
    public function getOutputFileAction()
    {
        try {
            $fullFileName = base64_decode($this->getRequest()->get('path'));
            $fileName = base64_decode($this->getRequest()->get('filename'));
            if (is_readable($fullFileName)) {
                $content = file_get_contents($fullFileName);
                return $this->_prepareDownloadResponse($fileName, $content);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError(Mage::helper('emvcore')->__('Error during get output file'));
            Mage::logException($e);
        }
        $this->_redirect('*/dataProcessing/');
    }

    /**
     * Get log content
     *
     * @return void
     */
    public function logAction()
    {
        try {
            $content = $this->_getProcess()->getLogContent();
            return $this->_prepareDownloadResponse($this->_getProcess()->getId().'-log.txt', $content);
        } catch (Exception $e) {
            $this->_getSession()->addError(Mage::helper('emvcore')->__('Error during get process log'));
            Mage::logException($e);
        }
        $this->_redirect('*/dataProcessing/');
    }

    /**
     * Get current process
     *
     * @return Emv_Core_Model_DataProcessing_Process
     */
    protected function _getProcess()
    {
        if (is_null($this->_process)) {
            $processId = $this->getRequest()->get('id');
            if (empty($processId)) {
                Mage::throwException('Process is empty');
            }
            $process = Mage::getModel('emvcore/dataProcessing_process')->load($processId);
            if (!$process->getId()) {
                Mage::throwException('Process does not exist : ' . $processId);
            }
            $this->_process = $process;
        }
        return $this->_process;
    }

    /**
     * Delete mass action
     *
     * @return void
     */
    public function massDeleteAction()
    {
        $processIds = $this->getRequest()->getParam('ids');
        if (!is_array($processIds)) {
            $this->_getSession()->addError(Mage::helper('emvcore')->__('Please select something!'));
        } else {
            $deleted = 0;
            foreach ($processIds as $processId) {
                try {
                    $process = Mage::getSingleton('emvcore/dataProcessing_process')->load($processId);
                    $process->delete();
                    $deleted++;
                } catch (Mage_Core_Exception $e) {
                    $this->_getSession()->addError(
                        Mage::helper('emvcore')->__('Unable to delete the process #%s. Reason: %s', $processId, $e->getMessage())
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError(
                        Mage::helper('emvcore')->__('Unable to delete the process #%s', $processId)
                    );
                    Mage::logException($e);
                }
            }
        }
        if ($deleted > 1) {
            $this->_getSession()->addSuccess(
                Mage::helper('emvcore')->__('%s records were successfully deleted', $deleted)
            );
        } elseif ($deleted == 1) {
            $this->_getSession()->addSuccess(
                Mage::helper('emvcore')->__('One record was successfully deleted')
            );
        }
        $this->_redirect('*/*/');
    }

    /**
     * Check if having a correct permission
     *
     * @return boolean
     */
    protected function _isallowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/emvdataprocessing');
    }
}