<?php
/**
 * Abandoned Cart Detail Controller
 *
 * @category    Emv
 * @package     Emv_Report
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Report_Adminhtml_DetailsController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Intialise action - set active menu and build title
     */
    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('emailvision/abandonment');

        $this->_title(Mage::helper('abandonment_report')->__('SmartFocus'))
            ->_title(Mage::helper('abandonment_report')->__('Converted Cart Report'));
    }

    /**
     * Grid action
     */
    public function indexAction()
    {
        Mage::register('abandonmentalertid', $this->getRequest()->getParam('alertid', ''));
        Mage::register('from', $this->getRequest()->getParam('from', null));
        Mage::register('to', $this->getRequest()->getParam('to', null));

        $this->_initAction();

        $this->_addContent($this->getLayout()->createBlock('abandonment_report/adminhtml_details'));
        $this->renderLayout();
    }

    /**
     * Export converted cart detail grid to CSV format
     */
    public function exportDetailsCsvAction()
    {
        Mage::register('abandonmentalertid', $this->getRequest()->getParam('alertid', ''));
        Mage::register('from', $this->getRequest()->getParam('from', null));
        Mage::register('to', $this->getRequest()->getParam('to', null));

        $fileName   = 'converted_cart_details.csv';
        $grid       = $this->getLayout()->createBlock('abandonment_report/adminhtml_details_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     * Check menu access
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/abandonment');
    }

}