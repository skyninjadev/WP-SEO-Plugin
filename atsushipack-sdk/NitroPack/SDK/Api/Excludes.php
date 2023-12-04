<?php
namespace NitroPack\SDK\Api;

use \NitroPack\SDK\ExcludeEntry;

class Excludes extends SignedBase
{
    protected $secret;

    public function __construct($siteId, $siteSecret)
    {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function enable()
    {
        $path = 'excludes/enable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while enabling Excludes: %s');
        }
    }

    public function disable()
    {
        $path = 'excludes/disable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while disabling Excludes: %s');
        }
    }

    public function get()
    {
        $path = 'excludes/get/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                $entries = json_decode($httpResponse->getBody(), true);
                return array_map(function($el) {
                    $entry = new ExcludeEntry();
                    foreach ($el as $prop => $val) {
                        $entry->$prop = $val;
                    }
                    return $entry;
                }, $entries);
            default:
                $this->throwException($httpResponse, 'Error while getting Excludes: %s');
        }
    }

    public function set($excludes)
    {
        $path = 'excludes/set/' . $this->siteId;

        
        $excludes = json_decode(json_encode($excludes), true);
        $excludes = array_map(function($el) {
            // Ugly but needed for the signature to match
            foreach ($el as &$val) {
                if ($val === NULL) {
                    $val = "";
                }
            }

            foreach ($el["operation"] as &$val) {
                $val = (string)(int)$val;
            }
            return $el;
        }, $excludes);

        $post = !empty($excludes) ? ['excludes' => $excludes] : [];

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while setting Excludes: %s');
        }
    }
}
