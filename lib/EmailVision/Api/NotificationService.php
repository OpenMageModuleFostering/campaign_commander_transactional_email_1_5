<?php
/**
 * Notification (Transactional Message Trigger) Api
 *
 * @category    EmailVision
 * @package     EmailVision_Api
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 * @copyright Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class EmailVision_Api_NotificationService extends EmailVision_Api_Common
{
    /**
     * Default REST service url
     */
    const REST_SERVICE_URL = 'https://api.notificationmessaging.com/NMSXML';

    /**
     * Rest service url
     *
     * @var string
     */
    protected $_restServiceUrl = null;

    /**
     * HTTP response status codes for REST service
     */
    const REST_STATUS_OK = 200;
    const NOT_FOUND_SERVICE = 404;
    const INTERNAL_SERVER_ERROR = 500;

    /**
     * Curl status code
     */
    const CURLE_COULDNT_RESOLVE_PROXY = 5;
    const CURLE_COULDNT_RESOLVE_HOST = 6;
    const CURLE_COULDNT_CONNECT = 7;
    const CURLE_OPERATION_TIMEDOUT = 28;

    /**
     * Connection time out for rest service - The number of seconds to wait while trying to connect.
     */
    const REST_CONNECTION_TIME = 1;

    /**
     * Time out for rest service - The maximum number of seconds to allow cURL functions to execute.
     */
    const REST_TIME_OUT = 2;

    /**
     * Convert an array of mapping into an array understandable by the webservice method sendObject
     *
     * @param array $attributes
     * @return array
     */
    public function arrayToWebServiceRequest(array $attributes)
    {
        $wsArray = array();
        foreach($attributes as $key => $value)
        {
            $wsArray[] = array('key' => $key, 'value' => $value);
        }

        return array('entry' => $wsArray);
    }

    /**
     * Set rest service url
     *
     * @param string $url
     * @return EmailVision_Api_NotificationService
     */
    public function setRestServiceUrl($url)
    {
        $this->_restServiceUrl = $url;
        return $this;
    }

    /**
     * Get rest service url
     *
     * @return string
     */
    public function getRestServiceUrl()
    {
        if ($this->_restServiceUrl == null) {
            $this->_restServiceUrl = self::REST_SERVICE_URL;
        }
        return $this->_restServiceUrl;
    }

    /**
     * Send email by EMV webservice
     *
     * @param string $encrypt - Security tag
     * @param string $notificationId
     * @param string $random - Unique Random Tag
     * @param string $email
     * @param array $content - need to be assiociative array - key => value
     * @param array $variable - need to be assiociative array - key => value
     * @param string $sendDate
     * @return string
     * @throws EmailVision_Api_Exception
     */
    public function sendTemplate(
        $encrypt, $notificationId, $random,
        $email, $content = array(), $variable = array(),
        $sendDate = '1980-01-01T00:00:00'
    )
    {
        $returnString = '';
        try {
            $returnString = '';
            //send mail
            $params = array(
                'arg0' => array(
                    'content' => $this->arrayToWebServiceRequest($content),
                    'dyn'     => $this->arrayToWebServiceRequest($variable),
                    'email'   => $email,
                    'encrypt' => $encrypt,
                    'notificationId' => $notificationId,
                    'random'         => $random,
                    'senddate'       => $sendDate,
                    'synchrotype'    => 'NOTHING',
                    'uidkey'         => 'EMAIL',
                )
            );

            // use method sendObject of the webservice
            $wsResult = $this->soapCall('sendObject', $params);
            if (isset($wsResult->return)) {
                $returnString .= $email . ' : ' . $wsResult->return;
            }

        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        return $returnString;
    }

    /**
     * Decorate exception with a new eventual message and eventual error code
     * @param Exception $e
     */
    public function decorateException(Exception $e)
    {
        parent::decorateException($e);

        if ($e instanceof EmailVision_Api_Exception) {
            $messageException = $e->getMessage();
            $message = '';

            if (stripos($messageException, 'CREATE_SENDREQUEST_FAILED') !== false) {
                $message = 'Error with sending parameters (random, encrypt, or email address) !';
                $e->setCode(EmailVision_Api_Exception::INVALID_EMAIL_SENDING_PARAMETERS);
            }

            if ($message) {
                $message .= ' - Details from server return : ' . $messageException;
                $e->setMessage($message);
            }
        }
    }

    /**
     * Send email by Rest service
     *
     * @param string $encrypt
     * @param string $notificationId
     * @param string $random
     * @param string $mail
     * @param array $content - need to be assiociative array - key => value
     * @param array $variable - need to be assiociative array - key => value
     * @param string $sendDate
     * @param string $proxyHost - proxy host
     * @param string $proxyPort - proxy port
     * @return boolean
     * @throws EmailVision_Api_Exception
     */
    public function sendRest(
        $encrypt, $notificationId, $random,
        $mail, $content = array(), $variable = array(),
        $sendDate = '2013-01-01T00:00:00',
        $proxyHost = null, $proxyPort = null
    )
    {
        // prepare dynamic variable
        $dyn = '<dyn>';
        foreach ($variable as $key => $value) {
            $dyn .= '<entry>';
            $dyn .= '<key><![CDATA[' . $key . ']]></key>';
            $dyn .= '<value><![CDATA[' . $value . ']]></value>';
            $dyn .= '</entry>';
        }
        $dyn .= '</dyn>';

        // prepare dynamic content
        $dynContent = '<content>';
        // prepare Data
        foreach ($content as $key => $value) {
            $dynContent .= '<entry>';
            $dynContent .= '<key><![CDATA[' . $key . ']]></key>';
            $dynContent .= '<value><![CDATA[' . $value . ']]></value>';
            $dynContent .= '</entry>';
        }
        $dynContent .= '</content>';

        // template id
        $encrypt = '<encrypt><![CDATA[' . $encrypt . ']]></encrypt>';
        $random  = '<random><![CDATA[' . $random . ']]></random>';
        $sendDate = '<senddate><![CDATA[' . $sendDate . ']]></senddate>';
        $mail = '<email><![CDATA[' . $mail . ']]></email>';

        // prepare raw data for sending
        $rawData = '<MultiSendRequest><sendrequest>';
        $rawData .= $dyn . $dynContent . $mail . $encrypt . $random . $sendDate;
        // refer to EmailVision documentation, do not do anything synchronization
        $rawData .= '
            <synchrotype><![CDATA[NOTHING]]></synchrotype>
            <uidkey></uidkey>
        ';
        $rawData .= '</sendrequest></MultiSendRequest>';

        $reason = '';
        $reasonCode = null;
        $httpCode = null;
        try {
            $ch = curl_init();

            // set service url
            curl_setopt($ch, CURLOPT_URL, $this->getRestServiceUrl());

            // set option to have status code and response from server
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_VERBOSE , 1 );
            // set header to http request
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type"=>"application/xml; charset=UTF-8",
                "Connection"=>"Keep-Alive",
                "Accept-Encoding"=>"*",
            ));

            // timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::REST_CONNECTION_TIME);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::REST_TIME_OUT);

            // send by post
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);

            if ($proxyHost) {
                $preparedProxy = $proxyHost;
                // set proxy information if proxy is set
                if ($proxyPort) {
                    $preparedProxy .= ':' . $proxyPort;
                }
                curl_setopt($ch, CURLOPT_PROXY, $preparedProxy);
            }

            // send data to webservice and get sever response
            // the server response will be returned by curl_exec
            $reason = curl_exec($ch);
            if ($reason === false) {
                // in case of error, try to get error number
                $reasonCode = curl_errno($ch);
            } else {
                // get httpd code
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }

            // close connection
            curl_close($ch);
        } catch(Exception $e) {
            $this->throwEmailVisionException($e);
        }

        // in case of error, the status should be 200
        if ($httpCode != self::REST_STATUS_OK) {
            $code = EmailVision_Api_Exception::APPLICATION_ERROR;

            // treat reason code when we don't have a response from server
            if ($reasonCode) {
                switch ($reasonCode) {
                    case self::CURLE_COULDNT_RESOLVE_HOST:
                    case self::CURLE_COULDNT_RESOLVE_PROXY:
                    case self::CURLE_OPERATION_TIMEDOUT :
                    case self::CURLE_COULDNT_CONNECT :
                        $code = EmailVision_Api_Exception::CONNECT_ERROR;
                        break;
                }
            } elseif ($httpCode) {
                // treat http code if defined
                switch ($httpCode) {
                    case self::NOT_FOUND_SERVICE :
                        $code = EmailVision_Api_Exception::CONNECT_ERROR;
                        break;
                    case self::INTERNAL_SERVER_ERROR :
                        if (
                            $reason
                            && (
                                stripos($reason,'Random') !== false
                                || stripos($reason,'encrypt') !== false
                                || stripos($reason,'email') !== false
                            )
                        ) {
                            $code = EmailVision_Api_Exception::INVALID_EMAIL_SENDING_PARAMETERS;
                            // remove the response received from SmartFocus
                            $reason = false;
                        }
                        break;
                }
            }

            if (!$reason) {
                $reason = 'Unknown error !';
                switch ($code) {
                    case EmailVision_Api_Exception::CONNECT_ERROR :
                        $reason = 'Could not connect to Notification service. Please check your network setting, and service url!';
                        break;
                    case EmailVision_Api_Exception::INVALID_EMAIL_SENDING_PARAMETERS :
                        $reason = 'Your sending parameters are invalid. Please verify them again !';
                        break;
                    case EmailVision_Api_Exception::APPLICATION_ERROR :
                        $reason = 'Error occured at SmartFocus server';
                        break;
                }
            }

            $message = array(
                'Error while sending content to SmartFocus api - reason : ' . $reason,
            );
            if ($httpCode) {
                $message[] = 'HTTP status code : ' . $httpCode;
            }
            $message = implode("\n", $message);

            $exception = new EmailVision_Api_Exception($message, $code);
            $this->throwEmailVisionException($exception);
        } // end error treatment

        return true;
    }
}