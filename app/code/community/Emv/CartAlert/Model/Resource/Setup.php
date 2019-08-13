<?php
/**
 * Set up for module cart alert
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Model_Resource_Setup extends Mage_Eav_Model_Entity_Setup
{
    /**
     * @return array
     */
    public function getDefaultEntities()
    {
        $entities = parent::getDefaultEntities();
        $entites = array_merge_recursive($entities, $this->getAdditionalEntities());
        return $entities;
    }

    /**
     * @return array
     */
    public function getAdditionalEntities()
    {
        return Array(
            'customer' => Array(
                'attributes' => Array(
                    'abandonment_subscribed' => Array(
                        'label'            => 'Subscribed from abandonned cart notifications',
                        'type'             => 'int',
                        'input'            => 'select',
                        'is_visible'       => true,
                        'source'           => 'eav/entity_attribute_source_boolean',
                        'required'         => true,
                        'user_defined'     => true,
                        'default'          => true,
                        'sort_order'       => 1000,
                        'system'           => false,
                        'position'         => 1000,
                        'adminhtml_only'   => 0,
                    ),
                )
            )
        );
    }

    /**
     * Update customer form with additional attributes
     */
    public function updateCustomerForms()
    {
        $entities = $this->getAdditionalEntities();

        $attributes = $entities['customer']['attributes'];
        foreach ($attributes as $attributeCode => $data) {
            $eavConfig = Mage::getSingleton('eav/config');
            $attribute = $eavConfig->getAttribute('customer', $attributeCode);
            if (!$attribute) {
                continue;
            }
            if (false === ($attribute->getData('is_system') == 1 && $attribute->getData('is_visible') == 0)) {
                $usedInForms = array(
                    'customer_account_create',
                    'customer_account_edit',
                    'checkout_register',
                );

                if (!empty($data['adminhtml_only'])) {
                    $usedInForms = array('adminhtml_customer');
                } else {
                    $usedInForms[] = 'adminhtml_customer';
                }
                if (!empty($data['admin_checkout'])) {
                    $usedInForms[] = 'adminhtml_checkout';
                }

                $attribute->setData('used_in_forms', $usedInForms);
            }

            $attribute->save();
        }
    }
}