<?php
/**
 * Emt grid renderer for email vision template name
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Grid_Renderer_MageTemplate extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    const INVALID_EMAIL_TEMPLATE = 'N/A';

    /**
     * Render mode
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        if ($row->getTemplateCode()) {
            return  $row->getTemplateCode();
        } else {
            return self::INVALID_EMAIL_TEMPLATE;
        }
    }

}
