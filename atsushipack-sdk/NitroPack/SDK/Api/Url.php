<?php
namespace NitroPack\SDK\Api;

class Url extends SignedBase {

    protected $secret;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function get($page = 1, $limit = 250, $search = NULL, $deviceType = NULL, $optStatus = NULL) {
        $path = 'urls/get/' . $this->siteId . '/' . $page . '/' . $limit;
        $queryParams = array();

        if ($search) {
            $queryParams[] = "search=" . urlencode($search);
        }

        if ($deviceType) {
            $queryParams[] = "deviceType=" . strtolower($deviceType);
        }

        if ($optStatus) {
            $queryParams[] = "status=" . (int)$optStatus;
        }

        if ($queryParams) {
            $path .= "?" . implode("&", $queryParams);
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        case ResponseStatus::NOT_FOUND:
            return array();
        default:
            $this->throwException($httpResponse, 'Error while getting URLs list: %s');
        }
    }

    public function count($search = NULL, $deviceType = NULL, $optStatus = NULL) {
        $path = 'urls/count/' . $this->siteId;
        $queryParams = array();

        if ($search) {
            $queryParams[] = "search=" . urlencode($search);
        }

        if ($deviceType) {
            $queryParams[] = "deviceType=" . strtolower($deviceType);
        }

        if ($optStatus) {
            $queryParams[] = "status=" . (int)$optStatus;
        }

        if ($queryParams) {
            $path .= "?" . implode("&", $queryParams);
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while getting URLs count: %s');
        }
    }

    public function getpending($page = 1, $limit = 250, $priority = NULL) {
        $path = 'urls/getpending/' . $this->siteId . '/' . $page . '/' . $limit;

        if ($priority) {
            $path .= "?priority=" . $priority;
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        case ResponseStatus::NOT_FOUND:
            return array();
        default:
            $this->throwException($httpResponse, 'Error while getting pending URLs list: %s');
        }
    }

    public function pendingCount($priority = NULL) {
        $path = 'urls/pendingcount/' . $this->siteId;

        if ($priority) {
            $path .= "?priority=" . $priority;
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while getting pending URLs count: %s');
        }
    }
}
