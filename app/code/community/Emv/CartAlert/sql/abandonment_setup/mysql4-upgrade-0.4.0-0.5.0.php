<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */

$this->startSetup();

$this->getConnection()
    ->addColumn($this->getTable('abandonment/abandonment'), 'customer_id', 'INT(10) NULL');

// get customer_id for all exisiting abandonment
$sql = "UPDATE {$this->getTable('abandonment/abandonment')} AS aband"
    . " JOIN {$this->getTable('sales/quote')} AS quote ON quote.entity_id = aband.entity_id"
    . " SET aband.customer_id = quote.customer_id";

$this->run($sql);

$this->endSetup();
