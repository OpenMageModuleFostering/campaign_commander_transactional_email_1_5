<?php
/**
 * Extension status block
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Config_ExtensionStatus
    extends Emv_Core_Block_Adminhtml_Config_ExtensionStatus
{
    /**
     * List of call back functions to test
     * @var array
     */
    protected $_callbackList = array('getEmailTemplateRewriteStatus');

    /**
     * Check if email template has been correctly rewritten by our module or it's in conflict with another extension
     *
     * @return string
     */
    public function getEmailTemplateRewriteStatus()
    {
        $classModelName = 'core/email_template';
        $class = Mage::getModel($classModelName);

        $ok = true;
        $className = '';
        if (!$class instanceof Emv_Emt_Model_Mage_Core_Email_Template) {
            $ok = false;
            $className = get_class($class);
        }

        $message = '';
        if ($ok) {
            $image = $this->_getTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> Email Template class (<strong>"%s"</strong>) has been correctly overwritten.',
                $image,
                $classModelName
            );
        } else {
            $image = $this->_getUnTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> Email Template class (<strong>"%s"</strong>) has not been correctly overwritten - in conflict with the following class <strong>%s</strong>.',
                $image,
                $classModelName,
                $className
            );
        }
        return $message;
    }

}