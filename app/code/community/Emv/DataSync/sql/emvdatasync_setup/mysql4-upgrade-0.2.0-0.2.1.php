<?php
/* @var $installer Emv_Emt_Model_Resource_Setup */
$this->startSetup();

/* @var Varien_Db_Adapter_Pdo_Mysql $connection*/

$this->run("
    DROP TABLE IF EXISTS {$this->getTable('emvdatasync/purchase_info')};
    CREATE TABLE {$this->getTable('emvdatasync/purchase_info')} (
      `id` int(11) unsigned NOT NULL auto_increment,
      `created_at` DATETIME NOT NULL,
      `updated_at` DATETIME NOT NULL,
      `base_currency_code` varchar(255) NULL,

      `total_order` INT(4) DEFAULT 0,

      `total_ordered_item_qty` INT(5) DEFAULT 0,
      `avg_item_qty` INT(5) DEFAULT 0,
      `order_amount_total` DECIMAL(12,4) DEFAULT 0,
      `avg_order_amount_total` DECIMAL(12,4) DEFAULT 0,
      `discount_amount_total` DECIMAL(12,4) DEFAULT 0,
      `avg_discount_amount_total` DECIMAL(12,4) DEFAULT 0,

      `min_order_amount_total` DECIMAL(12,4) DEFAULT 0,
      `max_order_amount_total` DECIMAL(12,4) DEFAULT 0,
      `first_order_date` DATETIME NULL,
      `last_order_date` DATETIME NULL,
      `min_total_ordered_item_qty` INT(5) DEFAULT 0,
      `max_total_ordered_item_qty` INT(5) DEFAULT 0,

      `shipping_amount_total` DECIMAL(12,4) DEFAULT 0,
      `avg_shipping_amount_total` DECIMAL(12,4) DEFAULT 0,
      `shipping_list` TEXT NULL,
      `payment_methods` TEXT NULL,
      `coupon_list` TEXT NULL,
      `nb_order_having_discount` INT(4) DEFAULT 0,

      `customer_id` int(9) unsigned NULL,
      `email` varchar(255) NOT NULL,
      `order_list` TEXT NULL,

      PRIMARY KEY (`id`),

      INDEX `IDX_PURCHASE_INFO_CUSTOMER_ID` (`customer_id`),
      INDEX `IDX_PURCHASE_INFO_EMAIL` (`email`)

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Purchase Information';
");

/* @var Varien_Db_Adapter_Pdo_Mysql $connection*/
$connection = $this->getConnection();
$connection->addColumn($this->getTable('newsletter/subscriber'), 'date_last_purchase', 'DATETIME default NULL');

$gmtDate = Mage::getModel('core/date')->gmtDate();
$this->run("
    UPDATE {$this->getTable('newsletter/subscriber')} subscriber
    JOIN {$this->getTable('sales/order')} flat_order ON subscriber.customer_id = flat_order.customer_id
    SET date_last_purchase = '$gmtDate';
");
$this->endSetup();