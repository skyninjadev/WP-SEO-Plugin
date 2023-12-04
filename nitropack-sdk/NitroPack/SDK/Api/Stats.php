<?php
namespace NitroPack\SDK\Api;

class Stats extends SignedBase {

    protected $secret;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function getSavings() {
        $path = 'stats/savings/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while getting space savings information: %s');
        }
    }

    public function getDiskUsage() {
        $path = 'stats/diskusage/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while getting disk usage information: %s');
        }
    }

    public function getRequestUsage() {
        $path = 'stats/requestusage/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while getting request usage information: %s');
        }
    }

    public function resetSavings() {
        $path = 'stats/resetsavings/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while resetting stats: %s');
        }
    }

}
