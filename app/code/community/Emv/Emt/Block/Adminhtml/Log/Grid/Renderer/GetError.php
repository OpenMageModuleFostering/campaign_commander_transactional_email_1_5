<?php
/**
 * Get error for log renderer
 *
 * @category    Emv
 * @package     Emv_Emt
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Emt_Block_Adminhtml_Log_Grid_Renderer_GetError extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render ship action
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $html = '';
        if ($row->getError()) {
            $url = $this->getUrl('*/*/getError', array('id' => $row->getId()));
            $html = sprintf('<a href="%s">%s</a>', $url,  Mage::helper('emvemt')->__('Get Error'));
        }
        return $html;
    }
}