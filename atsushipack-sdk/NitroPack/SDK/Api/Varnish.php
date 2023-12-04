<?php
namespace NitroPack\SDK\Api;

class Varnish extends SignedBase
{
    protected $secret;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId, $siteSecret);
        $this->secret = $siteSecret;
    }

    public function enable()
    {
        $path = 'varnish/enable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while enabling Varnish integration: %s');
        }
    }

    public function disable()
    {
        $path = 'varnish/disable/' . $this->siteId;

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST');

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return true;
            default:
                $this->throwException($httpResponse, 'Error while disabling Varnish integration: %s');
        }
    }

    public function configure($settings)
    {
        $path = 'varnish/configure/' . $this->siteId;

        $post = [
            'Servers' => !empty($settings['Servers']) ? $settings['Servers'] : [],
            'PurgeAllUrl' => !empty($settings['PurgeAllUrl']) ? $settings['PurgeAllUrl'] : '',
            'PurgeAllMethod' => !empty($settings['PurgeAllMethod']) ? $settings['PurgeAllMethod'] : '',
            'PurgeSingleMethod' => !empty($settings['PurgeSingleMethod']) ? $settings['PurgeSingleMethod'] : '',
        ];

        $httpResponse = $this->makeRequest($path, array(), array(), 'POST', $post);

        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        switch ($status) {
            case ResponseStatus::OK:
                return json_decode($httpResponse->getBody(), true);
            default:
                $this->throwException($httpResponse, 'Error while configure the Varnish integration: %s');
        }
    }
}