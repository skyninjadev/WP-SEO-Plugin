<?php
namespace NitroPack\SDK\Api;

class RequestMaker extends Base {
    public function __construct($siteId) {
        parent::__construct($siteId);
    }

    public function makeRequest($path, $headers = array(), $cookies = array(), $type = 'GET', $bodyData=array(), $async = false, $verifySSL = false) {
        return parent::makeRequest($path, $headers, $cookies, $type, $bodyData, $async, $verifySSL);
    }
}
