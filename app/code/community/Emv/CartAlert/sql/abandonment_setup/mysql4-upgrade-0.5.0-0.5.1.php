<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()
    ->addColumn($this->getTable('abandonment/abandonment'), 'shopping_cart_rule_id', 'INT(9) NULL');
$this->getConnection()
    ->addColumn($this->getTable('abandonment/abandonment'), 'coupon_code', 'TEXT NULL');

$this->endSetup();