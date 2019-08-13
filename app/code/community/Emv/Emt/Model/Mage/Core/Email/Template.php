<?php
/**
 * Email template class
 * Override in order to add additional treatement on email sending mechanism.
 * The different sending method are defined in sendTransactional method
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Model_Mage_Core_Email_Template extends Mage_Core_Model_Email_Template
{
    /**
     * EmailVision send mode is used or not ?
     * @var boolean
     */
    private $_emvSendMode = false;

    /**
     * Xml path config for SmartFocus account
     */
    const XML_PATH_CONFIG_ACCOUNT = 'emvemt/transactional_service/account';

    /**
     * The last email has been used to send
     * @var string | array
     */
    protected $_lastEmail = null;

    /**
     * Log
     * @var Emv_Emt_Model_Log
     */
    protected $_log = null;

    /**
     * Last used data
     * @var array
     */
    protected $_lastData = array();

    /**
     * Send transactional email to recipient. 3 different sending methods :
     *  - Classic : Email will be sent by Magento system
     *  - Send : Email will be sent by EmailVision system
     *  - Create : Email will be sent by EmailVision system by using EmailVision template
     *
     * @param   int $templateId
     * @param   string|array $sender sender information, can be declared as part of config path
     * @param   string $email recipient email
     * @param   string $name recipient name
     * @param   array $vars varianles which can be used in template
     * @param   int|null $storeId
     * @return  Mage_Core_Model_Email_Template
     */
    public function sendTransactional($templateId, $sender, $email, $name, $vars=array(), $storeId=null)
    {
        // emailvision account id
        $accountId = Mage::getStoreConfig(self::XML_PATH_CONFIG_ACCOUNT, $storeId);

        /* @var $account Emv_Emt_Helper_Emvtemplate */
        $templateHelper = Mage::helper('emvemt/emvtemplate');
        $sendingData = $templateHelper->getDataForSending($templateId, $accountId);

        $emvemt = $sendingData['emv_template'];

        // get sending mode
        $mode = $emvemt->getData('emv_send_mail_mode_id');
        // use classic mode
        if ($mode === null) {
            $mode = Emv_Emt_Model_Mailmode::CLASSIC_MODE;
        }
        // if we are forced to send by emailvision defaut template
        if ($this->_emvSendMode)  {
            $mode = Emv_Emt_Model_Mailmode::EMV_SEND;
            if ($accountId) {
                $account = $sendingData['account'];
                if (!$account->getId()) {
                    $accountAndDefaultTemplate  = $templateHelper->getAccountAndDefaultTemplate($accountId);
                    $sendingData['account']     = $accountAndDefaultTemplate['account'];
                    $sendingData['tmp_sending'] = $accountAndDefaultTemplate['tmp_sending'];
                }
            }
        }

        $this->_lastEmail = $email;

        // prepare log
        $this->_log = $templateHelper->prepareLogBeforeSend($mode, $sendingData, $this->_lastEmail, $storeId);
        try {
            switch ($mode) {
                // classic mode - email is sent by Magento
                case Emv_Emt_Model_Mailmode::CLASSIC_MODE :
                    parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
                    $templateHelper->prepareLogAfterSend($this->_log, $this->_lastData, $this->_lastEmail, true);
                    break;

                // create mode - email is sent by using EmailVision template
                case Emv_Emt_Model_Mailmode::EMV_CREATE :
                    $this->sendEmailByEmvTemplate($sendingData, $email, $vars, $storeId);
                    break;

                // send mode - email is sent by EmailVision using Magento template
                case Emv_Emt_Model_Mailmode::EMV_SEND :
                    $this->sendEmailByDefaultTemplate($sendingData, $templateId, $sender, $email, $name, $vars, $storeId);
                    break;
            }
        } catch (Exception $e) {
            Mage::logException($e);

            $error = array(
                'msg'  => $e->getMessage(),
                'code' => $e->getCode()
            );

            $templateHelper->prepareLogAfterSend($this->_log, $this->_lastData, $this->_lastEmail, false, $error, $storeId);
        }
    }

    /**
     * Send Email by EmailVision API using EmailVision Default Template
     *
     * @param array $sendingData
     * @param string $templateId
     * @param string | array $sender
     * @param string | array $email
     * @param string | array $name
     * @param array $vars
     * @param string $storeId
     * @return boolean | string
     */
    public function sendEmailByDefaultTemplate($sendingData, $templateId, $sender, $email, $name, $vars=array(), $storeId=null)
    {
        /* @var $defaultTemplate Emv_Emt_Model_Emt */
        $defaultTemplate = $sendingData['tmp_sending'];
        $templateHelper = Mage::helper('emvemt/emvtemplate');
        /* @var $account Emv_Core_Model_Account */
        $account =  $sendingData['account'];

        $this->_lastData = array();

        // get emailvision parameters from template
        $params = $defaultTemplate->getEmailVisionParams();

        if (!is_array($email)) {
            $email = array($email);
        }
        if (!is_array($name)) {
            $name = array($name);
        }

        $result = true;

        // try to validate emv params, in case invalid throw exception
        $templateHelper->validateEmvParams($params);

        // load email template
        if (is_numeric($templateId)) {
            $this->load($templateId);
        } else {
            $localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
            $this->loadDefault($templateId, $localeCode);
        }

        // prepare body and subject for email
        $this->setTemplateText($this->getProcessedTemplate($vars));
        $this->setTemplateSubject($this->getProcessedTemplateSubject($vars));

        // prepare sender name and email
        if (!is_array($sender)) {
            $this->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId));
            $this->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email', $storeId));
        } else {
            $this->setSenderName($sender['name']);
            $this->setSenderEmail($sender['email']);
        }

        // prepare variables in order to use default template
        if($this->getTemplateType() == Mage_Newsletter_Model_Template::TYPE_HTML) {
            $varContent = array('1' => $this->getTemplateText());
        } else {
            $varContent = array('2' => $this->getTemplateText());
        }
        $varDyn = array (
            'SUBJECT'     => $this->getTemplateSubject(),
            'FROM'        => $this->getSenderName(),
            'FROM_EMAIL'  => $this->getSenderEmail(),
            'TO'          => '',
            'REPLY'       => $this->getSenderName(),
            'REPLY_EMAIL' => $this->getSenderEmail(),
        );

        foreach ($email as $index => $oneMail) {
            try {
                // Name destination
                $oneName = $name[0];
                if (isset($name[$index])) {
                    $oneName = $name[$index];
                }
                $varDyn['TO'] = $oneName;

                // set last email
                $this->_lastEmail = $oneMail;
                // set last data
                $this->_lastData = $this->prepareLastData(
                    $params['emv_encrypt'], $params['emv_id'], $params['emv_random'], $params['emv_name'],
                    $varContent, $varDyn
                );

                /* @var $notificationService Emv_Core_Model_Service_Notification */
                $notificationService = Mage::getSingleton('emvcore/service_notification');
                $result = $notificationService->sendTemplate(
                    $params['emv_encrypt'],
                    $params['emv_id'],
                    $params['emv_random'],
                    $oneMail,
                    $varContent,
                    $varDyn,
                    $account,
                    $storeId
                );

                // everything is ok
                $templateHelper->prepareLogAfterSend($this->_log, $this->_lastData, $this->_lastEmail, true, array(), $storeId);
            } catch(Exception $e) {
                Mage::logException($e);
                $error = array(
                    'msg'  => $e->getMessage(),
                    'code' => $e->getCode()
                );

                $templateHelper->prepareLogAfterSend($this->_log, $this->_lastData, $this->_lastEmail, false, $error, $storeId);
            }
        }

        return $result;
    }

    /**
     * Prepare an assocative array for last data
     *
     * @param string $emvEncrypt
     * @param string $emvId
     * @param string $emvRandom
     * @param string $emvName
     * @param array $emvContent
     * @param array $emvDyn
     * @return array
     */
    public function prepareLastData($emvEncrypt, $emvId, $emvRandom, $emvName, $emvContent = array(), $emvDyn = array())
    {
        return array(
            'emv_encrypt'           => $emvEncrypt,
            'emv_id'                => $emvId,
            'emv_random'            => $emvRandom,
            'emv_name'              => $emvName,

            'emv_content_variables' => $emvContent,
            'emv_dyn_variables'     => $emvDyn
        );
    }

    /**
     * Send Email by EmailVision using an EmailVision template
     *
     * @param array $sendingData
     * @param array | string $email
     * @param array $vars
     * @param string $storeId
     * @return boolean | string
     */
    public function sendEmailByEmvTemplate($sendingData, $email, $vars = array(), $storeId = null)
    {
        $emvemt = $sendingData['emv_template'];
        $templateHelper = Mage::helper('emvemt/emvtemplate');

        // validate emailvision parameters
        $params = $emvemt->getEmailVisionParams();
        $templateHelper->validateEmvParams($params);

        $this->_lastData = array();

        /* @var $attributes Emv_Emt_Model_Mysql4_Attribute_Collection */
        $attributes = Mage::getResourceModel('emvemt/attribute_collection');
        // get attribute mapping
        $mappedAttributes = $attributes->addEmtFilter($emvemt->getId())
            ->load()->getMappingWithType();

        // get content variables
        $realValueEmvContentAttributes = array();
        if (isset($mappedAttributes[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_CONTENT])) {
            $realValueEmvContentAttributes = $this->prepareAttributes(
                $mappedAttributes[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_CONTENT],
                $vars
            );
        }
        // get dynamic variables
        $realValueEmvDynAttributes = array();
        if (isset($mappedAttributes[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_DYN])) {
            $realValueEmvDynAttributes = $this->prepareAttributes(
                $mappedAttributes[Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_DYN],
                $vars
            );
        }

        $result = true;
        if (!is_array($email)) {
            $email = array($email);
        }

        /* @var $notificationService Emv_Core_Model_Service_Notification */
        $notificationService = Mage::getSingleton('emvcore/service_notification');
        foreach ($email as $oneMail) {
            try {
                // set last email
                $this->_lastEmail = $oneMail;
                // set last data$notificationService
                $this->_lastData = $this->prepareLastData(
                    $params['emv_encrypt'], $params['emv_id'], $params['emv_random'], $params['emv_name'],
                    $realValueEmvContentAttributes, $realValueEmvDynAttributes
                );
                $result = $notificationService->sendTemplate(
                    $params['emv_encrypt'],
                    $params['emv_id'],
                    $params['emv_random'],
                    $oneMail,
                    $realValueEmvContentAttributes,
                    $realValueEmvDynAttributes,
                    $sendingData['account'],
                    $storeId
                );

                // everything is ok
                $templateHelper->prepareLogAfterSend($this->_log, $this->_lastData, $this->_lastEmail, true, array(), $storeId);
            } catch(Exception $e) {
                Mage::logException($e);

                $error = array(
                    'msg'  => $e->getMessage(),
                    'code' => $e->getCode()
                );

                $templateHelper->prepareLogAfterSend($this->_log, $this->_lastData, $this->_lastEmail, false, $error, $storeId);
            }
        }

        return $result;
    }

    /**
     * Return values of magento template variables in an array
     *
     * @param array $attributesArray
     * @param array $var array of magento attributes (cutomer, order ...) from which we going to
     *      pick values
     * @return array
     */
    public function prepareAttributes($attributesArray, $var)
    {
        $attributesWithValues = array();

        $this->setTemplateStyles(null);

        foreach($attributesArray as $emv => $mage) {
            if (preg_match(Varien_Filter_Template::CONSTRUCTION_PATTERN, $mage)) {
                $this->setTemplateText($mage);
                $attributesWithValues[$emv] = $this->getProcessedTemplate($var);
            } else {
                //it's a constant
                $attributesWithValues[$emv] = $mage;
            }
        }

        return $attributesWithValues;
    }

    /**
     * Sending with default EmailVision Template.
     *
     * @param boolean $emvSendMode
     * @return Emv_Emt_Model_Mage_Core_Email_Template
     */
    public function setEmvSend($emvSendMode)
    {
        $this->_emvSendMode = $emvSendMode;
        return $this;
    }
}
