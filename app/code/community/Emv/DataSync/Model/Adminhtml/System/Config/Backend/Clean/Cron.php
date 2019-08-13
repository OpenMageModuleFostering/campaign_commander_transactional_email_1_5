<?php
/**
 * Cron Expression for Exported File Cleanning process
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Adminhtml_System_Config_Backend_Clean_Cron extends Emv_DataSync_Model_Adminhtml_System_Config_Backend_BatchMember_Cron
{
    /**
     * @var string
     */
    protected $_crontabPath = 'crontab/jobs/emailvision_clean/schedule/cron_expr';
}