<?php
$this->startSetup();
$this->run("
-- DROP TABLE IF EXISTS {$this->getTable('abandonment')};
CREATE TABLE {$this->getTable('abandonment')} (
  `abandonment_id` int(11) unsigned NOT NULL auto_increment,
  `entity_id` varchar(30) NOT NULL default '',
  `template` varchar(30) NOT NULL default '',
  PRIMARY KEY (`abandonment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$this->endSetup();

