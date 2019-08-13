<?php
/**
 * Links renderer
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_DataProcessing_Process_Grid_Column_Renderer_Links
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render a link to the log/report/exception files
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        if (!$row instanceof Emv_Core_Model_DataProcessing_Process) {
            return '';
        }

        $output = '';
        if ($row->checkLogData()) {
            $url = Mage::helper('emvcore')->getLogUrlForProcess($row);
            $output .= sprintf('<a href="%s">%s</a>', $url, $this->helper('emvcore')->__('View Log'));
        }
        return $output;
    }
}