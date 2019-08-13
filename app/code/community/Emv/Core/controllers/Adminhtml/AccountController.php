<?php
/**
 * This controller is used to manage all account view (grid list, edit/new form)
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Adminhtml_AccountController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Initialise action
     *  - load all layout
     *  - set active menu SmartFocus
     */
    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('emailvision/emvcore');
        $this->_title(Mage::helper('emvcore')->__('SmartFocus'))
            ->_title(Mage::helper('emvcore')->__('Accounts'));
    }

    /**
     * Check menu access
     *
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('emailvision/emvcore');
    }

    /**
     * Initialise the account model with the given account id in the parameter
     *
     * @return Emv_Core_Model_Account
     */
    protected function _initAccount()
    {
        $id = $this->getRequest()->getParam('id', null);
        $accountModel = Mage::getModel('emvcore/account')->load($id);
        Mage::register('current_emvaccount', $accountModel);

        return $accountModel;
    }

    /**
     * Action to display account grid
     */
    public function indexAction()
    {
        // save all messages from session
        $this->getLayout()->getMessagesBlock()->setMessages(
            $this->_getSession()->getMessages()
        );

        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('emvcore/adminhtml_accounts'));
        $this->renderLayout();
    }

    /**
     * Account Edit Action
     */
    public function editAction()
    {
        $accountModel = $this->_initAccount();

        // get emv account form data from session
        $data = $this->_getSession()->getEmvAccountFormData(true);
        if (isset($data)) {
            // set entered data if was error when we do save
            $accountModel->addData($data);
        }

        $this->_initAction();
        $this->_title(
            $accountModel->getId() ? $accountModel->getName() : Mage::helper('emvcore')->__('New Account')
        );
        $this->_addContent($this->getLayout()->createBlock('emvcore/adminhtml_account_edit'));
        $this->renderLayout();
    }

    /**
     * Account New Action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Account Delete Action
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('emvcore/account')->load($id);
                if ($model->getId()) {
                    $model->delete();
                    $this->_getSession()->addSuccess(
                        Mage::helper('emvcore')->__('SmartFocus account deleted.'));
                    $this->_redirect('*/*/');

                    return;
                }
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getPost('id')));
                return;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('emvcore')->__('Unable to find an account to delete.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $accountId = $this->getRequest()->getParam('id', null);

            // set empty data to null for the database and trim all input
            foreach ($data as $key => $value) {
                if ('' === $data[$key]) {
                    $data[$key] = null;
                } elseif (is_string($data[$key])) {
                    $data[$key] = trim($data[$key]);
                }
            }

            if (isset($data['emv_urls'])) {
                foreach($data['emv_urls'] as $nameService => $serviceData) {
                    $data['emv_urls'][$nameService]['url'] = trim($serviceData['url']);
                }
            }

            $accountModel = Mage::getModel('emvcore/account')->load($accountId);
            $accountModel->setData($data);

            try {
                $result = $accountModel->validate();

                if (is_array($result)) {
                    $this->_getSession()->setEmvAccountFormData($data);
                    foreach ($result as $message)
                    {
                        $this->_getSession()->addError($message);
                    }
                    $this->_redirect('*/*/edit', array('id' => $accountModel->getId()));
                    return;
                }

                $accountModel->save();
                $this->_getSession()->addSuccess(
                    Mage::helper('emvcore')->__('The SmartFocus account has been saved.'));

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $accountModel->getId()));
                    return;
                }
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setEmvAccountFormData($data);

                $this->_redirect('*/*/edit', array('id'=>$accountModel->getId()));
                return;
            }
        }
        $this->_redirect('*/*/');
        return;
    }
}