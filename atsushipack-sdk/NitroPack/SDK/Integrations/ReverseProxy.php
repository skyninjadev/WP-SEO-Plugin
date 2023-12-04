<?php
namespace NitroPack\SDK\Integrations;
use \NitroPack\HttpClient\HttpClient;
use \NitroPack\HttpClient\HttpClientMulti;

class ReverseProxy {

    protected $serverList;
    protected $purgeMethod;
    protected $headers;

    public function __construct($serverList=null, $purgeMethod="PURGE", $headers = []) {
        $this->serverList = $serverList;
        $this->purgeMethod = $purgeMethod;
        $this->headers = $headers;
    }

    public function setServerList($serverList=null) {
        $this->serverList = $serverList;
    }

    public function setPurgeMethod($method) {
        $this->purgeMethod = $method;
    }

    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    public function purge($url) {
        if (empty($this->serverList)) return false;

        $httpMulti = new HttpClientMulti();
        foreach ($this->serverList as $server) {
            $client = new HttpClient($url);
            $client->hostOverride($client->host, $server);
            $client->doNotDownload = true;

            foreach ($this->headers as $name => $value) {
                $client->setHeader($name, $value);
            }

            $httpMulti->push($client);
        }

        $httpMulti->fetchAll(true, $this->purgeMethod);
    }
}