<?php
/**
 * Input / output information renderer
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_DataProcessing_Process_Grid_Column_Renderer_Output
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Retrieve an URL for 'file' function
     *
     * @param array $params
     *
     * @return string
     */
    protected function _getFileDownloadUrl($params)
    {
        return $this->getUrl('*/*/getOutputFile', $params);
    }

    /**
     * Render a input / output text
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $index = $this->getColumn()->getIndex();
        $data = $row->getData($index);

        $label = '';
        if ($data) {
            $data = unserialize($data);
            if (is_array($data) && count($data)) {
                foreach ($data as $output) {
                    if (is_readable($output['path'])) {
                        $params = array('path' => base64_encode($output['path']),
                            'filename' => base64_encode($output['filename'])
                        );
                        $downloadUrl = $this->getUrl('*/*/getOutputFile', $params);

                        $label .= '<a href="'.$downloadUrl.'" title="'.htmlentities($output['label']).'">'
                            .htmlentities($output['label']).'</a>' . '<br/>';
                    } else {
                        $label .= htmlentities($output['label']) . '<br/>';
                    }
                }
            }
        }

        return $label;
    }
}