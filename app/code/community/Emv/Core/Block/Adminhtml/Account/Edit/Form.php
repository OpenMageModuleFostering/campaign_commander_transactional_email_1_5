<?php
/**
 * Account edit view form
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_Account_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareLayout()
    {
        $this->setChild('webservice',
            $this->getLayout()->createBlock('emvcore/adminhtml_account_edit_associatedUrls')
        );
        return parent::_prepareLayout();
    }

    /**
     * Prepare form
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Form::_prepareForm()
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id'     => 'edit_form',
                'action' => $this->getUrl('*/*/save'),
                'method' => 'post',
            )
        );

        // get account from registry, if empty create a new one
        $account = Mage::registry('current_emvaccount');
        if (null === $account) {
            $account = Mage::getModel('emvcore/account');
        }
        if ($account->getId()) {
            $form->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        $fieldset = $form->addFieldset(
            'account_info',
            array(
                'legend' => Mage::helper('emvcore')->__('Account Information'),
            )
        );

        $fieldset->addField('name', 'text', array(
            'value'    => '',
            'label'    => Mage::helper('emvcore')->__('Account Name'),
            'name'     => 'name',
            'required' => true,
            'class'    => 'required-entry',
        ));

        // api credential
        $fieldset = $form->addFieldset(
            'api_info',
            array(
                'legend' => Mage::helper('emvcore')->__('API Information'),
            )
        );

        $fieldset->addField('account_login', 'text', array(
            'value'    => '',
            'label'    => Mage::helper('emvcore')->__('API Login'),
            'name'     => 'account_login',
            'required' => true,
            'class'    => 'required-entry',
        ));

        $fieldset->addField('account_password', 'password', array(
            'value'    => '',
            'label'    => Mage::helper('emvcore')->__('API Password'),
            'name'     => 'account_password',
            'required' => true,
            'class'    => 'required-entry',
        ));

        $fieldset->addField('manager_key', 'text', array(
            'value'    => '',
            'label'    => Mage::helper('emvcore')->__('API Manager Key'),
            'name'     => 'manager_key',
            'required' => true,
            'class'    => 'required-entry',
        ));

        // proxy
        $fieldset = $form->addFieldset(
            'proxy_info',
            array(
                'legend' => Mage::helper('emvcore')->__('Proxy Information'),
            )
        );

        $fieldset->addField('use_proxy', 'select', array(
            'value'    => '',
            'label'    => Mage::helper('emvcore')->__('API Uses Proxy'),
            'name'     => 'use_proxy',
            'required' => true,
            'class'    => 'validate-select',
            'values'   => $this->_getYesNoArray(),
        ));

        $fieldset->addField('proxy_host', 'text', array(
            'value'    => '',
            'label'    => Mage::helper('emvcore')->__('Proxy Host'),
            'name'     => 'proxy_host',
        ));

        $fieldset->addField('proxy_port', 'text', array(
            'value' => '',
            'label' => Mage::helper('emvcore')->__('Proxy Port'),
            'name'  => 'proxy_port',
            'class' => 'validate-digits validate-greater-than-zero',
        ));

        // urls
        $fieldset = $form->addFieldset(
            'emv_urls',
            array(
                'legend' => Mage::helper('emvcore')->__('Associated Webservice Urls'),
            )
        );
        $fieldset->addField('available_webservices', 'select', array(
            'value'  => '',
            'label'  => Mage::helper('emvcore')->__('Available Webservices'),
            'name'   => 'available_webservices',
            'values' => $account->getAvailableUrlTypesToEnter(),
        ));

        // add a new block to handle associated urls
        $this->getChild('webservice')->setData('account', $account);
        $fieldset->addField('webservice', 'note', array(
            'text'   => $this->getChildHtml('webservice'),
        ));

        $form->setUseContainer(true);
        $form->setValues($account->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get yes/no array
     * @return array
     */
    protected function _getYesNoArray()
    {
        return array (
            array (
                'value' => 0,
                'label' => Mage::helper('emvcore')->__('No')
            ),
            array(
                'value' => 1,
                'label' => Mage::helper('emvcore')->__('Yes')
            ),
        );
    }

}