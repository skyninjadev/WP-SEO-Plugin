<?php
namespace NitroPack\SDK\Api;

class AdditionalDomains extends SignedBase
{
    protected $secret;

    public function __construct($siteId, $siteSecret)
    {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function enable()
    {
        $path = 'additionaldomains/enable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while enabling additional domains: %s');
        }
    }

    public function disable()
    {
        $path = 'additionaldomains/disable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while enabling additional domains: %s');
        }
    }

    public function add($domain)
    {
        $path = 'additionaldomains/add/' . $this->siteId;

        $post = ['domain' => $domain];
        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while adding additional domain: %s');
        }
    }

    public function remove($domain)
    {
        $path = 'additionaldomains/remove/' . $this->siteId;

        $post = ['domain' => $domain];
        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while removing additional domain: %s');
        }
    }
}