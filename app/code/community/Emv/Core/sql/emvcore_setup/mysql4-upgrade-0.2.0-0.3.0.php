<?php
/* @var $this Emv_Emt_Model_Resource_Setup */
$this->startSetup();

$this->run("
    DROP TABLE IF EXISTS `{$this->getTable('emvcore/dataprocessing_process')}`;
    CREATE TABLE `{$this->getTable('emvcore/dataprocessing_process')}` (

        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `type` varchar(255) NOT NULL,
        `title` TEXT NOT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NULL,
        `terminated_at` datetime NULL,
        `state` int(10) DEFAULT '0',
        `status` int(1) DEFAULT '0',
        `output_information` TEXT NULL,

        PRIMARY KEY (`id`),
        INDEX `IDX_PROCESS_STATE` (`state`),
        INDEX `IDX_PROCESS_TYPE` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$this->endSetup();