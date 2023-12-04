<?php
namespace NitroPack\SDK\Api;

class SecureRequestMaker extends SignedBase {
    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId, $siteSecret);
    }

    public function makeRequest($path, $headers = array(), $cookies = array(), $type = 'GET', $bodyData=array(), $async = false, $verifySSL = false) {
        return parent::makeRequest($path, $headers, $cookies, $type, $bodyData, $async, $verifySSL);
    }
}
