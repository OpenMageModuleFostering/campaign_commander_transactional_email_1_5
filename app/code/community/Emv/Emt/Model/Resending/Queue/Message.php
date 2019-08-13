<?php
/**
 * EmailVision resending queue message model - all timestamp fields are always in GMT
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Resending_Queue_Message extends Emv_Emt_Model_Log
{
    /**
     * Constant for not sent message
     */
    const IS_NOT_SENT = 0;

    /**
     * Constant for sent message
     */
    const IS_SENT = 1;

    /**
     * Resending Max attempt limit
     */
    const MAX_ATTEMPT = 4;

    /**
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    public function _construct()
    {
        $this->_init('emvemt/resending_queue_message');
    }

    /**
     * Add success attempt
     *
     * @return Emv_Emt_Model_Resending_Queue_Message
     */
    public function addSuccessAttempt()
    {
        $this->incrementAttempt();
        $this->setSentSucess(self::IS_SENT);

        return $this;
    }

    /**
     * Get number attempts
     *
     * @return int
     */
    public function getNumberAttempts()
    {
        $attempts = $this->getData('number_attempts');
        if (!$attempts) {
            $attempts = 0;
        }
        return $attempts;
    }

    /**
     * Increment sending attempt
     *
     * @return Emv_Emt_Model_Resending_Queue_Message
     */
    public function incrementAttempt()
    {
        $attempts = $this->getNumberAttempts();
        // get gmt date time
        $gmtDate = Mage::getModel('core/date')->gmtDate();

        if (!$attempts) {
            $attempts = 1;
            $this->setData('first_attempt', $gmtDate);
        } else {
            $attempts++;
        }

        $this->setData('last_attempt', $gmtDate);
        $this->setNumberAttempts($attempts);
        return $this;
    }
}