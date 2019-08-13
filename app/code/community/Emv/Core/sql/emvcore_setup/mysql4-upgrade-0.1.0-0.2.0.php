<?php
/* @var $this Emv_Emt_Model_Resource_Setup */
$this->startSetup();

$this->run("
ALTER TABLE {$this->getTable('emvcore/account')}
    ADD COLUMN `created_at` TIMESTAMP NULL,
    ADD COLUMN `updated_at` TIMESTAMP NULL,
    ADD COLUMN `emv_urls` TEXT NOT NULL
");

$gmtDate = Mage::getModel('core/date')->gmtDate();
$this->run("
    UPDATE {$this->getTable('emvcore/account')} SET created_at = '{$gmtDate}', updated_at = '{$gmtDate}';
");

$this->endSetup();