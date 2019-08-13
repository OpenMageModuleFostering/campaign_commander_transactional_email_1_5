<?php
/* @var $installer Emv_Emt_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
-- DROP TABLE {$installer->getTable('emvemt/mailmode')};
CREATE TABLE {$installer->getTable('emvemt/mailmode')}
(
    `id` int(10) unsigned NOT NULL auto_increment,
    `name` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE UQ_emv_emt_send_mail_mode_Mode(`name`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='All emv mail mode';

-- DROP TABLE {$installer->getTable('emvemt/emt')};
CREATE TABLE {$installer->getTable('emvemt/emt')}(
    `id` int(10) unsigned NOT NULL auto_increment,
    `mage_template_id` int(10) NOT NULL REFERENCES core_email_template (template_id) ON DELETE CASCADE,
    `emv_account_id` int(10) REFERENCES emv_account (`id`),
    `emv_send_mail_mode_id` int(10) REFERENCES emv_emt_send_mail_mode (`id`),
    `emv_template_id` INTEGER,
    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Transactional email mapping';

-- DROP TABLE {$installer->getTable('emvemt/attribute')};
CREATE TABLE {$installer->getTable('emvemt/attribute')}
(
    `id` int(10) unsigned NOT NULL auto_increment,
    `emv_emt_id` INTEGER NOT NULL REFERENCES emv_emt (id) ON DELETE CASCADE ON UPDATE CASCADE,
    `emv_attribute` VARCHAR(200) NOT NULL,
    `mage_attribute` VARCHAR(200) NOT NULL,
    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Relation table between for attribute mapping in emt';

INSERT INTO {$installer->getTable('emvemt/mailmode')} (`name`) VALUES ('classic'), ('emv create'), ('emv send');
");

$installer->endSetup();