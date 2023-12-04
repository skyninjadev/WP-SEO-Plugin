<?php
namespace NitroPack\SDK\Api;

class VariationCookie extends SignedBase {
    protected $secret;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function set($name, $values, $group) {
        $path = 'variationcookie/set/' . $this->siteId;

        $post = array(
            'name' => $name
        );

        if (!empty($values)) {
            // Set variation cookie values as comma-separated values
            $post['value'] = is_array($values) ? implode(",", $values) : $values;
        }

        if (!empty($group)) {
            $post['group'] = (int)$group;
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while setting the variation cookie: %s');
        }
    }

    public function delete($name) {
        $path = 'variationcookie/delete/' . $this->siteId;

        $post = array(
            'name' => $name
        );

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while unsetting the variation cookie: %s');
        }
    }

    public function get() {
        $path = 'variationcookie/get/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while getting the variation cookie: %s');
        }
    }
}
