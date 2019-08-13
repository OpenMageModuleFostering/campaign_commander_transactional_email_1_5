<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */

$this->startSetup();

$this->run("
ALTER TABLE {$this->getTable('abandonment')}
    MODIFY COLUMN entity_id INT(10) NOT NULL,

    ADD INDEX `IDX_ABANDONMENT_ENTITY_ID` (`entity_id`),
    ADD INDEX `IDX_ABANDONMENT_TEMPLATE` (`template`)
");

$this->run("
ALTER TABLE {$this->getTable('abandonment/stats')}
    ADD INDEX `IDX_ABANDONMENT_STAT_REMINDER_ID` (`reminder_id`),
    ADD INDEX `IDX_ABANDONMENT_STAT_QUOTE_ID` (`quote_id`)
");

$this->run("
ALTER TABLE {$this->getTable('abandonment/order_flag')}
    ADD INDEX `IDX_ABANDONMENT_ORDER_FLAG_ENTITY_ID` (`entity_id`),
    ADD INDEX `IDX_ABANDONMENT_ORDER_FLAG_FLAG` (`flag`)
");
$this->endSetup();
