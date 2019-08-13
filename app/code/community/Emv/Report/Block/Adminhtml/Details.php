<?php
/**
 * Abandoned Cart Detail Grid Container
 *
 * @category    Emv
 * @package     Emv_Report
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Report_Block_Adminhtml_Details extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_details';
        $this->_blockGroup = 'abandonment_report';

        $from = Mage::registry('from');
        $to = Mage::registry('to');
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

        // build header text
        $type = '';
        $alertId = Mage::registry('abandonmentalertid');
        switch ($alertId) {
            case Emv_CartAlert_Constants::FIRST_ALERT_REMINDER_ID:
            default :
                $type = Mage::helper('abandonment_report')->__('the first reminders');
                break;
            case Emv_CartAlert_Constants::SECOND_ALERT_REMINDER_ID:
                $type = Mage::helper('abandonment_report')->__('the second reminders');
                break;
            case Emv_CartAlert_Constants::THIRD_ALERT_REMINDER_ID:
                $type = Mage::helper('abandonment_report')->__('the third reminders');
                break;
        }
        $this->_headerText = Mage::helper('abandonment_report')->__('Converted Cart Report for %s', $type) . $fromTo;

        parent::__construct();

        // remove button add
        $this->_removeButton('add');
    }
}
