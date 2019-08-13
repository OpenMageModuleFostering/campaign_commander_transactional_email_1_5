<?php
/**
 * Conversion block
 *
 * @category    Emv
 * @package     Emv_Report
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Report_Block_Adminhtml_Conversion extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'report_sales_sales';
        $this->_headerText = Mage::helper('abandonment_report')->__('Total Abandoned Cart Report');

        parent::__construct();

        $this->setTemplate('report/grid/container.phtml');

        $this->_removeButton('add');

        $this->addButton('filter_form_submit', array(
            'label'     => Mage::helper('reports')->__('Show Report'),
            'onclick'   => 'filterFormSubmit()'
        ));

        $this->addButton('export_csv', array('onclick' => 'filterExportSubmit()',
            'label' => Mage::helper('abandonment')->__('Export CSV')));
    }

    /**
     * @return string
     */
    public function getFilterUrl()
    {
        $this->getRequest()->setParam('filter', null);
        return $this->getUrl('*/*/index', array('_current' => true));
    }

    /**
     * @return string
     */
    public function getExportUrl()
    {
        $this->getRequest()->setParam('filter', null);
        return $this->getUrl('*/*/exportCsv', array('_current' => true));
    }

    /**
     * The number of sent emails for the first reminder
     *
     * @return float
     */
    public function getNbFirstReminderSent()
    {
        return Mage::getModel('abandonment/stats')->getResource()
            ->getTotalFirstReminderSent($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
    }

    /**
     * The number of sent emails for the second reminder
     *
     * @return float
     */
    public function getNbSecondReminderSent()
    {
        return Mage::getModel('abandonment/stats')->getResource()
            ->getTotalSecondReminderSent($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
    }

    /**
     * The number of sent emails for the third reminder
     *
     * @return float
     */
    public function getNbThirdReminderSent()
    {
        return Mage::getModel('abandonment/stats')->getResource()
            ->getTotalThirdReminderSent($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
    }

    /**
     * The number of converted abandoned carts for the first reminder
     *
     * @return float
     */
    public function getNbFirstReminderConverted()
    {
        return Mage::getModel('abandonment/orderflag')->getResource()
            ->getNbFirstReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
    }

    /**
     * The number of converted abandoned carts for the second reminder
     *
     * @return float
     */
    public function getNbSecondReminderConverted()
    {
        return Mage::getModel('abandonment/orderflag')->getResource()
            ->getNbSecondReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
    }

    /**
     * The number of converted abandoned carts for the third reminder
     *
     * @return float
     */
    public function getNbThirdReminderConverted()
    {
        return Mage::getModel('abandonment/orderflag')->getResource()
            ->getNbThirdReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
    }

    /**
     * The percentage of converted abandoned carts for the first reminder
     *
     * @return float
     */
    public function getPercentageFirstConversion()
    {
        $percent = 0;
        $nbSent = $this->getNbFirstReminderSent();
        if ($nbSent) {
            $nbConverted = $this->getNbFirstReminderConverted();
            $percent = $nbConverted / $nbSent * 100;
        }

        return  $percent;
    }

    /**
     * The percentage of converted abandoned carts for the second reminder
     *
     * @return float
     */
    public function getPercentageSecondConversion()
    {
        $percent = 0;
        $nbSent = $this->getNbSecondReminderSent();
        if ($nbSent) {
            $nbConverted = $this->getNbSecondReminderConverted();
            $percent = $nbConverted / $nbSent * 100;
        }

        return  $percent;
    }

    /**
     * The percentage of converted abandoned carts for the third reminder
     *
     * @return float
     */
    public function getPercentageThirdConversion()
    {
        $percent = 0;
        $nbSent = $this->getNbThirdReminderSent();
        if ($nbSent) {
            $nbConverted = $this->getNbThirdReminderConverted();
            $percent = $nbConverted / $nbSent * 100;
        }

        return  $percent;
    }

    /**
     * Get total amount of all converted abandoned carts for the first reminder
     *
     * @param string $includeContainer
     * @return float
     */
    public function getRevenueFirstConversion()
    {
        $sumAmount = Mage::getModel('abandonment/orderflag')->getResource()
            ->getSumAmountFirstReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());

        return $sumAmount;
    }

    /**
     * Get total amount of all converted abandoned carts for the second reminder
     *
     * @param string $includeContainer
     * @return float
     */
    public function getRevenueSecondConversion()
    {
        $sumAmount = Mage::getModel('abandonment/orderflag')->getResource()
            ->getSumAmountSecondReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());

        return $sumAmount;
    }

    /**
     * Get total amount of all converted abandoned carts for the third reminder
     *
     * @param string $includeContainer
     * @return float
     */
    public function getRevenueThirdConversion()
    {
        $sumAmount = Mage::getModel('abandonment/orderflag')->getResource()
            ->getSumAmountThirdReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());

        return $sumAmount;
    }

    /**
     * Get average revenue of all converted abandoned carts for the first reminder
     *
     * @param string $includeContainer
     * @return float
     */
    public function getAverageRevenueFirstConversion()
    {
        $average = 0;
        $resource = Mage::getModel('abandonment/orderflag')->getResource();

        $nbConvertedCarts= $resource->getNbFirstReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
        if ($nbConvertedCarts) {
            $total = $resource->getSumAmountFirstReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
            $average = $total / $nbConvertedCarts;
        }

        return $average;
    }

    /**
     * Get average revenue of all converted abandoned carts for the second reminder
     *
     * @param string $includeContainer
     * @return float
     */
    public function getAverageRevenueSecondConversion()
    {
        $average = 0;
        $resource = Mage::getModel('abandonment/orderflag')->getResource();

        $nbConvertedCarts= $resource->getNbSecondReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
        if ($nbConvertedCarts) {
            $total = $resource->getSumAmountSecondReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
            $average = $total / $nbConvertedCarts;
        }

        return $average;
    }

    /**
     * Get average revenue of all converted abandoned carts for the third reminder
     *
     * @param string $includeContainer
     * @return float
     */
    public function getAverageRevenueThirdConversion()
    {
        $average = 0;
        $resource = Mage::getModel('abandonment/orderflag')->getResource();

        $nbConvertedCarts= $resource->getNbThirdReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
        if ($nbConvertedCarts) {
            $total = $resource->getSumAmountThirdReminderConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
            $average = $total / $nbConvertedCarts;
        }

        return $average;
    }

    /**
     * Get total revenue of all converted abandoned carts during a given period
     *
     * @param string $includeContainer
     * @return string
     */
    public function getSumAmountConvertedCarts()
    {
        $sumAmount = Mage::getModel('abandonment/orderflag')->getResource()
            ->getSumAmountConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
        return $sumAmount;
    }

    /**
     * Get total revenue of conversion for all converted abandoned carts
     *
     * @param string $includeContainer
     * @return string
     */
    public function getTotalSumAmountConvertedCarts()
    {
        $sumAmount = Mage::getModel('abandonment/orderflag')->getResource()->getSumAmountConvertedCarts();
        return $sumAmount;
    }

    /**
     * Get the total number of converted abandoned carts during a specific period
     *
     * @return int
     */
    public function getSumNbConvertedCarts()
    {
        $nbFlaged = Mage::getModel('abandonment/orderflag')->getResource()
            ->getSumNbConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
        return  $nbFlaged;
    }

    /**
     * Get the total number of converted abandoned carts
     * @return int
     */
    public function getTotalSumNbConvertedCarts()
    {
        $nbFlaged = Mage::getModel('abandonment/orderflag')->getResource()
            ->getSumNbConvertedCarts();
        return $nbFlaged;
    }

    /**
     * Get the total number of sent emails during a specific period
     *
     * @return int
     */
    public function getSumNbReminderSent()
    {
        $nbSent = Mage::getModel('abandonment/stats')->getResource()
            ->getSumReminderSent($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
        return $nbSent;
    }

    /**
     * Get the total number of sent emails
     *
     * @return int
     */
    public function getTotalSumNbReminderSent()
    {
        $nbSent = Mage::getModel('abandonment/stats')->getResource()
            ->getSumReminderSent();
        return  $nbSent;
    }

    /**
     * Get total pourcentage conversion during a specific period
     *
     * @return float
     */
    public function getPercentageConversion()
    {
        $percent = 0;

        $numberSent = $this->getSumNbReminderSent();
        if ($numberSent > 0) {
            $nbConvertedCarts = $this->getSumNbConvertedCarts();
            $percent = $nbConvertedCarts / $numberSent * 100;
        }

        return $percent;
    }

    /**
     * Get total pourcentage conversion
     *
     * @return float
     */
    public function getTotalPercentageConversion()
    {
        $percent = 0;

        $numberSent = $this->getTotalSumNbReminderSent();
        if ($numberSent > 0) {
            $nbConvertedCarts = $this->getTotalSumNbConvertedCarts();
            $percent = $nbConvertedCarts / $numberSent * 100;
        }

        return $percent;
    }

    /**
     * Get average revenue conversion for a given period
     *
     * @param string $includeContainer
     * @return float
     */
    public function getAverageRevenueConversion()
    {
        $resource = Mage::getModel('abandonment/orderflag')->getResource();
        $average = 0;

        $nbConvertedCarts = $resource->getSumNbConvertedCarts($this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod());
        if ($nbConvertedCarts > 0) {
            $sumAmout = $resource->getSumAmountConvertedCarts(
                    $this->_getFilterStartingPeriod(), $this->_getFilterEndingPeriod()
                );
            $average = $sumAmout / $nbConvertedCarts;
        }

        return $average;
    }

    /**
     * Get total average revenue conversion
     *
     * @param string $includeContainer
     * @return float
     */
    public function getTotalAverageRevenueConversion()
    {
        $resource = Mage::getModel('abandonment/orderflag')->getResource();
        $average = 0;

        $nbConvertedCarts= $resource->getSumNbConvertedCarts();
        if ($nbConvertedCarts) {
            $average = $resource->getSumAmountConvertedCarts() / $nbConvertedCarts;
        }

        return $average;
    }

    /**
     * @param string $alertId
     * @return string
     */
    protected function _getDetailsAlertUrl($alertId)
    {
        $params = array('alertid' => $alertId);
        if($this->_getFilterStartingPeriod() !== null) {
            $params['from'] = $this->_getFilterStartingPeriod();
        }
        if($this->_getFilterEndingPeriod() !== null) {
            $params['to'] = $this->_getFilterEndingPeriod();
        }

        return $this->getUrl('*/details/index', $params);
    }

    /**
     * @return string - Varien_Date::DATETIME_INTERNAL_FORMAT
     */
    protected function _getFilterStartingPeriod()
    {
        $from = null;
        if ($this->getFilterData()->getFrom()) {
            $from = $this->getFilterData()->getFrom();
        }
        return $from;
    }

    /**
     * @return string - Varien_Date::DATETIME_INTERNAL_FORMAT
     */
    protected function _getFilterEndingPeriod()
    {
        $to = null;
        if ($this->getFilterData()->getTo()) {
            $to = $this->getFilterData()->getTo() . ' 23:59:59';
        }
        return $to;
    }

    /**
     * @return string
     */
    public function getDetailsFirstAlertUrl()
    {
        return $this->_getDetailsAlertUrl(Emv_CartAlert_Constants::FIRST_ALERT_REMINDER_ID);
    }

    /**
     * @return string
     */
    public function getDetailsSecondAlertUrl()
    {
        return $this->_getDetailsAlertUrl(Emv_CartAlert_Constants::SECOND_ALERT_REMINDER_ID);
    }

    /**
     * @return string
     */
    public function getDetailsThirdAlertUrl()
    {
        return $this->_getDetailsAlertUrl(Emv_CartAlert_Constants::THIRD_ALERT_REMINDER_ID);
    }

    /**
     * @param string $templateIdStoreConfigPath
     * @return string
     */
    protected function _getAlertTemplateSubject($templateIdStoreConfigPath)
    {
        $templateId = Mage::getStoreConfig($templateIdStoreConfigPath);
        /* @var $template Emv_Emt_Model_Mage_Core_Email_Template */
        $template = Mage::getModel('core/email_template');
        if (is_numeric($templateId)) {
            $template->load($templateId);
        } else {
            $localeCode = Mage::getStoreConfig('general/locale/code');
            $template->loadDefault($templateId, $localeCode);
        }

        return $template->getTemplateSubject();
    }

    /**
     * @return string
     */
    public function getFirstAlertTemplateSubject()
    {
        return $this->_getAlertTemplateSubject(Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_TEMPLATE);
    }

    /**
     * @return string
     */
    public function getSecondAlertTemplateSubject()
    {
        return $this->_getAlertTemplateSubject(Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_TEMPLATE);
    }

    /**
     * @return string
     */
    public function getThirdAlertTemplateSubject()
    {
        return $this->_getAlertTemplateSubject(Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_TEMPLATE);
    }
}
