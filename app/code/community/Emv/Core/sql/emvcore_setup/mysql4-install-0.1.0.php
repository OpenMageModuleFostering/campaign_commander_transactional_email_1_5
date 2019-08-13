<?php
/* @var $installer Emv_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
-- DROP TABLE {$installer->getTable('emvcore/account')};
CREATE TABLE {$installer->getTable('emvcore/account')}(
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` VARCHAR(150) NOT NULL,
  `account_login` VARCHAR(150) NOT NULL,
  `account_password` VARCHAR(150) NOT NULL,
  `manager_key` VARCHAR(150) NOT NULL,
  `use_proxy` BOOL NOT NULL,
  `proxy_host` VARCHAR(150) DEFAULT NULL,
  `proxy_port` INTEGER DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE UQ_Account_manager_key(`manager_key`),
  UNIQUE UQ_Account_name(`name`)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='SmartFocus account';
");

$installer->endSetup();
