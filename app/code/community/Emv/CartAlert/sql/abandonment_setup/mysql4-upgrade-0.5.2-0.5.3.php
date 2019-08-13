<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */

$this->startSetup();

$this->getConnection()
    ->addColumn($this->getTable('abandonment/abandonment'), 'updated_at', 'DATETIME NULL');

$gmtDate = Mage::getModel('core/date')->gmtDate();
// update the existing abandonments
$sql = "UPDATE {$this->getTable('abandonment/abandonment')} AS aband"
    . " SET aband.updated_at = '{$gmtDate}'";
$this->run($sql);

$this->endSetup();
