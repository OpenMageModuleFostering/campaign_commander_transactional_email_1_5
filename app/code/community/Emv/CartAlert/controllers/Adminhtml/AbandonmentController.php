<?php
/**
 * Abandoned Cart Reminder controller
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Adminhtml_AbandonmentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Initialise action
     *  - load all layout
     *  - set active menu SmartFocus
     */
    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('emailvision/abandonment');
        // Define module dependent translate
        $this->setUsedModuleName('Emv_CartAlert');
        $this->_title(Mage::helper('abandonment')->__('SmartFocus'))
            ->_title(Mage::helper('abandonment')->__('Abandoned Cart Reminders'));
    }

    /**
     * Check menu access
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/abandonment/list');
    }

    /**
     * Abandoned Cart Reminder List
     */
    public function listAction()
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
            $this->getLayout()->createBlock('abandonment/adminhtml_list','list')
        );
        $this->renderLayout();
    }

    /**
     * Abandoned Cart Reminder Grid (for ajax query)
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('abandonment/adminhtml_list_grid')->toHtml()
        );
    }

    /**
     * Display product information in a given quote
     */
    public function displayQuoteAction()
    {
        $quoteId = $this->getRequest()->getParam('quote');
        if ($quoteId) {
            $collection = Mage::getModel('sales/quote')->getCollection();
            $collection->addFieldToFilter('entity_id', array('in' => array($quoteId)));
            $quote = $collection->getFirstItem();
            if ($quote && $quote->getId()) {
                Mage::register('smartfocus_quote', $quote);

                // save all messages from session
                $this->getLayout()->getMessagesBlock()->setMessages(
                    $this->_getSession()->getMessages()
                );

                $this->_initAction();

                $this->_addContent(
                    $this->getLayout()->createBlock('abandonment/adminhtml_quote','list')
                );
                $this->renderLayout();
            }
        }
    }

    /**
     * Test reminder - send a list of abandoned carts with a given reminder (first, second or third)
     */
    public function testReminderAction()
    {
        $quoteIds = $this->getRequest()->getParam('quotes');
        if (!is_array($quoteIds)) {
            $this->_getSession()->addError(Mage::helper('emvcore')->__('Please select something!'));
        } else {
            $template = $this->getRequest()->getParam('template');
            if ($template && in_array($template,
                    array(
                        Emv_CartAlert_Constants::FIRST_ALERT_FLAG,
                        Emv_CartAlert_Constants::SECOND_ALERT_FLAG,
                        Emv_CartAlert_Constants::THIRD_ALERT_FLAG
                    )
                )
            ) {
                // save the current store so that it doesn't interfere with any other functions
                $currentStore = Mage::app()->getStore()->getStoreId();

                /* @var $helper Emv_CartAlert_Helper_Data */
                $helper = Mage::helper('abandonment');
                $rule = $helper->getShoppingCartPriceRule();

                $sent = 0;
                $collection = Mage::getModel('sales/quote')->getCollection();
                $collection->addFieldToFilter('entity_id', array('in' => array($quoteIds)));
                foreach($collection as $quote) {
                    // Set the current store
                    Mage::app()->setCurrentStore($quote->getStoreId());

                    try {
                        $abandonment = Mage::getModel('abandonment/abandonment');
                        $abandonment->setShoppingCartRuleId($rule->getId());
                        $abandonment->setCouponCode($helper->getCouponCode($rule->getId()));

                        $helper->prepareAndSendReminder($template, $quote, $abandonment, false);
                        $sent ++;
                    } catch (Exception $e) {
                        $this->_getSession()->addError(
                            $helper->__(
                                'Cannot send reminder for cart #%s. Reason: %s',
                                $quote->getEntityId(), $e->getMessage()
                            )
                        );
                    }
                }

                // return the store to normal
                Mage::app()->setCurrentStore($currentStore);

                $reminderLabels = Mage::helper('abandonment')->getReminderLables();
                $label = $template;
                if (isset($reminderLabels[$template])) {
                    $label = $reminderLabels[$template];
                }
                if ($sent > 1) {
                    $this->_getSession()->addSuccess(
                        $helper->__('%s carts were successfully sent with %s', $sent, $label)
                    );
                } elseif ($sent == 1) {
                    $this->_getSession()->addSuccess(
                        $helper->__('One cart was successfully sent with %s', $label)
                    );
                }
            }
        }

        // get back to abandoned cart reminder list
        $this->_redirect('*/*/list');
    }

    /**
     * Export abandoned cart grid in csv file
     */
    public function exportAbandonedCsvAction()
    {
        $fileName   = 'abandoned_carts.csv';
        $content    = $this->getLayout()->createBlock('abandonment/adminhtml_list_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export abandoned cart grid in excel file (xml)
     */
    public function exportAbandonedExcelAction()
    {
        $fileName   = 'abandoned_carts.xml';
        $content    = $this->getLayout()->createBlock('abandonment/adminhtml_list_grid')
            ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }
}