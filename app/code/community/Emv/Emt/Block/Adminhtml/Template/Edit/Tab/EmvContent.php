<?php
/**
 *  EMV CONTENT attribute tab for SmartFocus email template
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Edit_Tab_EmvContent extends Emv_Emt_Block_Adminhtml_Template_Edit_Tab_EmvDyn
{
    /**
     * Attribute type (EMV CONTENT)
     * @var string
     */
    protected $_attributeType = Emv_Emt_Model_Attribute::ATTRIBUTE_TYPE_EMV_CONTENT;

    /**
     * Max rows for textarea
     * @var int
     */
    protected $_maxRowsForTextarea = 10;
}