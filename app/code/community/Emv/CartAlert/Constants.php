<?php
/**
 * Cart alert constants
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
interface Emv_CartAlert_Constants
{
    const XML_PATH_TEST_MODE_ENABLED     = 'abandonment/general/test_mode';
    const XML_PATH_LIFETIME              = 'abandonment/general/lifetime';

    const XML_PATH_FIRST_ALERT_ENABLED   = 'abandonment/first_alert_config/enabled';
    const XML_PATH_FIRST_ALERT_TEMPLATE  = 'abandonment/first_alert_config/template';
    const XML_PATH_FIRST_ALERT_DELAY     = 'abandonment/first_alert_config/delay';

    const XML_PATH_SECOND_ALERT_ENABLED  = 'abandonment/second_alert_config/enabled';
    const XML_PATH_SECOND_ALERT_TEMPLATE = 'abandonment/second_alert_config/template';
    const XML_PATH_SECOND_ALERT_DELAY    = 'abandonment/second_alert_config/delay';

    const XML_PATH_THIRD_ALERT_ENABLED   = 'abandonment/third_alert_config/enabled';
    const XML_PATH_THIRD_ALERT_TEMPLATE  = 'abandonment/third_alert_config/template';
    const XML_PATH_THIRD_ALERT_DELAY     = 'abandonment/third_alert_config/delay';

    const XML_PATH_ALERT_SENDER_IDENTITY = 'abandonment/general/email_identity';

    const XML_PATH_COUPON_CODE_LENGTH = 'abandonment/general/length';
    const XML_PATH_COUPON_CODE_FORMAT = 'abandonment/general/format';
    const XML_PATH_COUPON_CODE_PREFIX = 'abandonment/general/prefix';
    const XML_PATH_COUPON_CODE_SUFFIX = 'abandonment/general/suffix';
    const XML_PATH_COUPON_CODE_DASH   = 'abandonment/general/dash';

    const NONE_FLAG                      = 'none';
    const FIRST_ALERT_FLAG               = 'first_alert';
    const FIRST_ALERT_REMINDER_ID        = 1;
    const SECOND_ALERT_FLAG              = 'second_alert';
    const SECOND_ALERT_REMINDER_ID       = 2;
    const THIRD_ALERT_FLAG               = 'third_alert';
    const THIRD_ALERT_REMINDER_ID        = 3;

    const OUTDATED_FLAG                  = 'outdated';
    const TO_BE_PROCESSED_FLAG           = 'to_be_processed';

    const OUTDATED_CART_DELAY            = 168; // 168 hours = 7 days
}