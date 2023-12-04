<?php

namespace NitroPack\SDK\Api;

use \NitroPack\HttpClient\Exceptions\SocketReadTimedOutException;

class Tagger extends SignedBase {

    protected $secret;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function tag($url, $tag) {
        $path = 'tags/add/' . $this->siteId;
        $params = array(
            'url' => $url,
            'tag' => is_array($tag) ? implode(',', $tag) : $tag
        );

        try {
            $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $params);

            $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
            if ($status !== 200) {
                $this->throwException($httpResponse, 'Error while tagging a url: %s');
            }
            $body = $httpResponse->getBody();
            $response = new Response($status, $body);
        } catch (SocketReadTimedOutException $e) {
            $response = new Response(200, "");
        }
        return $response;
    }

    public function remove($url, $tag) {
        $path = 'tags/remove/' . $this->siteId;
        $params = array('tag' => $tag);
        if ($url) {
            $params['url'] = $url;
        }

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $params);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        if ($status !== 200) {
            $this->throwException($httpResponse, 'Error while removing a tag from a url: %s');
        }
        $body = $httpResponse->getBody();
        $response = new Response($status, $body);
        return $response;
    }

    public function getTags($url, $page = 1, $limit = 250) {
        $path = 'tags/get/' . $this->siteId . '/' . $page . '/' . $limit . '?url=' . urlencode($url);

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        case ResponseStatus::NOT_FOUND:
            return array();
        default:
            $this->throwException($httpResponse, 'Error while getting the tags for a url: %s');
        }
    }

    public function getUrls($tag, $page = 1, $limit = 250) {
        $path = 'tags/geturls/' . $this->siteId . '/' . $tag . '/' . $page . '/' . $limit;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        case ResponseStatus::NOT_FOUND:
            return array();
        default:
            $this->throwException($httpResponse, 'Error while getting tagged urls: %s');
        }
    }

    public function getUrlsCount($tag) {
        $path = 'tags/geturlscount/' . $this->siteId . '/' . $tag;

        $httpResponse = $this->makeRequest($path, array(), array(), 'GET');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
        case ResponseStatus::OK:
            return json_decode($httpResponse->getBody(), true);
        case ResponseStatus::NOT_FOUND:
            return array();
        default:
            $this->throwException($httpResponse, 'Error while getting tagged urls count: %s');
        }
    }

}