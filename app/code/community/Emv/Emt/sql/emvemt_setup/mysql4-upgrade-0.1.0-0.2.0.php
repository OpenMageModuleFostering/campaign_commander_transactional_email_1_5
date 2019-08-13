<?php
/* @var $installer Emv_Emt_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE {$installer->getTable('emvemt/attribute')}
    ADD COLUMN `emv_attribute_type` VARCHAR(200) NOT NULL AFTER emv_emt_id
");

// This part is intentionally commented - Emv_Emt_Constants class has been removed in later version
/*
$installer->run("
UPDATE {$installer->getTable('emvemt/attribute')} SET `emv_attribute_type` = '".Emv_Emt_Constants::ATTRIBUTE_TYPE_EMV_DYN."'
");
*/

$installer->endSetup();