<?php
/**
 * EmailVision email template class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_MageTemplate extends Mage_Core_Model_Abstract
{
    /**
     * Get not mapped Magento template collection
     * @param string $emvAccountId
     * @param array $selectFields => selected fields
     * @return void|Mage_Core_Model_Mysql4_Email_Template_Collection
     */
    public function getNotMappedMageTemplateCollection($emvAccountId, $selectFields = array())
    {
        if(!is_numeric($emvAccountId))
        {
            return;
        }

        /* @var $magentoTemplates Mage_Core_Model_Mysql4_Email_Template_Collection */
        $magentoTemplates = Mage::getResourceModel('core/email_template_collection');

        $emt = Mage::getResourceModel('emvemt/emt_collection');
        $emt->addFieldToFilter('emv_account_id', $emvAccountId);
        $emt->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('mage_template_id')->where("emv_account_id = ?", $emvAccountId);

        $magentoTemplates->addFieldToFilter('template_id', array('nin' => $emt->getSelect()));
        if (count($selectFields)) {
            $magentoTemplates->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns($selectFields);
        }

        $magentoTemplates->load();

        return $magentoTemplates;
    }
}
