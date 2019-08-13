<?php
/* @var $installer Emv_Emt_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE {$installer->getTable('emvemt/emt')}
    ADD COLUMN `created_at` TIMESTAMP NULL,
    ADD COLUMN `updated_at` TIMESTAMP NULL,
    ADD COLUMN `emv_parameters` TEXT NULL,
    MODIFY COLUMN mage_template_id VARCHAR(255) NOT NULL,

    ADD INDEX `IDX_EMV_EMT_EMV_TEMPLATE_ID` (`emv_template_id`),
    ADD INDEX `IDX_EMV_EMT_ACCOUNT_ID` (`emv_account_id`),
    ADD INDEX `IDX_EMV_MAGE_TEMPLATE_ID` (`mage_template_id`),
    ADD INDEX `IDX_EMV_MAGE_SEND_MAIL_MODE_ID` (`emv_send_mail_mode_id`)
");

$gmtDate = Mage::getModel('core/date')->gmtDate();
$this->run("
    UPDATE {$this->getTable('emvemt/emt')} SET created_at = '{$gmtDate}', updated_at = '{$gmtDate}';
");

$installer->endSetup();