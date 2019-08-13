<?php
/**
 * Member Service Api - Data member updates
 *
 * @category    EmailVision
 * @package     EmailVision_Api
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 * @copyright Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 * @license http://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */
class EmailVision_Api_MemberService extends EmailVision_Api_Common
{
    const LIMIT_CALL_PER_DAY = 500;
    const LIMIT_CALL_REJOIN_PER_DAY = 50;

    const SEPARATOR_FIELD_CONDITION_MEMBER_UID = ':';
    const SEPARATOR_MEMBER_UID_FIELD = '|';

    /**
     * Describe the member table in SmartFocus platform
     *
     * @param booelan $closeApi - indicate if need to close api after query
     * @return array - contains all the table field (each field is charactirised by name and type)
     */
    public function descMemberTable($closeApi = true)
    {
        $listFields = array();
        try {
            $params = array(
                'token' => $this->openApiConnection(),
            );

            $wsResult = $this->soapCall('descMemberTable', $params);
            if (
                property_exists($wsResult, 'return')
                && property_exists($wsResult->return, 'fields')
                && is_array($wsResult->return->fields)
            ) {
                 foreach ($wsResult->return->fields as $field) {
                     $listFields[] = array(
                         'name' => $field->name,
                         'type' => $field->type
                     );
                 }
            }
        } catch (Exception $e) {
            $this->decorateException($e);
            $this->throwEmailVisionException($e);
        }

        if ($closeApi) {
            $this->closeApiConnection();
        }

        return $listFields;
    }

    /**
     * Prepare memberUID field for query
     *
     * @param array $listCriteria - associative array with :
     *   - key => the field for which the condition is verified
     *   - value => the condition
     * @return string
     */
    public function prepareMemberUidForWebservice($listCriteria = array())
    {
        // create member uid
        $memberUid = array();
        foreach ($listCriteria as $field => $condition) {
            // all fields need to be in UPPER CASE
            $memberUid[] = strtoupper($field) . self::SEPARATOR_FIELD_CONDITION_MEMBER_UID . $condition;
        }
        return implode(self::SEPARATOR_MEMBER_UID_FIELD, $memberUid);
    }

    /**
     * Get list of members (50 members at maximum are allowed to be returned)
     * that match the given criteria
     *  - list of criteria needs to be associative array.
     *  Example : 'email' => 'test@sample.com', 'firstname' => 'john'...
     *
     * @param array $listCriteria
     * @param string $closeApi
     * @return array
     */
    public function getListMembersByObj($listCriteria = array(), $closeApi = true)
    {
        $listMembers = array();

        $memberUid = $this->prepareMemberUidForWebservice($listCriteria);
        if ($memberUid) {
            try {
                $params = array(
                    'token' => $this->openApiConnection(),
                    'member' => array(
                        'dynContent' => '',
                        'memberUID'  => $memberUid
                    )
                );

                $wsResult = $this->soapCall('getListMembersByObj', $params);
                $tmpList = array();
                if (property_exists($wsResult, 'return')) {
                    // the return can be an array or an object
                    $tmpList = $wsResult->return;
                    if (!is_array($wsResult->return)) {
                        $tmpList = array($wsResult->return);
                    }

                    foreach($tmpList as $member) {
                        if (
                            property_exists($member, 'attributes')
                            && property_exists($member->attributes, 'entry')
                            && is_array($member->attributes->entry)
                        ) {
                            $memberFields = array();
                            foreach ($member->attributes->entry as $field) {
                                if (isset($field->key)) {
                                    $memberFields[$field->key] = (isset($field->value))
                                        ? $field->value : '';
                                }
                            }
                            if (count($memberFields)) {
                                $listMembers[] = $memberFields;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $this->decorateException($e);
                $this->throwEmailVisionException($e);
            }

            if ($closeApi) {
                $this->closeApiConnection();
            }
        }

        return $listMembers;
    }

    /**
     * Re-join members that match the given condition.
     *  - field can be email or SmartFocus member id
     *  - example :
     *     1. field = 'email' , condition = 'test@sample.com'
     *     2. field = 'id' , condtion = 12345678
     *  EmailVision imposes a limit of 50 calls per day
     *
     * @param string $field
     * @param string $condition
     * @param boolean $closeApi
     * @return boolean | string - upload id
     */
    public function rejoinMember($field, $condition, $closeApi = true)
    {
        $result = false;
        try {
            $params = array(
                'token' => $this->openApiConnection()
            );

            $methodToCall = '';
            $webserviceField = '';
            switch($field)
            {
                case 'email' :
                    $methodToCall = 'rejoinMemberByEmail';
                    $webserviceField = 'email';
                    break;
                default :
                    $methodToCall = 'rejoinMemberById';
                    $webserviceField = 'memberId';
                    break;
            }
            $params[$webserviceField] = $condition;

            $wsResult = $this->soapCall($methodToCall, $params);
            if (property_exists($wsResult, 'return')) {
                $result = $wsResult->return;
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
     * Unjoin members that match the given condition
     *  - field can be : email, object (the only way to supply a multiple of conditions), or SmartFocus member's id
     *  - Example :
     *      1. field = 'email' , condition = 'test@samples.com'
     *      2. field = 'object', condition = array(
     *          'email' => 'test@samples.com',
     *          'firstname' => 'john'
     *          'member_id' => 1234567 // SmartFocus member's id
     *      )
     *      3. field = 'id' , condition = 1234567
     *
     * @param string $field
     * @param string $condition
     * @param boolean $closeApi
     * @return boolean
     */
    public function unjoinMember($field, $condition, $closeApi = true)
    {
        $result = false;
        try {
            $params = array(
                'token' => $this->openApiConnection()
            );

            $methodToCall = '';
            $webserviceField = '';
            switch($field)
            {
                case 'email' :
                    $methodToCall = 'unjoinMemberByEmail';
                    $webserviceField = 'email';
                    break;

                case 'object' :
                    $methodToCall = 'unjoinMemberByObj';
                    $webserviceField = 'member';

                    // the condition for method unjoinMemberByObj is totally different from another ones
                    if (!is_array($condition)) {
                        $condition = array();
                    }
                    $condition = $this->prepareMemberUidForWebservice($condition);
                    if ($condition) {
                        $condition = array(
                            'dynContent' => '',
                            'memberUID'  => $condition
                        );
                    }
                    break;

                default :
                    $methodToCall = 'unjoinMemberById';
                    $webserviceField = 'memberId';
                    break;
            }

            // only call the webservice if condition is not empty
            if ($condition) {
                $params[$webserviceField] = $condition;
                $wsResult = $this->soapCall($methodToCall, $params);
                if (property_exists($wsResult, 'return')) {
                    $result = true;
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
     * Prepare DynContent Array for api calls
     *
     * @param array $param
     * @return array
     */
    public function prepareDynContentArray(array $param)
    {
        $preparedArray = array();
        foreach($param as $key => $value) {
            $preparedArray['entry'][] = array('key' => $key, 'value' => $value);
        }
        return $preparedArray;
    }

    /**
     * @param array $listCriteria - criteria list for searching members
     * @param array $param - list of fields and the value to update
     * @param boolean $closeApi
     */
    public function insertOrUpdateMemberByObj(array $listCriteria, $param = array(), $closeApi = true)
    {
        $memberUid  = $this->prepareMemberUidForWebservice($listCriteria);
        $dynContent = $this->prepareDynContentArray($param);
        $uploadId   = false;

        if ($memberUid) {
            try {
                $params = array(
                    'token' => $this->openApiConnection(),
                    'member' => array(
                        'dynContent' => $dynContent,
                        'memberUID'  => $memberUid
                    )
                );

                $wsResult = $this->soapCall('insertOrUpdateMemberByObj', $params);
                if (property_exists($wsResult, 'return')) {
                    $uploadId = $wsResult->return;
                }
            } catch (Exception $e) {
                $this->decorateException($e);
                $this->throwEmailVisionException($e);
            }

            if ($closeApi) {
                $this->closeApiConnection();
            }
        }

        return $uploadId;
    }

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
            if (stripos($messageException, 'the maximum number of rejoins') !== false) {
                $e->setCode(EmailVision_Api_Exception::EXCEED_REJOIN_MEMBER_LIMIT);
            }

            if ($message) {
                $message .= ' - Details from server return : ' . $messageException;
                $e->setMessage($message);
            }
        }
    }
}