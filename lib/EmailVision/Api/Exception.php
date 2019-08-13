<?php
/**
 * EmailVision Api Exception
 *
 * @category    EmailVision
 * @package     EmailVision_Api
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 * @copyright Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class EmailVision_Api_Exception extends Exception
{
    /* Misc */
    const HTTP_HEADER_ERROR      = 98001; // Error Fetching http headers
    const NO_XML_DOCUMENT        = 98002;
    const INVALID_URL            = 98003;
    const CONNECT_ERROR          = 98004;
    const WSDL_PARSE_ERROR       = 98005; // SOAP-ERROR: Parsing WSDL
    const REQUEST_ERROR          = 98006;
    const CONNECTION_RESET       = 98007; // SSL: Connection reset by peer
    const CONNECTION_FAILED      = 98008; // Could not connect to host
    const SERVICE_UNAVAILABLE    = 98009; // Service Temporarily Unavailable
    const SOAPCLIENT_ERROR       = 98010; // SoapClient::__doRequest(): %s
    const SERVER_ERROR           = 98500; // Internal Server Error

    const APPLICATION_ERROR      = 98030;

    /**
     * Login problems
     */
    const INVALID_CREDENTIAL     = 98040;
    const EXPIRED_SECURITY_TOKEN = 98041;

    /**
     * Can't retreive the template
     */
    const INVALID_TEMPLATE_ID  = 98050;

    /**
     * Error with sending parameters (random, encrypt, or email address) !
     */
    const INVALID_EMAIL_SENDING_PARAMETERS = 98060;

    /**
     * Exceed allowed rejoin member call limit
     */
    const EXCEED_REJOIN_MEMBER_LIMIT = 98070;

    /**
     * Error codes relative to batch member calls
     */
    const EXCEED_UPLOAD_LIMIT      = 98081; // you have reached the limit of uploads at the same time
    const EXCEED_UPLOAD_FILE_SIZE  = 98082; // you have reached the limit of upload file size
    const INVALID_PARAMETER_UPLOAD = 98083; // the upload parameters are invalid
    const INVALID_UPLOAD_ID        = 98084; // the upload id is invalid

    /**
     * Array of exceptions we can (maybe) recover from
     * @var array
     */
    protected $_recoverable = array(
        self::HTTP_HEADER_ERROR,
        self::NO_XML_DOCUMENT,
        self::CONNECT_ERROR,
        self::WSDL_PARSE_ERROR,
        self::CONNECTION_RESET,
        self::CONNECTION_FAILED,
        self::SERVICE_UNAVAILABLE,
        self::SERVER_ERROR,
        self::SOAPCLIENT_ERROR,
    );

    /**
     * @var string
     */
    protected $_request;

    /**
     * @var string
     */
    protected $_response;

    /**
     * For PHP <5.3.0
     *
     * @var Exception
     */
    protected $_previous;

    /**
     * @param string $message
     * @param string $code
     * @param int $tries
     * @param Exception $previous
     */
    public function __construct($message = '', $code = 0, $tries = null, Exception $previous = null)
    {
        if (empty($code)) {
            // try to get error code from given message
            $parts = explode(':', $message, 2);
            if (is_array($parts)) {
                $parts = array_map('trim', $parts);
            }
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $code    = (int)    $parts[0];
                $message = (string) $parts[1];
            }
        }

        if (empty($code)) {
            // Handle some SoapFault exceptions
            if (stripos($message, 'Error Fetching http headers') !== false) {
                $code = self::HTTP_HEADER_ERROR;
            } else if (stripos($message, 'looks like we got no XML document') !== false) {
                $code = self::NO_XML_DOCUMENT;
            } else if (stripos($message, 'Could not connect to host') !== false) {
                $code = self::CONNECT_ERROR;
            } else if (stripos($message, 'Parsing WSDL') !== false) {
                $code = self::WSDL_PARSE_ERROR;
            } else if (stripos($message, 'There was an error in your soap request') !== false) {
                $code = self::REQUEST_ERROR;
            } else if (stripos($message, 'Connection reset by peer') !== false) {
                $code = self::CONNECTION_RESET;
            } else if (stripos($message, 'Unable to parse URL') !== false) {
                $code = self::INVALID_URL;
            } else if (stripos($message, 'Service Temporarily Unavailable') !== false) {
                $code = self::SERVICE_UNAVAILABLE;
            } else if (stripos($message, 'Internal Server Error') !== false) {
                $code = self::SERVER_ERROR;
            } else if (stripos($message, 'SoapClient::__doRequest()') !== false) {
                $code = self::SOAPCLIENT_ERROR;
            }
        }

        if (!empty($code)) {
            $message = "{$code} : {$message}";
        }

        if (!empty($tries) && $tries > 1) {
            $message .= " [Tried: {$tries}]";
        }

        // manipulate detail code if not empty
        if (empty($code) && $previous != null && $previous instanceof SoapFault) {
            if (isset($previous->{'detail'})) {
                $detail = $previous->{'detail'};
                $message .= "\n" . ' with detail - ' .  print_r($detail, true);
            }
        }

        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            parent::__construct($message, $code, $previous);
        } else {
            $this->_previous = $previous;
            parent::__construct($message, $code);
        }
    }

    /**
     * Set error code
     *
     * @param int $code
     * @return EmailVision_Api_Exception
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }
    /**
     * Is exception recoverable by verifying its code ?
     *
     * @return bool
     */
    public function isRecoverable()
    {
        if (!$this->getCode()) {
            return false;
        }
        return in_array($this->getCode(), $this->_recoverable);
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param string $text
     */
    public function appendToMessage($text)
    {
        $this->message .= " {$text}";
    }

    /**
     * Set webservice request
     *
     * @param string $request
     * @return EmailVision_Api_Exception
     */
    public function setRequest($request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Get webservice request
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set server response for webservice call
     *
     * @param string $response
     * @return EmailVision_Api_Exception
     */
    public function setResponse($response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Get response from webservice call
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Get previous exception
     *
     * For PHP <5.3.0
     * @return Exception|null
     */
    public function getPreviousException()
    {
        if (method_exists($this, 'getPrevious')) {
            return $this->getPrevious();
        }
        return $this->getPreviousException();
    }

    /**
     * Get function calling trace for debugging
     *
     * @return array
     */
    public function getTraceSafe()
    {
        if (!isset($this->_trace)) {
            $this->_trace = $this->getTrace();
            if (empty($this->_trace)) {
                $backtrace = debug_backtrace();
                $this->_trace = array($backtrace[count($backtrace)-1]);
            }
        }
        return $this->_trace;
    }

    /**
     * @return string
     */
    public function getErrorClass()
    {
        $trace = $this->getTraceSafe();
        return $trace[0]['class'];
    }

    /**
     * @return string
     */
    public function getErrorMethod()
    {
        $trace = $this->getTraceSafe();
        return $trace[0]['function'];
    }
}
