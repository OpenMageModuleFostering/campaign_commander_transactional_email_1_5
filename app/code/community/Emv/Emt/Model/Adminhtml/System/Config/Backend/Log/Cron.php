<?php
/**
 * Log Cron Backend Model
 *
 * @category   Emv
 * @package    Emv_Emt
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Adminhtml_System_Config_Backend_Log_Cron extends Emv_Core_Model_Adminhtml_System_Config_Backend_Cron
{
    protected $_crontabPath = 'crontab/jobs/emailvision_process_sending_log_cleanning/schedule/cron_expr';
}
