<?php
/**
 * Emt grid renderer for email vision template name
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Template_Grid_Renderer_Emvname extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render mode
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $params = $row->getEmailVisionParams();
        if (isset($params['emv_id']) && isset($params['emv_name'])) {
            return $params['emv_id'] . ' / ' . $params['emv_name'];
        }
    }

}
