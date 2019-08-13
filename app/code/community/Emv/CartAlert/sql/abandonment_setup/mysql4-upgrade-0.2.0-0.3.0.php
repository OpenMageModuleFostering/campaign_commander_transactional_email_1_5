<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */
$this->startSetup();
$this->run("
    DROP TABLE IF EXISTS {$this->getTable('abandonment/stats')};
    CREATE TABLE {$this->getTable('abandonment/stats')} (
      `id` int(11) unsigned NOT NULL auto_increment,
      `reminder_id` int(11) unsigned NOT NULL,
      `send_date` DATETIME NOT NULL,
      `quote_id` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$this->run("
    DROP TABLE IF EXISTS {$this->getTable('abandonment/order_flag')};
    CREATE TABLE {$this->getTable('abandonment/order_flag')} (
      `id` int(11) unsigned NOT NULL auto_increment,
      `entity_id` int(11) unsigned NOT NULL,
      `flag` VARCHAR(64),
      `flag_date` DATETIME,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$this->endSetup();