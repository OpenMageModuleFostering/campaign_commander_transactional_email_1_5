<?php
/**
 * EmailVision Soap Client
 *
 * @category    EmailVision
 * @package     EmailVision
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 * @copyright  Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class EmailVision_SoapClient extends SoapClient
{
    /**
     * Overriding __doRequest :
     *  - To replace known invalid xml characters.
     *  - To support Mtom supporting server (extract a unique soap envelop from a mutlipart
     *    response) Carefull! This method won't work with multiple soap envelops returned in a unique response.
     *
     * @see http://www.w3.org/TR/xml/#charsets
     * @return string
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
        $response = parent::__doRequest($request, $location, $action, $version);

        // Only replace unicode characters if PCRE version is less than 8.30
        if (version_compare(strstr(constant('PCRE_VERSION'), ' ', true), '8.30', '<')) {
            $response = preg_replace('/[\x{0}-\x{8}\x{B}-\x{C}\x{E}-\x{1F}\x{D800}-\x{DFFF}]/u', '', $response);
        }

        $start = strpos($response, '<soap');
        $end = strrpos($response, '>');
        $response = substr($response, $start, $end-$start+1);

        return $response;
    }
}