<?php
namespace NitroPack\SDK\Api;

class ExcludedUrls extends SignedBase
{
    protected $secret;

    public function __construct($siteId, $siteSecret)
    {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function enable()
    {
        $path = 'excludedurls/enable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while enabling Excluded Urls: %s');
        }
    }

    public function disable()
    {
        $path = 'excludedurls/disable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while disabling Excluded Urls: %s');
        }
    }

    public function add($urlPattern)
    {
        $path = 'excludedurls/add/' . $this->siteId;

        $post = ['url_pattern' => $urlPattern];
        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while adding exclusion: %s');
        }
    }

    public function remove($urlPattern)
    {
        $path = 'excludedurls/remove/' . $this->siteId;

        $post = ['url_pattern' => $urlPattern];
        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while removing exclusion: %s');
        }
    }
}