<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */

$this->startSetup();

$this->addAttribute('customer', 'newsletter_subscribed', array(
    'label'          => 'Subscribed to newsletter(s)',
    'type'           => 'int',
    'input'          => 'select',
    'visible'        => true,
    'source'         => 'eav/entity_attribute_source_boolean',
    'required'       => false,
    'user_defined'   => false,
    'nullable'       => false,
    'default'        => '1',
    'sort_order'     => 1000
));

$this->updateCustomerForms();
$this->endSetup();
