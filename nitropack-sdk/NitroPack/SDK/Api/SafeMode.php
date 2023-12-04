<?php
namespace NitroPack\SDK\Api;

class SafeMode extends SignedBase {
    public function status() {
        $path = 'safemode/status/' . $this->siteId;

        $httpResponse = $this->makeRequest($path);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody());
        default:
            $this->throwException($httpResponse, 'Error while enabling safe mode: %s');
        }
    }

    public function enable() {
        $path = 'safemode/enable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while enabling safe mode: %s');
        }
    }

    public function disable() {
        $path = 'safemode/disable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while disabling safe mode: %s');
        }
    }
}
