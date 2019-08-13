<?php
/**
 * EmailVision resending queue model
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Resending_Queue extends Mage_Core_Model_Abstract
{
    /**
     * Queue rule
     * @var Emv_Emt_Model_Emt_Resending_Rule
     */
    protected $_rule = null;

    /**
     * Limit of emails to be sent
     */
    const EMAIL_LIMIT = 500;

    /**
     * Set message queue rule
     *
     * @param Emv_Emt_Model_Emt_Resending_Rule $rule
     * @return Emv_Emt_Model_Resending_Queue
     */
    public function setRule(Emv_Emt_Model_Emt_Resending_Rule $rule)
    {
        $this->_rule = $rule;
        return $this;
    }

    /**
     * Get message queue rule
     *
     * @return Emv_Emt_Model_Emt_Resending_Rule
     */
    public function getRule()
    {
        if ($this->_rule == null) {
            $this->_rule = Mage::getModel('emvemt/resending_rule');
        }
        return $this->_rule;
    }

    /**
     * Reschedule Error Sending. The rule will determine if possible or not and execute the rescheduling.
     *
     * @return Emv_Emt_Model_Resending_Queue
     */
    public function rescheduleErrorSending()
    {
        if (!$this->getRule()->canScheduleErrorSending()) {
            return $this;
        }

        try {
            $this->getRule()->rescheduleErrorSending();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Get all pending messages
     *
     * @return Emv_Emt_Model_Mysql4_Resending_Queue_Message_Collection
     */
    public function getPendingMessages()
    {
        return Mage::getModel('emvemt/resending_queue_message')->getCollection()->getListNotSent();
    }

    /**
     * Send pending message
     *
     * @return Emv_Emt_Model_Resending_Queue
     */
    public function sendPendingMessages()
    {
        if (!$this->getRule()->canSendPendingMessage()) {
            return $this;
        }

        /* @var $templateHelper Emv_Emt_Helper_Emvtemplate */
        $templateHelper = Mage::helper('emvemt/emvtemplate');
        /* @var $notificationService Emv_Core_Model_Service_Notification */
        $notificationService = Mage::getSingleton('emvcore/service_notification');

        $list = $this->getPendingMessages();
        $size = $list->getSize();
        $pageCount = ceil($size / self::EMAIL_LIMIT);

        for ($curPage = 1; $curPage <= $pageCount; $curPage++) {
            // Define current page
            $list->clear()
                ->setPageSize(self::EMAIL_LIMIT)
                ->setCurPage($curPage);

            foreach ($list as $message) {
                if (!$this->getRule()->isMessageReadyToBeSent($message)) {
                    continue;
                }

                // need to execute this method in order to have the unserialized variables
                $message->prepareDataAfterLoad();

                /* @var $message Emv_Emt_Model_Resending_Queue_Message */
                $params = $message->getData('emv_params');

                $lastEmail   = $message->getEmail();
                $emailToSend = $message->getEmail();
                $sentEmail   = array();

                // get account
                $account = null;
                if ($message->getAccountId()) {
                    $account = Mage::helper('emvcore')->getAccount($message->getAccountId());
                }

                // log - prepare log object before sending email
                $sendingData = array(
                    'account'                        => $account,
                    'magento_template_name'          => $message->getData('magento_template_name'),
                    'original_magento_template_name' => $message->getData('original_magento_template_name'),
                );
                $log = $templateHelper->prepareLogBeforeSend(
                    $message->getSendingMode(),
                    $sendingData,
                    $lastEmail,
                    $message->getStoreId(),
                    Emv_Emt_Model_Log::RESENDING_WORKFLOW
                );

                $lastData = array('emv_name' => $message->getData('emv_name'));
                try {
                    // validate if all SmartFocus parameters are valid
                    $templateHelper->validateEmvParams($params);

                    $emvContent   = is_array($message->getData('emv_content_variables'))
                        ? $message->getData('emv_content_variables') : array();
                    $emvAttribute = is_array($message->getData('emv_dyn_variables'))
                        ? $message->getData('emv_dyn_variables') : array();

                    // in case we have several emails to send
                    foreach($emailToSend as $oneMail ) {
                        $lastEmail = $oneMail;

                        // send message
                        $result = $notificationService->sendTemplate(
                            $params['emv_encrypt'],
                            $params['emv_id'],
                            $params['emv_random'],
                            $oneMail,
                            $emvContent,
                            $emvAttribute,
                            $account,
                            $message->getStoreId()
                        );
                        $sentEmail[] = $oneMail;

                        // if everything is ok, we can send the email
                        $templateHelper->prepareLogAfterSend(
                            $log,
                            $lastData,
                            $lastEmail,
                            true,
                            array(),
                            $message->getStoreId()
                        );
                    }
                } catch(Exception $e) {
                    Mage::logException($e);

                    // if error happens, log error
                    $error = array(
                        'msg'  => $e->getMessage(),
                        'code' => $e->getCode()
                    );

                    $templateHelper->prepareLogAfterSend($log, $lastData, $lastEmail, false, $error, $message->getStoreId());
                }

                // if we couldn't send all scheduled emails
                if (count($emailToSend) != count($sentEmail)) {
                    if (count($sentEmail) >= 1) {
                        // if message is not correctly sent to all email,
                        // we need to reschedule the resending for the rest of emails
                        $newMessage = Mage::getModel('emvemt/queue_message');
                        $newMessage->setData($message->getData());
                        // get the rest of emails to send
                        $rest = array_diff($emailToSend, $sentEmail);
                        $newMessage->setEmail($rest);
                        $newMessage->setSentSucess(false);
                        $newMessage->save();

                        $message->addSuccessAttempt();
                    } else {
                        $message->incrementAttempt();
                    }
                } else {
                    $message->addSuccessAttempt();
                }
                $message->save();
            }
        }

        return $this;
    }
}