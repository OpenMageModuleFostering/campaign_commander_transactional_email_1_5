<?php
/* @var $this Emv_CartAlert_Model_Resource_Setup */
$this->startSetup();

$this->removeAttribute('customer', 'newsletter_subscribed');

$this->updateCustomerForms();
$this->endSetup();