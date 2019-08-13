<?php
/**
 * EmailVision email template Resource Collection Class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Emt_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('emvemt/emt','emvemt/emt');
    }

    /**
     * Join collection with the Magento template name
     *
     * @return Emv_Emt_Model_Mysql4_Emt_Collection
     */
    public function prepareQueryToGetMagentoName()
    {
        // left join to get also
        $select = $this->getSelect();
        $select->joinLeft(
                array('core/email_template' => $this->getTable('core/email_template')),
                'main_table.mage_template_id=template_id',
                array('template_code'));
        return $this;
    }

    /**
     * Join collection with the EmailVision Account name
     *
     * @return Emv_Emt_Model_Mysql4_Emt_Collection
     */
    public function prepareQueryToGetAccountName()
    {
        // left join to get also
        $select = $this->getSelect();
        $select->joinLeft(
            array('account' => $this->getTable('emvcore/account')),
            'main_table.emv_account_id = account.id',
            array('account_name' => 'account.name')
        );
        return $this;
    }

    /**
     *
     * Add account filter into collection
     *
     * @param string $accountId - EmailVision account
     * @return Emv_Emt_Model_Mysql4_Emt_Collection
     */
    public function addAccountFilter($accountId)
    {
        $this->addFieldToFilter('emv_account_id', array('eq' => $accountId));

        return $this;
    }

    /**
     * Add Magento Tempalte filter
     *
     * @param string $mageTemplateId  - Magento template id
     * @return Emv_Emt_Model_Mysql4_Emt_Collection
     */
    public function addMageTemplateFilter($mageTemplateId)
    {
        $this->addFieldToFilter('mage_template_id', array('eq' => $mageTemplateId));

        return $this;
    }

    /**
     * @return Emv_Emt_Model_Mysql4_Emt_Collection
     */
    public function unselectEmvSendingTemplate()
    {
        $this->addFieldToFilter('mage_template_id', array('neq' => Emv_Emt_Model_Emt::MAGENTO_TEMPLATE_ID_FOR_EMV_SEND));
        return $this;
    }
}