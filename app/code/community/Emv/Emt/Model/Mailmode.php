<?php
/**
 * Mail mode class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mailmode extends Mage_Core_Model_Abstract
{
    /**
     * Classic mode - email is sent by Magento
     */
    const CLASSIC_MODE = 1;

    /**
     * Create mode - email is sent by EmailVision template
     */
    const EMV_CREATE   = 2;

    /**
     * Send mode - email is sent by EmailVision by using Magento template
     */
    const EMV_SEND     = 3;

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_init('emvemt/mailmode');
    }

    /**
     * Get mail modes and their translated labels
     * @return array
     */
    public static function getMailModesAndLabels()
    {
        return array(
            self::CLASSIC_MODE =>  Mage::helper('emvemt')->__('Classic'),
            self::EMV_CREATE => Mage::helper('emvemt')->__('SmartFocus Template'),
            self::EMV_SEND => Mage::helper('emvemt')->__('SmartFocus Routage'),
        );
    }

    /**
     * Get all sending supported modes
     *
     * @return array
     */
    public static function getSupportedModes()
    {
        return array(
            self::CLASSIC_MODE,
            self::EMV_CREATE,
            self::EMV_SEND,
        );
    }
    /**
     * To option array for select element
     *
     * @return array
     */
    public static function toOptionArray()
    {
        $mailModesAndLabels = self::getMailModesAndLabels();
        return array(
            array('value' => self::CLASSIC_MODE, 'label' => $mailModesAndLabels[self::CLASSIC_MODE]),
            array('value' => self::EMV_CREATE, 'label' => $mailModesAndLabels[self::EMV_CREATE]),
            array('value' => self::EMV_SEND, 'label' => $mailModesAndLabels[self::EMV_SEND]),
        );
    }
}