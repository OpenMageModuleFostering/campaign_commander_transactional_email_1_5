<?php
/**
 * Email Sending Log Resource Collection Class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Log_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('emvemt/log','emvemt/log');
    }

    /**
     * Join collection with the EmailVision Account name
     *
     * @return Emv_Emt_Model_Mysql4_Log_Collection
     */
    public function prepareQueryToGetAccountName()
    {
        // left join to get also
        $select = $this->getSelect();
        $select->joinLeft(
            array('account' => $this->getTable('emvcore/account')),
            'main_table.account_id = account.id',
            array('account_name' => 'account.name')
        );

        return $this;
    }
}