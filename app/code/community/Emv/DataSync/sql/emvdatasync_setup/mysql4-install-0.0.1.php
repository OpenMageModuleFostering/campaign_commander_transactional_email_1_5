<?php
$this->startSetup();

/* @var Varien_Db_Adapter_Pdo_Mysql $connection*/
$connection = $this->getConnection();

$connection->addColumn($this->getTable('newsletter/subscriber'), 'date_unjoin', 'DATETIME default NULL');
$connection->addColumn($this->getTable('newsletter/subscriber'), 'data_last_update_date', 'DATETIME default NULL');
$connection->addColumn($this->getTable('newsletter/subscriber'), 'member_last_update_date', 'DATETIME default NULL');

$this->endSetup();
?>