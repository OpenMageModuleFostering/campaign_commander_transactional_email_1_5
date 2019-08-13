<?php
/**
 * Data helper for Sync Services
 *
 * @category    Emv
 * @package     Emv_DataSync
 * @author      Minh Quang VO (minhquang.vo@smartfocus.com)
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 */
class Emv_DataSync_Helper_Service extends Mage_Core_Helper_Abstract
{
    /**
     * @var string
     */
    protected $_customerTypeId = null;

    /**
     * @var string
     */
    protected $_customerAddressTypeId = null;

    /**
     * Newsletter Subscriber Additional Fields
     */
    const FIELD_MEMBER_LAST_UPDATE   = 'member_last_update_date';
    const FIELD_DATA_LAST_UPDATE     = 'data_last_update_date';
    const FIELD_PURCHASE_LAST_UPDATE = 'date_last_purchase';
    const FIELD_DATE_UNJOIN          = 'date_unjoin';
    const FIELD_QUEUED               = 'queued';

    /**
     * Values for "Queued" Field
     */
    const SCHEDULED_VALUE   = 1;
    const NOT_SCHEDULED_VALUE = 0;

    /**
     * Get magento attribute for the given attribute ids
     *
     * @param array $attributeIds
     * @return Mage_Eav_Model_Resource_Entity_Attribute_Collection
     */
    public function getMagentoAttributes($attributeIds = array(), $attributeCodes = array())
    {
        $magentoAttributes = array();
        $customerTypeId              = $this->getCustomerTypeId();
        $customerAddressEntityTypeId = $this->getCustomerAddressTypeId();

        $collection = Mage::getResourceModel('eav/entity_attribute_collection')
            ->addFieldToFilter(
                'entity_type_id',
                array('in' => array($customerTypeId, $customerAddressEntityTypeId))
            );

        if (count($attributeIds)) {
            $collection->addFieldToFilter(
                    'attribute_id',
                    array('in' => $attributeIds)
                )
            ;
        }
        if (count($attributeCodes)) {
            $collection->addFieldToFilter(
                    'attribute_code',
                    array('in' => $attributeCodes)
                )
            ;
        }

        return $collection;
    }

    /**
     * Get date in EmailVison format in the following format : m/d/Y H:i:s (such as 10/09/2013 14:31:24)
     *
     * @param string $date in GMT timezone
     * @return string
     */
    public function getEmailVisionDate($date)
    {
        $dateModel = Mage::getSingleton('core/date');
        return $dateModel->date(
            'm/d/Y H:i:s',
            $date
        );
    }

    /**
     * @return string
     */
    public function getCustomerTypeId()
    {
        if ($this->_customerTypeId == null) {
            $this->_customerTypeId = Mage::getModel('eav/entity')->setType('customer')->getTypeId();
        }
        return $this->_customerTypeId;
    }

    /**
     * @return string
     */
    public function getCustomerAddressTypeId()
    {
        if ($this->_customerAddressTypeId == null) {
            $this->_customerAddressTypeId = Mage::getModel('eav/entity')->setType('customer_address')->getTypeId();
        }
        return $this->_customerAddressTypeId;
    }

    /**
     * Set member last update date for a list of members (perform sql query directly)
     *
     * @param array $updatedSubscribers
     * @param array $rejoinedSubscribers
     * @param string $time
     * @return boolean
     */
    public function massSetMemberLastUpdateDate(
        array $updatedSubscribers = array(),
        array $rejoinedSubscribers = array(),
        $time = null
    )
    {
        $error = true;

        if (count($updatedSubscribers) > 0) {
            /* @var $write Varien_Db_Adapter_Pdo_Mysql */
            $resource = Mage::getSingleton('core/resource');
            $write    = $resource->getConnection('core_write');
            $subscriberTable = $resource->getTableName('newsletter/subscriber');

            // date should be store ind GMT timezone
            $now = Mage::helper('emvdatasync')->getFormattedGmtDateTime();
            if ($time == null) {
                $time = $now;
            }

            try {
                // updated subscriber treatment
                $set = array(
                    self::FIELD_MEMBER_LAST_UPDATE => $time,
                    self::FIELD_QUEUED             => self::NOT_SCHEDULED_VALUE
                );
                $where = array('subscriber_id IN (?)' => $updatedSubscribers);
                $write->update($subscriberTable, $set, $where);

                if (count($rejoinedSubscribers) > 0) {
                    // rejoined subscriber treatment
                    $set = array(
                        self::FIELD_DATE_UNJOIN => null,
                    );
                    $where = array('subscriber_id IN (?)' => $rejoinedSubscribers);
                    $write->update($subscriberTable, $set, $where);
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $error  = 'Exception while processing massSetMemberLastUpdateDate with exceptionMessage:\n        '
                    . $e->getMessage();
                $error .= '\n        Subscribers ids detail: ' . implode(', ', $updatedSubscribers);
            }
        }

        return $error;
    }
}