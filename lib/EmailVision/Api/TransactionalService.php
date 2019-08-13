<?php
/**
 * Transactional Message Api
 *
 * @category    EmailVision
 * @package     EmailVision_Api
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 * @copyright Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class EmailVision_Api_TransactionalService extends EmailVision_Api_Common
{
    /**
     * Default Campagin Commander template type
     */
    const DEFAULT_EMV_TEMPLATE_TYPE = 'TRANSACTIONAL';

    /**
     * Default send template name
     */
    const DEFAULT_TEMPLATE_SEND = 'emv_send_temporary_template';

    /**
     * Default template type
     */
    const DEFAULT_TEMPLATE_TYPE = 'TRANSACTIONAL';

    /**
     * How many templates per page
     */
    const LIST_SIZE = 15;

    /**
     * (non-PHPdoc)
     * @see EmailVision_Api_Common::decorateException()
     */
    public function decorateException(Exception $e)
    {
        parent::decorateException($e);

        if ($e instanceof EmailVision_Api_Exception) {
            $messageException = $e->getMessage();
            $message = '';

            if (stripos($messageException, 'GET_TEMPLATE_FAILED') !== false) {
                $e->setCode(EmailVision_Api_Exception::INVALID_TEMPLATE_ID);
            }

            if ($message) {
                $message .= ' - Details from server return : ' . $messageException;
                $e->setMessage($message);
            }
        }
    }

    /**
     * Get Transactional template by id
     *
     * @param string $id
     * @param boolean $closeApi
     * @return NULL | stdClass
     * @throws EmailVision_Api_Exception
     */
    public function getTemplateById($id, $closeApi = true)
    {
        try {
            $params = array(
                'token' => $this->openApiConnection(),
                'id' => $id,
            );

            $wsResult = $this->soapCall('getTemplate', $params);
        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        if (isset($wsResult->return)) {
            return $wsResult->return;
        } else {
            return null;
        }
    }

    /**
     * Create default emv send template and return template id
     *
     * @param string $templateName
     * @param boolean $closeApi
     * @return NULL | string
     * @throws EmailVision_Api_Exception
     */
    public function createDefaultEmvSendTemplate($templateName = self::DEFAULT_TEMPLATE_SEND, $closeApi = true)
    {
        $templateId = null;
        try {
            $params = array(
                'token' => $this->openApiConnection(),
                'name' => $templateName,
                'subject' => '[EMV DYN]SUBJECT[EMV /DYN]',
                'description' => 'DO NOT DELETE',
                'from' => '[EMV DYN]FROM[EMV /DYN]',
                'fromEmail' => '[EMV DYN]FROM_EMAIL[EMV /DYN]',
                'to' => '[EMV DYN]TO[EMV /DYN]',

                'body' => '[EMV HTMLPART]
<html>
<body>
[EMV CONTENT]1[EMV /CONTENT]
</body>
</html>
[EMV TEXTPART]
[EMV CONTENT]2[EMV /CONTENT]',

                'encoding' => 'UTF-8',
                'replyTo' => '[EMV DYN]REPLY[EMV /DYN]',
                'replyToEmail' => '[EMV DYN]REPLY_EMAIL[EMV /DYN]',
                'type' => 'TRANSACTIONAL',
            );

            // use createTemplate method
            $wsResult = $this->soapCall('createTemplate', $params);
            if (isset($wsResult->return)) {
                $templateId = $wsResult->return;
            }
        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $templateId;
    }

    /**
     * Get default template send
     * @return NULL | stdClass
     * @throws EmailVision_Api_Exception
     */
    public function getDefaultTemplateSend($defaultName = self::DEFAULT_TEMPLATE_SEND, $closeApi = true)
    {
        $defaultTemplate = null;

        $defaultTemplateId = $this->getTemplatesByField($defaultName, 'name', 1, self::DEFAULT_EMV_TEMPLATE_TYPE, false);
        if (is_array($defaultTemplateId) && count($defaultTemplateId) >= 1) {
            $defaultTemplate = $this->getTemplateById($defaultTemplateId[0], false);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $defaultTemplate;
    }

    /**
     * Get templates by field
     *
     * @param string $value
     * @param string $field
     * @param int $limit
     * @param string $type
     * @param boolean $closeApi
     * @return array
     * @throws EmailVision_Api_Exception
     */
    public function getTemplatesByField($value, $field = 'name', $limit = 1, $type = self::DEFAULT_EMV_TEMPLATE_TYPE, $closeApi = true)
    {
        $listTemplates = array();
        try {
            $params = array(
                'token' => $this->openApiConnection(),
                'type'  => self::DEFAULT_TEMPLATE_TYPE,
                'field' => $field,
                'value' => $value,
                'limit' => $limit,
            );

            // use getTemplatesByField method
            $wsResult = $this->soapCall('getTemplatesByField', $params);

            if (property_exists($wsResult, 'return')) {
                if (is_array($wsResult->return)) {
                    $listTemplates = $wsResult->return;
                } elseif (is_numeric($wsResult->return)) {
                    $listTemplates[] = $wsResult->return;
                }
            }
        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $listTemplates;
    }

    /**
     * Get template by period
     *
     * @param $from string date with format 'YYYY-MM-DD HH:MM:SS'
     * @param $to string date with format 'YYYY-MM-DD HH:MM:SS'
     * @return array
     * @throws EmailVision_Api_Exception
     */
    public function getTemplatesByPeriod($from, $to, $closeApi = true)
    {
        $listTemplates = array();
        try {
            $params = array(
                'token'     => $this->openApiConnection(),
                'type'      => self::DEFAULT_TEMPLATE_TYPE,
                'dateBegin' => $from,
                'dateEnd'   => $to,
            );

            // use getTemplatesByPeriod method
            $wsResult = $this->soapCall('getTemplatesByPeriod', $params);
            if (property_exists($wsResult, 'return')) {
                if (is_array($wsResult->return)) {
                    $listTemplates = $wsResult->return;
                } elseif (is_numeric($wsResult->return)) {
                    $listTemplates[] = $wsResult->return;
                }
            }
        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $listTemplates;
    }

    /**
     * Get template summary list
     *
     * @param int $page
     * @param int $size
     * @param string $closeApi
     * @param array $search
     *     - from : date with format 'YYYY-MM-DDTHH:MM:SS'
     *     - to   : date with format 'YYYY-MM-DDTHH:MM:SS'
     *     - name : template name
     * @return array :
     *   - total_templates : how many templates in SmartFocus account
     *   - total_retreived : how many templates has been retrieved
     *   - next_page : the eventual page number
     *   - page_size : how many templates per page
     *   - actual_page : the current page
     *   - list_retreived : retreived template id
     */
    public function getTemplateSummmaryList($page = 1, $size = self::LIST_SIZE, $search = array(), $closeApi = true)
    {
        $result = array(
            'total_templates' => 0,
            'total_retreived' => 0,
            'next_page'       => 0,
            'page_size'       => $size,
            'actual_page'     => $page,
            'list_retreived'  => array()
        );

        try {
            $params = array(
                'token'     => $this->openApiConnection(),
                'listOptions' => array(
                    'page'     => $page,
                    'pageSize' => $size
                ),
            );

            // prepare search parameters
            if (count($search)) {
                $paramSearch = array();
                if (isset($search['from']) && $search['from']) {
                    $paramSearch['minCreationDate'] = $search['from'];
                }
                if (isset($search['to']) && $search['to']) {
                    $paramSearch['maxCreationDate'] = $search['to'];
                }
                if (isset($search['name']) && $search['name']) {
                    $paramSearch['name'] = $search['name'];
                }

                if (count($paramSearch)) {
                    $params['listOptions']['search'] = $paramSearch;
                }
            }

            // use getTemplatesByPeriod method
            $wsResult = $this->soapCall('getTemplateSummaryList', $params);

            // handle server return
            if (property_exists($wsResult, 'return')) {
                if (
                    property_exists($wsResult->return, 'templateSummaryList')
                    && property_exists($wsResult->return->templateSummaryList, 'templateSummary')
                ) {
                    if (is_array($wsResult->return->templateSummaryList->templateSummary)) {
                        $result['list_retreived']  = $wsResult->return->templateSummaryList->templateSummary;
                        $result['total_retreived'] = count($result['list_retreived']);
                    }
                }
                if (isset($wsResult->return->nbTotalItems)) {
                    $result['total_templates'] = $wsResult->return->nbTotalItems;
                }
                if (isset($wsResult->return->nextPage) && $wsResult->return->nextPage > 0) {
                    $result['next_page'] = $wsResult->return->nextPage;
                }
            }

        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $result;
    }

    /**
     * Get template preview
     *
     * @param string $templateId
     * @param string $part
     * @param string $closeApi
     *
     * @return false - in case of errors | string
     */
    public function getTemplatePreview($templateId, $part='HTML', $closeApi = true)
    {
        $result = false;

        try {
            $params = array(
                'token' => $this->openApiConnection(),
                'id'    => $templateId,
                'part'  => $part
            );
            // use getTemplatesByPeriod method
            $wsResult = $this->soapCall('getTemplatePreview', $params);
            if (property_exists($wsResult, 'return')) {
                $result = $wsResult->return;
            }
        }  catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $result;
    }

    /**
     * Get template preview HTML with eventual Dyn and Content variables
     *
     * @param string $templateId
     * @param array $variables
     *  variable contains two supported types (EMV_DYN and EMV_CONTENT)
     * @param string $part
     * @param string $closeApi
     * @return boolean
     */
    public function getTemplatePreviewWithDynContent($templateId, $variables = array(), $part='HTML', $closeApi = true)
    {
        $result = false;

        try {
            $params = array(
                'token' => $this->openApiConnection(),
                'templatePerso' => array(
                    'contentItemList' => array(),
                    'persoItemList'   => array(),
                    'templateId'      => $templateId
                ),
                'part'  => $part
            );

            // prepare Dyn variables
            if (isset($variables['EMV_DYN'])) {
                foreach ($variables['EMV_DYN'] as $key => $value) {
                    $params['templatePerso']['persoItemList']['persoItem'][] = array(
                        'label' => $key,
                        'value' => $value
                    );
                }
            }
            if (isset($variables['EMV_CONTENT'])) {
                foreach ($variables['EMV_CONTENT'] as $key => $value) {
                    $params['templatePerso']['contentItemList']['contentItem'][] = array(
                        'label' => $key,
                        'value' => $value
                    );
                }
            }

            // use getTemplatesByPeriod method
            $wsResult = $this->soapCall('getTemplatePreviewByObj', $params);
            if (property_exists($wsResult, 'return')) {
                $result = $wsResult->return;
            }
        }  catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $result;
    }
}