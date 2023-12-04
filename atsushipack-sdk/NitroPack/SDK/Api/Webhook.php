<?php
namespace NitroPack\SDK\Api;

class Webhook extends SignedBase {
    protected $secret;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function set($type, $url) {
        $path = 'webhook/set/' . $this->siteId;

        if (!empty($url)) {
            // Set a webhook
            $post = array(
                'type' => $type,
                'url' => $url
            );
        } else {
            // Unset a webhook
            $post = array(
                'type' => $type
            );
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while setting the webhook: %s');
        }
    }

    public function get($type) {
        $path = 'webhook/get/' . $this->siteId . '/' . $type;

        $httpResponse = $this->makeRequest($path);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while getting the webhook: %s');
        }
    }
}
