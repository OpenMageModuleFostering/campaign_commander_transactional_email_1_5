<?php
/**
 * Cron Expression for Scheduled Exports
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Adminhtml_System_Config_Backend_BatchMember_Cron extends Emv_Core_Model_Adminhtml_System_Config_Backend_Cron
{
    /**
     * Crontab expression path - please define an appropriate path in order to make this backend work
     * @var string
     */
    protected $_crontabPath = 'crontab/jobs/emailvision_batchmember_export/schedule/cron_expr';
}