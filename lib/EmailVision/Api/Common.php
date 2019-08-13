<?php
/**
 * Base Class for All APIs
 *
 * @category    EmailVision
 * @package     EmailVision_Api
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 * @copyright Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class EmailVision_Api_Common
{
    /**
     * @var string
     */
    protected $_sessionId;

    /**
     * Stored last request method
     *
     * @var string
     */
    protected $_lastRequestMethod;

    /**
     * Stored last request data
     *
     * @var array
     */
    protected $_lastRequestData;

    /**
     * Api credential (login, pwd, key)
     * @var array
     */
    protected $_apiCredentials = array();

    /**
     * Soap Client
     *
     * @var EmailVision_SoapClient
     */
    protected $_soapClient;

    /**
     * Retry limit for webservice call
     */
    const RETRY_LIMIT = 2;

    /**
     * Default Soap Client Class
     */
    const SOAP_CLIENT_CLASS = 'EmailVision_SoapClient';

    /**
     * Waiting time before every new webservice call
     */
    const WAITING_TIME = 2;

    /**
     * Webservice connection time out
     */
    const CONNECTION_TIME_OUT = 2;

    /**
     * Default options for API
     *
     * @var array
     */
    protected $_options = array(
        'soap_client'     => self::SOAP_CLIENT_CLASS,
        'retry_limit'     => self::RETRY_LIMIT,
        'debug'           => false,

        // SoapClient parameters
        'wsdl'               => '',
        'location'           => '',
        'proxy_host'         => null,
        'proxy_port'         => null,
        'soap_version'       => SOAP_1_1,
        'compression'        => true,
        'encoding'           => 'UTF-8',
        'trace'              => false,
        'exceptions'         => true,
        'cache_wsdl'         => WSDL_CACHE_BOTH,
        'user_agent'         => 'EmailVision API',
        'connection_timeout' => self::CONNECTION_TIME_OUT,
    );

    public function __construct(array $options = array()) {
        $this->_checkRequirements();
        $this->setOptions($options);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->closeApiConnection();
        $this->_soapClient = null;
    }

    /**
     * @param array $options
     * @return EmailVision_Api_Abstract
     */
    protected function setOptions(array $options = array())
    {
        foreach ($options as $name => $value) {
            $this->_setOption($name, $value);
        }
        return $this;
    }

    /**
     * Check all requirements are valid to make a webservice call
     *
     * @return EmailVision_Api_Abstract
     * @throws EmailVision_Api_Exception $e - in case of error
     */
    protected function _checkRequirements()
    {
        if (!extension_loaded('soap')) {
            $this->throwEmailVisionException('SOAP extension is not loaded.');
        }

        if (!extension_loaded('openssl')) {
            $this->throwEmailVisionException('OpenSSL extension is not loaded.');
        }

        return $this;
    }

    /**
     * @param bool $value
     * @return EmailVision_Api_Common
     */
    public function setDebug($value)
    {
        return $this->_setOption('debug', (bool) $value);
    }

    /**
     * @return bool
     */
    public function getDebug()
    {
        return (bool) $this->_options['debug'];
    }

    /**
     * @param string $name
     * @param string $value
     * @return EmailVision_Api_Abstract
     */
    protected function _setOption($name, $value)
    {
        if (array_key_exists($name, $this->_options)) {
            // Some settings need checked
            switch ($name) {
                case 'soap_client':
                    if (!class_exists($value)) {
                        $this->throwEmailVisionException("Unable to load class: {$value} as SoapClient.");
                    }
                    break;
                case 'soap_version':
                    if (!in_array($value, array(SOAP_1_1, SOAP_1_2))) {
                        $this->throwEmailVisionException('Invalid soap_version value specified. Use SOAP_1_1 or SOAP_1_2 constants.');
                    }
                    break;
                case 'cache_wsdl':
                    if (!in_array($value, array(WSDL_CACHE_NONE, WSDL_CACHE_DISK, WSDL_CACHE_MEMORY, WSDL_CACHE_BOTH))) {
                        $this->throwEmailVisionException('Invalid cache_wsdl value specified.');
                    }
                    // If debug mode, ignore WSDL cache setting
                    if ($this->getDebug()) {
                        $value = WSDL_CACHE_NONE;
                    }
                    break;
                case 'debug':
                    if ($value == true) {
                        $this->_options['trace']      = true;
                        $this->_options['cache_wsdl'] = WSDL_CACHE_NONE;
                    }
                    break;
            }

            $this->_options[$name] = $value;
        }

        return $this;
    }

    /**
     * Get soap client - if soap client is null, try to create a new one with default configurations
     *
     * @return EmailVision_SoapClient
     */
    public function getSoapClient()
    {
        // create soap client with default options
        if (is_null($this->_soapClient)) {
            $this->_initSoapClient();
        }

        return $this->_soapClient;
    }

    /**
     * @param SoapClient $soapClient
     * @return EmailVision_Api_Abstract
     */
    public function setSoapClient(SoapClient $soapClient)
    {
        $this->_soapClient = $soapClient;
        return $this;
    }

    /**
     * Initialize soap client with available options
     *
     * @return EmailVision_Api_Abstract
     */
    protected function _initSoapClient()
    {
        // check wsdl or service location is valid
        if (
            (!isset($this->_options['wsdl']) || empty($this->_options['wsdl']))
            && (!isset($this->_options['location']) || empty($this->_options['location']))
        ) {
            $this->throwEmailVisionException('Please provide WSDL emplacement or Server location in options');
        }

        // Retreive client class
        $soapClientClass  = $this->_options['soap_client'];
        // Set compression option for soapclient
        $this->_options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;

        // Create soap client
        $this->_soapClient = new $soapClientClass(
            $this->_options['wsdl'],
            array(
                'soap_version'       => $this->_options['soap_version'],
                'compression'        => $this->_options['compression'],
                'encoding'           => $this->_options['encoding'],
                'trace'              => $this->_options['trace'],
                'exceptions'         => $this->_options['exceptions'],
                'cache_wsdl'         => $this->_options['cache_wsdl'],
                'user_agent'         => $this->_options['user_agent'],
                'connection_timeout' => $this->_options['connection_timeout'],
                'proxy_host'         => $this->_options['proxy_host'],
                'proxy_port'         => $this->_options['proxy_port'],
            )
        );
        // Set soap service location
        $this->_soapClient->__setLocation($this->_options['location']);

        return $this;
    }

    /**
     * Custom exception handling.
     * Last Soap request and response will be added in the exception if debug mode is activated
     *
     * @param string|Exception $exception
     * @param string $message
     * @param string $code
     * @throws EmailVision_Api_Exception
     */
    public function throwEmailVisionException($exception, $message = null, $code = null)
    {
        if ($exception instanceOf Exception) {
            if (! $exception instanceOf EmailVision_Api_Exception) {
                // Convert
                $exception = new EmailVision_Api_Exception(
                    $exception->getMessage(),
                    $exception->getCode(),
                    null,
                    $exception
                );
            }
        } else {
            if (is_string($exception)) {
                if (class_exists($exception, false)) {
                    $exception = new $exception($message, $code);
                } else {
                    $exception = new EmailVision_Api_Exception($exception);
                }
            }
        }

        // For tracking request/response in debug mode
        if ($this->getDebug() && $exception instanceof EmailVision_Api_Exception) {
            /* @var $exception EmailVision_Api_Exception */
            $exception->setRequest($this->getLastRequest());
            $exception->setResponse($this->getLastResponse());
        }

        throw $exception;
    }

    /**
     * Decorate exception with a new eventual error message and a new eventual error code
     * @param Exception $e
     */
    public function decorateException(Exception $e)
    {
        if ($e instanceof EmailVision_Api_Exception) {
            $messageException = $e->getMessage();
            $message = '';
            if ($e->isRecoverable()) {
                $message = 'You have some problems to connect to EmailVision webservices. Please check your network settings or the webservice urls!';
            } else {
                if (stripos($messageException, 'OPEN_CONNECTION_FAILED') !== false) {
                    $message = 'Your API credential is invalid. Please correct it !';
                    // change error code to invalid credential
                    $e->setCode(EmailVision_Api_Exception::INVALID_CREDENTIAL);
                } else if (stripos($messageException, 'Your session has expired due to timeout') !== false) {
                    // change error code to expired security token
                    $e->setCode(EmailVision_Api_Exception::EXPIRED_SECURITY_TOKEN);
                } else {
                    $e->setCode(EmailVision_Api_Exception::APPLICATION_ERROR);
                }
            }

            if ($message) {
                $message .= ' - Details from server return : ' . $messageException;
                $e->setMessage($message);
            }
        }
    }

    /**
     * Retrieve request XML
     *
     * @return string
     */
    public function getLastRequest()
    {
        if ($this->_soapClient !== null) {
            return $this->_soapClient->__getLastRequest();
        }

        return '';
    }

    /**
     * Get response XML
     *
     * @return string
     */
    public function getLastResponse()
    {
        if ($this->_soapClient !== null) {
            return $this->_soapClient->__getLastResponse();
        }

        return '';
    }

    /**
     * Retrieve request headers
     *
     * @return string
     */
    public function getLastRequestHeaders()
    {
        if ($this->_soapClient !== null) {
            return $this->getSoapClient()->__getLastRequestHeaders();
        }

        return '';
    }

    /**
     * Retrieve response headers (as string)
     *
     * @return string
     */
    public function getLastResponseHeaders()
    {
        if ($this->_soapClient !== null) {
            return $this->getSoapClient()->__getLastResponseHeaders();
        }

        return '';
    }

    /**
     * Re-Initialize Api object
     *
     * @return EmailVision_Api_Abstract
     */
    public function reInit()
    {
        $this->_soapClient = null;
        $this->_sessionId  = null;

        return $this;
    }

    /**
     * Set api credentials - need to supply
     *   - login : login
     *   - pwd   : password
     *   - key   : Api key
     *
     * @param array $credentials
     * @return EmailVision_Api_Abstract
     */
    public function setApiCredentials(array $credentials = array())
    {
        $this->_apiCredentials = $credentials;
        return $this;
    }

    /**
     * @return array
     */
    public function getApiCredentials()
    {
        return $this->_apiCredentials;
    }

    /**
     * Verify whether all api credentials are valid
     *
     * @return EmailVision_Api_Abstract
     * @throws EmailVision_Api_Exception $e - in case of invalid api credentials
     */
    protected function _verifyApiCredentials()
    {
        $isOk = false;

        if (
            is_array($this->_apiCredentials)
            && count($this->_apiCredentials) == 3
            && isset($this->_apiCredentials['login'])
            && isset($this->_apiCredentials['pwd'])
            && isset($this->_apiCredentials['key'])
        ) {
            $isOk = true;
        }

        if (!$isOk) {
            $this->throwEmailVisionException('Please provide valid EmailVision Credentials (Login, Passsword, and Api Key)');
        }

        return $this;
    }

    /**
     * @param string $method
     * @param array $data
     * @return EmailVision_Api_Abstract
     */
    protected function _beforeRequest($method, array $data = array())
    {
        // Do nothing;
        return $this;
    }

    /**
     * Perform arguments pre-processing
     *
     * My be overridden in descendant classes
     *
     * @param array $arguments
     */
    protected function _preProcessArguments($arguments)
    {
        // Do nothing
        return $arguments;
    }

    /**
     * Perform result pre-processing
     *
     * My be overridden in descendant classes
     *
     * @param array $arguments
     */
    protected function _preProcessResult($result)
    {
        // Do nothing
        return $result;
    }

    /**
     * @return string
     */
    public function getLastRequestMethod()
    {
        return $this->_lastRequestMethod;
    }

    /**
     * @return array
     */
    public function getLastRequestData()
    {
        return $this->_lastRequestData;
    }

    /**
     * Make a soap call to webservice with a given method, and an eventual array of parameters
     *
     * @param string $method
     * @param array $param
     */
    public function soapCall($method, array $param = array())
    {
        $this->_beforeRequest($method, $param);

        $maxAttemps = (int) $this->_options['retry_limit'];
        $success  = false;
        $result = array();

        for ($tries = 0; $tries <= $maxAttemps && !$success; $tries++) {
            $error = false;

            try {
                // Store this request in case we need to retry later
                $this->_lastRequestMethod = $method;
                $this->_lastRequestData   = $param;

                // Prepare arguments
                $param = $this->_preProcessArguments($param);
                // call method with given parameters
                $result = $this->getSoapClient()->$method($param);
            } catch (Exception $e) {
                $error          = true;
                $exception      = new EmailVision_Api_Exception($e->getMessage(), $e->getCode(), $tries, $e);

                if (!$exception->isRecoverable() || $tries === $maxAttemps) {
                    $this->throwEmailVisionException($exception);
                } else {
                    // Waiting self::WAITING_TIME second before submitting a new request
                    sleep(self::WAITING_TIME);
                }
            }

            if (!$error) {
                $success = true;
            }
        }

        return $this->_preProcessResult($result);
    }

    /**
     * Login action.
     *
     * @return string session id
     * @throws EmailVision_Api_Exception $exception
     */
    public function openApiConnection ()
    {
        if (null != $this->_sessionId) {
            return $this->_sessionId;
        }

        // verify if api credentials are valid
        $this->_verifyApiCredentials();

        try {
            $params = array(
                'login' => $this->_apiCredentials['login'],
                'pwd'   => $this->_apiCredentials['pwd'],
                'key'   => $this->_apiCredentials['key']
            );
            // use openApiConnection method
            $wsResult = $this->soapCall('openApiConnection', $params);

        } catch (Exception $e) {
            // treat problem
            $this->throwEmailVisionException($e);
        }

        if (!isset($wsResult->return)) {
            $this->throwEmailVisionException('Unknown Error. Could not determine token from soap service return');
        }

        $this->_sessionId = $wsResult->return;

        return $this->_sessionId;
    }

    /**
     * Close the Api connection
     *
     * @param string $sessionId
     * @throws EmailVision_Api_Exception $exception
     */
    public function closeApiConnection()
    {
        try {
            if ($this->_sessionId) {
                $this->soapCall('closeApiConnection', array('token' => $this->_sessionId));
                $this->_sessionId = null;
            }
        } catch (Exception $e) {
            $this->throwEmailVisionException($e);
        }

        return $this;
    }
}