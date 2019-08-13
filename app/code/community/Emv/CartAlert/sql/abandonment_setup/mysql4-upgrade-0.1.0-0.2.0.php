<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */
$this->startSetup();

$this->addAttribute('customer', 'abandonment_subscribed', array(
    'label'         => 'Subscribed from abandonned cart notifications',
    'type'          => 'int',
    'input'         => 'select',
    'visible'       => true,
    'source'        => 'eav/entity_attribute_source_boolean',
    'required'      => false,
    'user_defined'  => false,
    'nullable'      => false,
    'default'       => '1',
    'sort_order'    => 1000
));

$this->getConnection()
    ->addColumn($this->getTable('abandonment/abandonment'), 'customer_abandonment_subscribed', 'TINYINT(1) NOT NULL DEFAULT 1');

$this->endSetup();