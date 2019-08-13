<?php
/* @var $installer Emv_Emt_Model_Resource_Setup */
$this->startSetup();

/* @var Varien_Db_Adapter_Pdo_Mysql $connection*/
$connection = $this->getConnection();
$connection->addColumn($this->getTable('newsletter/subscriber'), 'queued', 'TINYINT(1) default 0');

$this->run("
    UPDATE {$this->getTable('newsletter/subscriber')}
    SET queued = 1
    WHERE member_last_update_date IS NULL
        OR (member_last_update_date IS NOT NULL
            AND (
                date_unjoin > member_last_update_date OR data_last_update_date > member_last_update_date
            )
        )
");

$this->endSetup();