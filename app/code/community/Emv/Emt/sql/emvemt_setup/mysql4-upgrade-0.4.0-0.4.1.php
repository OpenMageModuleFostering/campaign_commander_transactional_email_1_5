<?php
/* @var $this Emv_Emt_Model_Resource_Setup */
$this->startSetup();

// create a new log table
$this->run("
DROP TABLE IF EXISTS {$this->getTable('emvemt/log')};
CREATE TABLE IF NOT EXISTS {$this->getTable('emvemt/log')}
(
    `id` INT(10) AUTO_INCREMENT NOT NULL,
    `sending_mode` VARCHAR(10) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `sending_type` VARCHAR(10) NOT NULL,
    `store_id` SMALLINT(5) NULL,
    `account_id` SMALLINT(5) NULL,
    `magento_template_name` VARCHAR(255) NOT NULL,
    `original_magento_template_name` VARCHAR(255) NOT NULL,

    `rescheduled` TINYINT(1) NOT NULL,

    `sent_sucess` TINYINT(1) NOT NULL,
    `created_at` TIMESTAMP NULL,

    `error` TEXT NULL,
    `error_code` INT(10) NULL,

    `emv_name` VARCHAR(255) NULL,
    `emv_params` TEXT NULL,
    `emv_content_variables` TEXT NULL,
    `emv_dyn_variables` TEXT NULL,

    INDEX `IDX_LOG_SENDING_MODE` (`sending_mode`),
    INDEX `IDX_LOG_EMAIL` (`email`),
    INDEX `IDX_LOG_MAGENTO_TEMPLATE_NAME` (`magento_template_name`),
    INDEX `IDX_LOG_ORIGINAL_MAGENTO_TEMPLATE_NAME` (`original_magento_template_name`),
    INDEX `IDX_LOG_ORIGINAL_EMV_NAME` (`emv_name`),

    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Log Sending';
");

// queue message
$this->run("
DROP TABLE IF EXISTS {$this->getTable('emvemt/resending_queue_message')};
CREATE TABLE IF NOT EXISTS {$this->getTable('emvemt/resending_queue_message')}
(
    `id` INT(10) AUTO_INCREMENT NOT NULL,
    `sending_mode` VARCHAR(10) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `store_id` SMALLINT(5) NULL,
    `account_id` SMALLINT(5) NULL,
    `log_id` SMALLINT(5) NULL,

    `magento_template_name` VARCHAR(255) NULL,
    `original_magento_template_name` VARCHAR(255) NULL,

    `created_at` TIMESTAMP NULL,

    `emv_name` VARCHAR(255) NOT NULL,
    `emv_params` TEXT NOT NULL,
    `emv_content_variables` TEXT NULL,
    `emv_dyn_variables` TEXT NULL,

    `sent_sucess` TINYINT(1) NULL,
    `number_attempts` INT(4) NULL,
    `first_attempt` TIMESTAMP NULL,
    `last_attempt` TIMESTAMP NULL,

    INDEX `IDX_LOG_SENDING_MODE` (`sending_mode`),
    INDEX `IDX_LOG_EMAIL` (`email`),
    INDEX `IDX_LOG_MAGENTO_TEMPLATE_NAME` (`magento_template_name`),
    INDEX `IDX_LOG_ORIGINAL_MAGENTO_TEMPLATE_NAME` (`original_magento_template_name`),
    INDEX `IDX_LOG_ORIGINAL_EMV_NAME` (`emv_name`),

    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Queue Message for sending';
");

$this->endSetup();