<?php
/* @var $installer Emv_Emt_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE {$installer->getTable('emvemt/attribute')}
    MODIFY COLUMN `mage_attribute` TEXT
");

$installer->endSetup();