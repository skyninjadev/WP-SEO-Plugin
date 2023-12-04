<?php
namespace NitroPack\SDK\Api;
use \NitroPack\Url\Url;

// Src: http://php.net/manual/en/function.hash-equals.php
if(!function_exists('hash_equals')) {
    function hash_equals($str1, $str2) {
        if(strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
            return !$ret;
        }
    }
}

class SignedBase extends Base {

    private static $signatureHeader = 'X-Nitro-Signature';
    private static $algorithm = 'sha512';

    protected $secret;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId);
        $this->secret = $siteSecret;
    }

    protected function addToBacklog($entry) {
        $entry["siteSecret"] = $this->secret;
        parent::addToBacklog($entry);
    }

    protected function makeRequest($path, $headers = array(), $cookies = array(), $type = 'GET', $bodyData=array(), $async = false, $verifySSL = false) {
        // calculate request signature
        $url = new Url($this->baseUrl . $path);
        $noParamsPath = $url->getPath();
        $params = array();
        if(!is_null($url->getQuery())) {
            parse_str($url->getQuery(), $params);
        }
        $params = $params + $bodyData;
        $headers[SignedBase::$signatureHeader] = static::calculateRequestSignature($noParamsPath, $params, static::getSignatureHeaders($headers), $this->secret);

        // make the request
        $http = parent::makeRequest($path, $headers, $cookies, $type, $bodyData, $async, $verifySSL);

        if ($async) {
            $http->setOnCompleteCallback(array($this, 'verifySignature'));
        } else {
            $this->verifySignature($http);
        }

        return $http;
    }

    protected function verifySignature($http) {
        if ($http->getStatusCode() === ResponseStatus::OK) {
            $responseHeaders = $http->getHeaders();
            if (!isset($responseHeaders[strtolower(SignedBase::$signatureHeader)])) {
                throw new \RuntimeException('Server did not include a signature for a request that was expected to be signed');
            }
            $signature = static::calculateResponseSignature($http->getBody(), static::getSignatureHeaders($responseHeaders), $this->secret);
            if (!hash_equals($signature, $responseHeaders[strtolower(SignedBase::$signatureHeader)])) { // TODO move RemoteConfigFetcher::internal_hash_equals to a place where its accessible everywhere and use it here
                throw new \RuntimeException('Server signature is wrong'); // TODO better message
            }
        }
    }

    /** Signature data:
     * UrlPath|header1:value,header2:value|param1:value,param2:value
     * if not using headers, we still expect their delimiter UrlPath||param1:value,param2:value
     */
    protected static function calculateRequestSignature($urlPath, $parameters, $headers, $secret) {
        // do not include the header that specifies the signature
        if (isset($headers[SignedBase::$signatureHeader])) {
            unset($headers[SignedBase::$signatureHeader]);
        }

        $data = $urlPath;

        ksort($headers);
        $headersTemp = [];
        foreach ($headers as $header => $value) {
            $headersTemp[] = str_replace('-', '_', strtolower($header)) . ':' . $value;
        }

        $data .= '|' . implode(',', $headersTemp);
        $headersTemp = null;

        ksort($parameters);
        $paramsTemp = [];
        foreach ($parameters as $param => $value) {
            if (is_array($value)) {
                $paramsTemp[] = $param . ':' . json_encode($value);
            } else {
                $paramsTemp[] = $param . ':' . $value;
            }
        }

        $data .= '|' . implode(',', $paramsTemp);
        $paramsTemp = null;
        return SignedBase::hmac($data, $secret);
    }

    protected static function getSignatureHeaders($headers) {
        $signatureHeaders = [];
        foreach ($headers as $header => $value) {
            if (stripos($header, 'x-nitro-') !== false && strtolower($header) != strtolower(SignedBase::$signatureHeader)) {
                $signatureHeaders[$header] = $value;
            }
        }

        return $signatureHeaders;
    }

    protected static function calculateResponseSignature($data, $headers, $secret) {
        ksort($headers);
        $headersTemp = [];
        foreach ($headers as $header => $value) {
            $headersTemp[] = str_replace('-', '_', strtolower($header)) . ':' . $value;
        }

        $data = implode(',', $headersTemp) . '|' . $data;
        $headersTemp = null;

        return SignedBase::hmac($data, $secret);
    }

    private static function hmac($data, $secret) {
        return hash_hmac(SignedBase::$algorithm, $data, $secret);
    }

}