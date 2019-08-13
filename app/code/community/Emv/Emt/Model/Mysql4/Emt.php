<?php
/**
 * EmailVision email template resource class
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mysql4_Emt extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Constructor
     *
     * @see Mage_Core_Model_Resource_Abstract::_construct()
     */
    protected function _construct()
    {
        $this->_init('emvemt/emt','id');
    }


    /**
     * Retreive an SmartFocus email template by Magento template id and SmartFocus account id
     *
     * @param Mage_Core_Model_Abstract $emt
     * @return mixed
     */
    public function mageTemplateMapped(Mage_Core_Model_Abstract $emt)
    {
        $emtTable = $this->getTable('emvemt/emt');

        $select = $this->_getReadAdapter()->select();
        $select->from($emtTable);
        $select->where("{$emtTable}.mage_template_id = '{$emt->getMageTemplateId()}' AND ".
            "{$emtTable}.emv_account_id = '{$emt->getEmvAccountId()}' AND ".
            "{$emtTable}.id != '{$emt->getId()}'");

        return $this->_getReadAdapter()->fetchRow($select);
    }

    /**
     * Retreive an SmartFocus email template by EmailVision template id and SmartFocus account id
     *
     * @param Mage_Core_Model_Abstract $emt
     * @return mixed
     */
    public function emvTemplateExists(Mage_Core_Model_Abstract $emt)
    {
        $emtTable = $this->getTable('emvemt/emt');

        $select = $this->_getReadAdapter()->select();
        $select->from($emtTable);
        $select->where("{$emtTable}.emv_template_id = '{$emt->getEmvTemplateId()}' ".
            "AND {$emtTable}.id != '{$emt->getId()}'");

        return $this->_getReadAdapter()->fetchRow($select);
    }

}