<?php
namespace NitroPack\SDK\Api;

class Warmup extends SignedBase {
    public function enable() {
        $path = 'warmup/enable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while enabling cache warmup: %s');
        }
    }

    public function disable() {
        $path = 'warmup/disable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while disabling cache warmup: %s');
        }
    }

    public function reset() {
        $path = 'warmup/reset/' . $this->siteId;

        $httpResponse = $this->makeRequest($path);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while resetting cache warmup: %s');
        }
    }

    public function stats() {
        $path = 'warmup/stats/' . $this->siteId;

        $httpResponse = $this->makeRequest($path);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while fetching cache warmup stats: %s');
        }
    }

    public function setSitemap($url = NULL) {
        $path = 'warmup/setsitemap/' . $this->siteId;

        if (!empty($url)) {
            // Set sitemap URL
            $post = array(
                'url' => $url
            );
        } else {
            // Unset sitemap URL
            $post = array();
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while setting sitemap URL: %s');
        }
    }

    public function setHomepage($url = NULL) {
        $path = 'warmup/sethomepageurl/' . $this->siteId;

        if (!empty($url)) {
            // Set sitemap URL
            $post = array(
                'url' => $url
            );
        } else {
            // Unset sitemap URL
            $post = array();
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while setting sitemap URL: %s');
        }
    }

    public function estimate($id = NULL, $urls = NULL) {
        $path = 'warmup/estimate/' . $this->siteId;

        if ($id) {
            $path .= '/' . $id;
        }

        $post = array();
        if (!empty($urls)) {
            $post = array(
                'urls' => is_array($urls) ? $urls : [$urls]
            );
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        default:
            $this->throwException($httpResponse, 'Error while setting sitemap URL: %s');
        }
    }

    public function run($urls = NULL, $force = false) {
        $path = 'warmup/run/' . $this->siteId;

        $post = array();
        if (!empty($urls)) {
            $post['urls'] = is_array($urls) ? $urls : [$urls];
        }

        if ($force) {
            $post['force'] = 1;
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return true;
        default:
            $this->throwException($httpResponse, 'Error while setting sitemap URL: %s');
        }
    }
}
