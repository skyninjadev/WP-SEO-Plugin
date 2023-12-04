<?php
namespace NitroPack\SDK\Api;

use \NitroPack\SDK\IntegrationUrl;
use \NitroPack\SDK\Website;
use \NitroPack\SDK\Crypto;

class Integration extends Base {
    private $siteSecret;
    private $keys;

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId);

        $this->baseUrl = IntegrationUrl::getBaseUrl();
        $this->siteSecret = $siteSecret;
    }

    /**
     * Generates a private/public key pair. This is a slow operation! Run it no more than once only whenever needed!
     * @return stdClass Pair of private and public keys
     */
    protected function keysInstance() {
        // This must be executed only once per request.
        if (empty($this->keys)) {
            $this->keys = Crypto::generateKeyPair();
        }

        return $this->keys;
    }

    protected function websiteFromStruct($data, $errorTemplate, $privateKey) {
        $site = new Website;

        $json = Crypto::decrypt($data->credentials, $privateKey);

        if ($json == "" || null === @json_decode($json)) {
            throw new \RuntimeException(sprintf($errorTemplate, "Cannot decrypt website credentials!"), ResponseStatus::BAD_REQUEST);
        }

        $credentials = @json_decode($json);

        $site->setName($data->name);
        $site->setURL($data->url);
        $site->setAPIKey($credentials->apikey);
        $site->setAPISecret($credentials->apisecret);
        $site->setUsedDiskSpaceBytes((int)$data->used_disk_space_bytes);
        $site->setUsedOptimizations((int)$data->used_optimizations);
        $site->setLastQuotaResetTimestamp((int)$data->last_quota_reset_timestamp);
        $site->setStatus(!!$data->status);
        $site->setCreatedTimestamp((int)$data->created_timestamp);
        $site->setModifiedTimestamp((int)$data->created_timestamp);

        return $site;
    }

    public function create(Website $website) {
        // Prepare keys
        $keys = $this->keysInstance();

        // Prepare the request URL
        $url = new IntegrationUrl("website_create", $this->siteId, $this->siteSecret);

        // Error template
        $errorTemplate = "Error while creating website: %s";

        // Request headers
        $headers = array();

        $headers['X-Nitro-Public-Key'] = base64_encode($keys->publicKey);

        // Request data
        $data = array();

        $data['website_url'] = $website->getURL();
        $data['website_name'] = $website->getName();

        // Do the request
        $httpResponse = $this->makeRequest($url->getPath(), $headers, array(), "POST", $data, false, true);
        
        // Read the response body
        if (null === $responseBody = @json_decode($httpResponse->getBody())) {
            $errorMessage = "No response body!";

            throw new \RuntimeException(sprintf($errorTemplate, $errorMessage), ResponseStatus::RUNTIME_ERROR);
        }

        // React according to the status code
        $statusCode = ResponseStatus::getStatus($httpResponse->getStatusCode());

        switch ($statusCode) {
            case ResponseStatus::OK:
                // All is well, return a result
                return $this->websiteFromStruct($responseBody->data->site, $errorTemplate, $keys->privateKey);
            default:
                // An error has occurred, throw an exception with the status code
                throw new \RuntimeException(sprintf($errorTemplate, $responseBody->message), $statusCode);
        }
    }

    public function update(Website $website) {
        // Prepare keys
        $keys = $this->keysInstance();

        // Provide target site_id
        $additional_params = array(
            'target_site_id' => $website->getAPIKey()
        );

        // Prepare the request URL
        $url = new IntegrationUrl("website_update", $this->siteId, $this->siteSecret, null, $additional_params);

        // Error template
        $errorTemplate = "Error while updating website: %s";

        // Request headers
        $headers = array();

        $headers['X-Nitro-Public-Key'] = base64_encode($keys->publicKey);

        // Request data
        $data = array();

        $data['website_url'] = $website->getURL();
        $data['website_name'] = $website->getName();

        // Do the request
        $httpResponse = $this->makeRequest($url->getPath(), $headers, array(), "POST", $data, false, true);
        
        // Read the response body
        if (null === $responseBody = @json_decode($httpResponse->getBody())) {
            $errorMessage = "No response body!";

            throw new \RuntimeException(sprintf($errorTemplate, $errorMessage), ResponseStatus::RUNTIME_ERROR);
        }

        // React according to the status code
        $statusCode = ResponseStatus::getStatus($httpResponse->getStatusCode());

        switch ($statusCode) {
            case ResponseStatus::OK:
                // All is well, return a result
                return $this->websiteFromStruct($responseBody->data->site, $errorTemplate, $keys->privateKey);
            default:
                // An error has occurred, throw an exception with the status code
                throw new \RuntimeException(sprintf($errorTemplate, $responseBody->message), $statusCode);
        }
    }

    public function remove($apikey) {
        // Provide target site_id
        $additional_params = array(
            'target_site_id' => $apikey
        );

        // Prepare the request URL
        $url = new IntegrationUrl("website_remove", $this->siteId, $this->siteSecret, null, $additional_params);

        // Error template
        $errorTemplate = "Error while removing website: %s";

        // Do the request
        $httpResponse = $this->makeRequest($url->getPath(), array(), array(), "DELETE", array(), false, true);
        
        // Read the response body
        if (null === $responseBody = @json_decode($httpResponse->getBody())) {
            $errorMessage = "No response body!";

            throw new \RuntimeException(sprintf($errorTemplate, $errorMessage), ResponseStatus::RUNTIME_ERROR);
        }

        // React according to the status code
        $statusCode = ResponseStatus::getStatus($httpResponse->getStatusCode());

        switch ($statusCode) {
            case ResponseStatus::OK:
                // All is well, return a result
                return true;
            default:
                // An error has occurred, throw an exception with the status code
                throw new \RuntimeException(sprintf($errorTemplate, $responseBody->message), $statusCode);
        }
    }

    public function readByAPIKey($apikey) {
        // Prepare keys
        $keys = $this->keysInstance();

        // Provide target site_id
        $additional_params = array(
            'target_site_id' => $apikey
        );

        // Prepare the request URL
        $url = new IntegrationUrl("website_read", $this->siteId, $this->siteSecret, null, $additional_params);

        // Error template
        $errorTemplate = "Error while reading website: %s";

        // Request headers
        $headers = array();

        $headers['X-Nitro-Public-Key'] = base64_encode($keys->publicKey);

        // Do the request
        $httpResponse = $this->makeRequest($url->getPath(), $headers, array(), "GET", array(), false, true);
        
        // Read the response body
        if (null === $responseBody = @json_decode($httpResponse->getBody())) {
            $errorMessage = "No response body!";

            throw new \RuntimeException(sprintf($errorTemplate, $errorMessage), ResponseStatus::RUNTIME_ERROR);
        }

        // React according to the status code
        $statusCode = ResponseStatus::getStatus($httpResponse->getStatusCode());

        switch ($statusCode) {
            case ResponseStatus::OK:
                // All is well, return a result
                return $this->websiteFromStruct($responseBody->data->site, $errorTemplate, $keys->privateKey);
            default:
                // An error has occurred, throw an exception with the status code
                throw new \RuntimeException(sprintf($errorTemplate, $responseBody->message), $statusCode);
        }
    }

    public function readPaginated($page, $limit = 250) {
        // Prepare keys
        $keys = $this->keysInstance();

        // Provide target site_id
        $additional_params = array(
            'page' => $page,
            'limit' => $limit
        );

        // Prepare the request URL
        $url = new IntegrationUrl("website_read", $this->siteId, $this->siteSecret, null, $additional_params);

        // Error template
        $errorTemplate = "Error while reading website: %s";

        // Request headers
        $headers = array();

        $headers['X-Nitro-Public-Key'] = base64_encode($keys->publicKey);

        // Do the request
        $httpResponse = $this->makeRequest($url->getPath(), $headers, array(), "GET", array(), false, true);
        
        // Read the response body
        if (null === $responseBody = @json_decode($httpResponse->getBody())) {
            $errorMessage = "No response body!";

            throw new \RuntimeException(sprintf($errorTemplate, $errorMessage), ResponseStatus::RUNTIME_ERROR);
        }

        // React according to the status code
        $statusCode = ResponseStatus::getStatus($httpResponse->getStatusCode());

        switch ($statusCode) {
            case ResponseStatus::OK:
                // All is well, return a result
                $result = new \stdClass;
                
                $result->websites = array();

                foreach ($responseBody->data->sites as $site) {
                    $result->websites[] = $this->websiteFromStruct($site, $errorTemplate, $keys->privateKey);
                }

                $result->pagination = $responseBody->data->pagination;

                return $result;
            default:
                // An error has occurred, throw an exception with the status code
                throw new \RuntimeException(sprintf($errorTemplate, $responseBody->message), $statusCode);
        }
    }
}
