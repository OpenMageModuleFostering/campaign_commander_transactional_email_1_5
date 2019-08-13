<?php
/**
 * Abandoned Cart Conversion Controller
 *
 * @category    Emv
 * @package     Emv_Report
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Report_Adminhtml_ConversionController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Initialise action
     */
    protected function _initAction()
    {
        $this->loadLayout();

        $this->_setActiveMenu('emailvision/abandonment');

        $this->_title(Mage::helper('abandonment_report')->__('SmartFocus'))
            ->_title(Mage::helper('abandonment_report')->__('Abandoned Cart Conversion Report'));
    }

    /**
     * Grid Action
     */
    public function indexAction()
    {
        $this->_initAction();

        $blocks = $this->getLayout()->getAllBlocks();
        $gridBlock = $this->getLayout()->getBlock('abandonment.report.grid.container');
        $filterFormBlock = $this->getLayout()->getBlock('abandonment.report.grid.filter.form');

        $this->_initReportAction(array($gridBlock, $filterFormBlock));

        $this->renderLayout();
    }

    /**
     * Export to Csv Action
     */
    public function exportCsvAction()
    {
        /* @var $block Emv_Report_Block_Adminhtml_Conversion */
        $block = $this->getLayout()->createBlock('abandonment_report/adminhtml_conversion');
        $this->_initReportAction($block);

        $io = new Varien_Io_File();

        // prepare path file
        $path = Mage::getBaseDir('var') . DS . 'export' . DS;
        $name = md5(microtime());
        $file = $path . DS . $name . '.csv';

        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);

        $from = $block->getFilterData()->getFrom();
        $to = $block->getFilterData()->getTo();
        $fromTo = '';
        if ($from || $to) {
            $fromTo = ' (';
            if ($from) {
                $from = new Zend_Date($from, Varien_Date::DATETIME_INTERNAL_FORMAT);
                $format = Mage::app()->getLocale()->getDateTimeFormat(null);
                $from = $from->toString($format);
            } else {
                $from = '/';
            }
            $fromTo .= $from;

            if ($to) {
                $to = new Zend_Date($to, Varien_Date::DATETIME_INTERNAL_FORMAT);
                $format = Mage::app()->getLocale()->getDateTimeFormat(null);
                $to = $to->toString($format);
            } else {
                $to = '/';
            }
            $fromTo .= ' - ' . $to . ')';
        }

        // build data for file
        $data = array(
            array(
                '',
                Mage::helper('abandonment_report')->__('Sent'),
                Mage::helper('abandonment_report')->__('Converted'),
                Mage::helper('abandonment_report')->__('Conversion Rate'),
                Mage::helper('abandonment_report')->__('Total Revenue'),
                Mage::helper('abandonment_report')->__('Average Revenue'),
            ),

            array(
                Mage::helper('abandonment_report')->__('SCA Email 1 - First Reminder') . $fromTo,
                $block->getNbFirstReminderSent(),
                $block->getNbFirstReminderConverted(),
                Mage::helper('emvcore')->formatNumberInLocale($block->getPercentageFirstConversion()) . '%',
                Mage::helper('core')->currency($block->getRevenueFirstConversion(), true, false),
                Mage::helper('core')->currency($block->getAverageRevenueFirstConversion(), true, false)
            ),
            array(
                Mage::helper('abandonment_report')->__('SCA Email 2 - Second Reminder') . $fromTo,
                $block->getNbSecondReminderSent(),
                $block->getNbSecondReminderConverted(),
                Mage::helper('emvcore')->formatNumberInLocale($block->getPercentageSecondConversion()) . '%',
                Mage::helper('core')->currency($block->getRevenueSecondConversion(), true, false),
                Mage::helper('core')->currency($block->getAverageRevenueSecondConversion(), true, false)
            ),
            array(
                Mage::helper('abandonment_report')->__('SCA Email 3 - Third Reminder') . $fromTo,
                $block->getNbThirdReminderSent(),
                $block->getNbThirdReminderConverted(),
                Mage::helper('emvcore')->formatNumberInLocale($block->getPercentageThirdConversion()) . '%',
                Mage::helper('core')->currency($block->getRevenueThirdConversion(), true, false),
                Mage::helper('core')->currency($block->getAverageRevenueThirdConversion(), true, false)
            ),
            array(
                Mage::helper('abandonment_report')->__('Shopping Cart Abandonment') . $fromTo,
                $block->getSumNbReminderSent(),
                $block->getSumNbConvertedCarts(),
                Mage::helper('emvcore')->formatNumberInLocale($block->getPercentageConversion()) . '%',
                Mage::helper('core')->currency($block->getSumAmountConvertedCarts(), true, false),
                Mage::helper('core')->currency($block->getAverageRevenueConversion(), true, false)
            ),

            array(
                Mage::helper('abandonment_report')->__('Total'),
                $block->getTotalSumNbReminderSent(),
                $block->getTotalSumNbConvertedCarts(),
                Mage::helper('emvcore')->formatNumberInLocale($block->getTotalPercentageConversion()) . '%',
                Mage::helper('core')->currency($block->getTotalSumAmountConvertedCarts(), true, false),
                Mage::helper('core')->currency($block->getTotalAverageRevenueConversion(), true, false)
            ),
        );

        foreach ($data as $line)
        {
            $io->streamWriteCsv($line);
        }

        $io->streamUnlock();
        $io->streamClose();

        $this->_prepareDownloadResponse('cart_conversion.csv', array( 'type' => 'filename', 'value' => $file, 'rm' => true ));
    }

    /**
     * Initialise report action - prepare block objects
     *
     * @param array $blocks
     * @return Emv_Report_Adminhtml_ConversionController
     */
    public function _initReportAction($blocks)
    {
        if (!is_array($blocks)) {
            $blocks = array($blocks);
        }

        $params = $this->_extractParams();

        foreach ($blocks as $block) {
            if ($block) {
                $block->setFilterData($params);
            }
        }

        return $this;
    }

    /**
     * Extract data from request
     *
     * @return Varien_Object
     */
    protected function _extractParams()
    {
        $requestData = Mage::helper('adminhtml')->prepareFilterString($this->getRequest()->getParam('filter'));
        $requestData = $this->_filterDates($requestData, array('from', 'to'));
        $params = new Varien_Object();

        foreach ($requestData as $key => $value) {
            if (!empty($value)) {
                $params->setData($key, $value);
            }
        }

        return $params;
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