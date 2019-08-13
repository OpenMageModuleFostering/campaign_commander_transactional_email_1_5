<?php
/**
 * Data process service
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Model_Service_DataProcess
{
    const XML_PATH_ENABLED_PURCHASE_INFO = 'emvdatasync/purchase_info/enabled';
    const XML_PATH_LIMIT_TABLE_SIZE      = 'emvdatasync/purchase_info/limit_size';
    const LIMIT_PAGE_SIZE                = 50000;

    /**
     * Chunk size (the limit number of customers to handle
     * @var int
     */
    public static $chunkSize = 2000;

    /**
     * List of fields to insert
     * @var array
     */
    protected $_fieldsForInsert = array(
            'email'                      ,
            'customer_id'                ,
            'order_list'                 ,

            'created_at'                 ,
            'updated_at'                 ,

            'base_currency_code'         ,

            'total_order'                ,

            'total_ordered_item_qty'     ,
            'avg_item_qty'               ,
            'order_amount_total'         ,
            'avg_order_amount_total'     ,
            'discount_amount_total'      ,
            'avg_discount_amount_total'  ,
            'shipping_amount_total'      ,
            'avg_shipping_amount_total'  ,

            'min_order_amount_total'     ,
            'max_order_amount_total'     ,
            'first_order_date'           ,
            'last_order_date'            ,
            'min_total_ordered_item_qty' ,
            'max_total_ordered_item_qty' ,

            'shipping_list'              ,
            'payment_methods'            ,
            'coupon_list'                ,
            'nb_order_having_discount'
        );

    /**
     * Is the purchase information process enabled ?
     *
     * @return boolean
     */
    public function enabledPurchaseInformation($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLED_PURCHASE_INFO, $storeId);
    }

    /**
     * Get limit of purchase information records to be stored inside the table
     *
     * @return int
     */
    public function getLimitPurchaseInformationTableSize()
    {
        return Mage::getStoreConfig(self::XML_PATH_LIMIT_TABLE_SIZE);
    }

    /**
     * Get the customer id list that have correct purchase information
     *
     * @param string $list - indicates if we should return the list
     * @param string $count - indicates if we should return the total number
     * @return array
     */
    public function getOkPurchaseInfoCustomerIdList($list = true, $count = false)
    {
        $resource       = Mage::getModel('core/resource');
        $readConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

        $queuedField = Emv_DataSync_Helper_Service::FIELD_QUEUED;
        $select = $readConnection->select();
        $select
            ->from(array('main_table' => $resource->getTableName('emvdatasync/purchase_info')), array())
            ->join(
                array('newsletter' => $resource->getTableName('newsletter/subscriber')),
                'main_table.customer_id = newsletter.customer_id
                    AND newsletter.date_last_purchase < main_table.updated_at',
                array()
            )
            -> where("$queuedField = ?", Emv_DataSync_Helper_Service::SCHEDULED_VALUE);

        if ($list) {
            $select->columns(array('main_table.customer_id'));
        }
        if ($count) {
            $select->columns('COUNT(*)');
        }

        return $readConnection->fetchCol($select);
    }

    /**
     * @return array
     */
    public function numberOfRecordsInPurchaseInfoTable()
    {
        $resource       = Mage::getModel('core/resource');
        $readConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

        $select = $readConnection->select();
        $select->from(array('main_table' => $resource->getTableName('emvdatasync/purchase_info')), array('COUNT(*)'));

        return $readConnection->fetchCol($select);
    }

    /**
     * Remove useless purchase information
     * - The subscribers that haven't been scheduled
     * - The purchase date is outdated
     *
     * @return int - the number of rows which have been removed
     */
    public function removeUselessPurchaseInfo()
    {
        $resource        = Mage::getModel('core/resource');
        $writeConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);

        $select = $writeConnection->select();
        $select
            ->from(array('purchase' => $resource->getTableName('emvdatasync/purchase_info')), array())
            ->joinLeft(
                array('subscriber' => $resource->getTableName('newsletter/subscriber')),
                'purchase.customer_id = subscriber.customer_id',
                array()
            )
            ->where(
                'subscriber.subscriber_id IS NULL OR subscriber.queued = ?
                    OR subscriber.date_last_purchase > purchase.updated_at',
                 Emv_DataSync_Helper_Service::NOT_SCHEDULED_VALUE
            )
        ;
        $select->reset(Zend_Db_Select::DISTINCT);
        $select->reset(Zend_Db_Select::COLUMNS);
        $deleteQuery = sprintf('DELETE purchase %s', $select->assemble());

        $stmt = $writeConnection->query($deleteQuery);
        $result = $stmt->rowCount();
        return $result;
    }

    /**
     * Get list of customers to proceed.
     * If $count is given and equal to true => only return the total number of concerning customers
     * else we return the list of customers
     *
     * @param string $count
     * @param int $page (the page to retrieve)
     * @param int $pageSize (the page size)
     * @return array
     */
    public function getListCustomerToProceed($count = false, $page = 1, $pageSize = self::LIMIT_PAGE_SIZE)
    {
        $queuedField = Emv_DataSync_Helper_Service::FIELD_QUEUED;

        $resource       = Mage::getModel('core/resource');
        $readConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

        $select = $readConnection->select();
        $select->from(
                array('main_table' => $resource->getTableName('newsletter/subscriber')),
                array()
            )
            ->joinLeft(
                array('purchase' => $resource->getTableName('emvdatasync/purchase_info')),
                'purchase.customer_id = main_table.customer_id',
                array()
            )
            ->where('main_table.customer_id > 0')
            ->where("$queuedField = ?", Emv_DataSync_Helper_Service::SCHEDULED_VALUE)
            ->where('main_table.date_last_purchase IS NOT NULL')
            // when member does not have calculated purchase information or, this information is outdated
            ->where('purchase.id IS NULL OR purchase.updated_at < main_table.date_last_purchase')
        ;

        if ($count) {
            $select->columns('COUNT(*)');
        } else {
            $select->columns('customer_id');
            $select->limitPage($page, $pageSize);
        }

        return $readConnection->fetchCol($select);
    }

    /**
     * Prepare and return the select to calculate purchase information
     * (for now, we only accept the calculation for 1 global currency code)
     *
     * @param array $customerList
     * @return Zend_Db_Select|boolean
     */
    public function getSelectToCalculatePurchaseInformation($customerList = array())
    {
        if (is_array($customerList) && count($customerList)) {
            $resource       = Mage::getModel('core/resource');
            $readConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
            $select = $readConnection->select();

            $gmtDate = Mage::getModel('core/date')->gmtDate();
            $select->from(
                    array('flat_order' => $resource->getTableName('sales/order')),
                    array(
                        'email'                      => "GROUP_CONCAT( DISTINCT flat_order.customer_email SEPARATOR '|' )",
                        'customer_id'                => 'flat_order.customer_id',
                        'order_list'                 => "GROUP_CONCAT( DISTINCT flat_order.increment_id SEPARATOR '|' )",

                        new Zend_Db_Expr('"' . $gmtDate . '" as created_at'),
                        new Zend_Db_Expr('"' . $gmtDate . '" as updated_at'),

                        'base_currency_code'         => "GROUP_CONCAT( DISTINCT flat_order.base_currency_code SEPARATOR '|' )",

                        'total_order'                => 'COUNT(flat_order.entity_id)',

                        'total_ordered_item_qty'     => 'SUM(flat_order.total_item_count)',
                        'avg_item_qty'               => 'AVG(flat_order.total_item_count)',
                        'order_amount_total'         => 'SUM(flat_order.base_grand_total)',
                        'avg_order_amount_total'     => 'AVG(flat_order.base_grand_total)',
                        'discount_amount_total'      => 'ABS(SUM(flat_order.base_discount_amount))',
                        'avg_discount_amount_total'  => 'ABS(AVG(flat_order.base_discount_amount))',
                        'shipping_amount_total'      => 'SUM(flat_order.base_shipping_amount)',
                        'avg_shipping_amount_total'  => 'AVG(flat_order.base_shipping_amount)',

                        'min_order_amount_total'     => 'MIN(flat_order.base_grand_total)',
                        'max_order_amount_total'     => 'MAX(flat_order.base_grand_total)',
                        'first_order_date'           => 'MIN(flat_order.created_at)',
                        'last_order_date'            => 'MAX(flat_order.created_at)',
                        'min_total_ordered_item_qty' => 'MIN(flat_order.total_item_count)',
                        'max_total_ordered_item_qty' => 'MAX(flat_order.total_item_count)',

                        'shipping_list'              => "GROUP_CONCAT( DISTINCT flat_order.shipping_description SEPARATOR '|' )",
                        'payment_methods'            => "GROUP_CONCAT( DISTINCT payment.method SEPARATOR '|' )",
                        'coupon_list'                => "GROUP_CONCAT( DISTINCT flat_order.coupon_code SEPARATOR '|' )",
                        'nb_order_having_discount'   => 'COUNT(coupon_code)'
                    )
                )
                ->join(
                    array('subscriber' => $resource->getTableName('newsletter/subscriber')),
                    'subscriber.customer_id = flat_order.customer_id',
                    array()
                ) // only prepare data for subscriber
                ->join(
                    array('payment' => $resource->getTableName('sales/order_payment')),
                    'payment.parent_id = flat_order.entity_id',
                    array()
                ) // get used payment methods
                ->group(array('flat_order.customer_id'))
                ->where('flat_order.customer_id IN (?)', $customerList);
            ;

            return $select;
        }

        return false;
    }

    /**
     * Proceed purchase information for a given customer list
     *
     * @param array $customerList
     */
    public function proceedPurchaseInfoForList($customerList = array())
    {
        $resource        = Mage::getModel('core/resource');
        $writeConnection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);

        // split email list into small chunk to easily handle
        $chunkList = array_chunk($customerList, self::$chunkSize);

        foreach ($chunkList as $list) {
            $select = $this->getSelectToCalculatePurchaseInformation($list);
            if ($select) {
                $insert = sprintf(
                    "INSERT INTO `%s` (%s) %s",
                    $resource->getTableName('emvdatasync/purchase_info'),
                    implode(', ', $this->_fieldsForInsert),
                    $select->assemble()
                );
                $writeConnection->query($insert);
            }
        }
    }

    /**
     * Start the whole process to proceed purchase information
     */
    public function prepareList()
    {
        $errorList = array();
        if ($this->enabledPurchaseInformation()) {
            try {
                // the number of clients will be proceeded
                $nbToTreat = $this->getListCustomerToProceed(true);
                if (is_array($nbToTreat) &&  count($nbToTreat) && $nbToTreat[0] > 0) {
                    $limit = $this->getLimitPurchaseInformationTableSize();

                    // the number of left records to treat
                    $nbRest = 0;

                    // clean useless information
                    $this->removeUselessPurchaseInfo();
                    // the number of existing records
                    $nbExisting = $this->numberOfRecordsInPurchaseInfoTable();

                    if (is_array($nbExisting) &&  count($nbExisting)) {
                        if ($nbExisting[0] + $nbToTreat[0] <= $limit) {
                            $nbRest = $nbToTreat[0];
                        } else {
                            if ($limit > $nbExisting[0]) {
                                $nbRest = $limit - $nbExisting[0];
                            }
                        }
                    }

                    $nbPages = ceil($nbRest/self::LIMIT_PAGE_SIZE);
                    $nbTaken = 0;
                    for ($curPage = 1; $curPage <= $nbPages; $curPage++) {
                        // always take the first page
                        $customerList = $this->getListCustomerToProceed(false, 0, self::LIMIT_PAGE_SIZE);

                        $nbToTake = $nbRest - $nbTaken;
                        if ($nbToTake < self::LIMIT_PAGE_SIZE) {
                            $this->proceedPurchaseInfoForList(array_slice($customerList, 0, $nbToTake));
                            $nbTaken += $nbToTake;
                        } else {
                            $this->proceedPurchaseInfoForList($customerList);
                            $nbTaken += self::LIMIT_PAGE_SIZE;
                        }
                    }
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $errorList[] = $e->getMessage();
            }
        }

        return $errorList;
    }
}