<?php
namespace NitroPack\SDK\Api;
use \NitroPack\HttpClient\HttpClient;
use \NitroPack\SDK\NitroPack;
use \NitroPack\SDK\HealthStatus;
use \NitroPack\SDK\ServiceDownException;

class Base {
    protected $baseUrl = 'https://api.getnitropack.com/';
    protected $siteId;
    protected $isBacklogEnabled;
    protected $backlog;
    protected $nitropack;

    public function __construct($siteId) {
        $this->siteId = $siteId;
        $this->isBacklogEnabled = false;
        $this->backlog = NULL;
        $this->nitropack = NULL;

        if (defined('NITROPACK_API_BASE_URL')) {
            $this->baseUrl = NITROPACK_API_BASE_URL;
        }
    }

    public function setBacklog($backlog) {
        $this->backlog = $backlog;
    }

    public function setNitroPack($nitropack) {
        $this->nitropack = $nitropack;
    }

    protected function addToBacklog($entry) {
        $this->backlog->append($entry);
    }

    protected function makeRequest($path, $headers = array(), $cookies = array(), $type = 'GET', $bodyData=array(), $async = false, $verifySSL = false) {
        $backlogEntry = array(
            "path" => $path,
            "headers" => $headers,
            "cookies" => $cookies,
            "type" => $type,
            "bodyData" => $bodyData,
            "async" => $async,
            "verifySSL" => $verifySSL
        );

        if ($this->nitropack && $this->nitropack->getHealthStatus() !== HealthStatus::HEALTHY) {
            $unhealthyMsg = "Connection to NitroPack is not reliable at the moment. Please try again in a few minutes.";
            if ($this->isBacklogEnabled) {
                $this->addToBacklog($backlogEntry);
                $unhealthyMsg .= " Request has been added to the backlog for delayed processing.";
            }
            throw new ServiceDownException($unhealthyMsg);
        }

        $http = new HttpClient($this->baseUrl . $path); // HttpClient keeps a cache of the opened connections, so creating a new instance every time is not an issue
        $http->connect_timeout = 3; // in seconds
        $http->ssl_timeout = 3; // in seconds
        $http->timeout = 30; // in seconds

        foreach ($headers as $name => $value) {
            $http->setHeader($name, $value);
        }

        foreach ($cookies as $name => $value) {
            $http->setCookie($name, $value);
        }

        if (in_array($type, array('POST', 'PUT'))) {
            $http->setPostData($bodyData);
        }

        $http->setVerifySSL($verifySSL);

        if ($this->isBacklogEnabled) {
            $http->backlogEntry = $backlogEntry;
        }

        if ($async) {
            $http->fetch(true, $type, $async);
        } else {
            $retries = 1;
            $isRequestProcessed = false;
            while ($retries--) {
                try {
                    $http->fetch(true, $type, $async);
                    if ($http->getStatusCode() < 500) {
                        $isRequestProcessed = true;
                        break;
                    }
                } catch (\Exception $e) {
                    if ($retries == 0) {
                        if (!$isRequestProcessed) {
                            if ($this->nitropack) {
                                if ($this->isBacklogEnabled) {
                                    $this->nitropack->setHealthStatus(HealthStatus::SICK);
                                } else {
                                    $now = microtime(true);
                                    $issuesFirstAppearedAt = $this->nitropack->getTimeMark("service-status");
                                    if ($issuesFirstAppearedAt === NULL) {
                                        $this->nitropack->setTimeMark("service-status", $now);
                                    } else {
                                        if ($now - $issuesFirstAppearedAt > 60) {
                                            $this->nitropack->setHealthStatus(HealthStatus::SICK);
                                        }
                                    }
                                }
                            }

                            if ($this->isBacklogEnabled) {
                                $this->addToBacklog($backlogEntry);
                            }
                        }
                        throw $e;
                    }
                }

                if ($retries > 0) {
                    usleep(500000);
                }
            }

            if ($isRequestProcessed) {
                $this->nitropack && $this->nitropack->unsetTimeMark("service-status");
            } else { // In case all response codes were 500+
                $this->nitropack && $this->nitropack->setHealthStatus(HealthStatus::UNDER_THE_WEATHER);
                if ($this->isBacklogEnabled) {
                    $this->addToBacklog($backlogEntry);
                }
            }
        }

        return $http;
    }

    protected function makeRequestAsync($path, $headers = array(), $cookies = array(), $type = 'GET', $bodyData=array(), $verifySSL = false) {
        return $this->makeRequest($path, $headers, $cookies, $type, $bodyData, true, $verifySSL);
    }

    protected function throwException($httpResponse, $template) {
        try {
            $err = json_decode($httpResponse->getBody(), true);
            $errorMessage = isset($err['error']) ? $err['error'] : 'Unknown';
        } catch (\Exception $e) {
            $errorMessage = 'Unknown';
        }

        $isServiceUnavailable = false;

        if ($errorMessage == 'Unknown') { // Fallback to known HTTP errors
            $statusCode = $httpResponse->getStatusCode();
            switch ($statusCode) {
            case ResponseStatus::BAD_REQUEST:
                $errorMessage = "Bad Request";
                break;
            case ResponseStatus::FORBIDDEN:
                $errorMessage = "Forbidden";
                break;
            case ResponseStatus::NOT_FOUND:
                $errorMessage = "Not Found";
                break;
            case ResponseStatus::RUNTIME_ERROR:
                $errorMessage = "Runtime Error";
                break;
            case ResponseStatus::SERVICE_UNAVAILABLE:
                $isServiceUnavailable = true;
                $errorMessage = "Service Unavailable";
                break;
            default:
                $errorMessage = 'Unknown';
                break;
            }
        }

        if ($isServiceUnavailable) {
            throw new ServiceDownException(sprintf($template, $errorMessage));
        } else {
            throw new \RuntimeException(sprintf($template, $errorMessage));
        }
    }
}