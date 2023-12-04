<?php
namespace NitroPack\HttpClient;

class HttpClientProxy {
    protected $addr;
    protected $port;
    protected $forceOnPrivate;

    public function __construct($ip, $port, $forceOnPrivate = false) {
        $this->addr = $ip;
        $this->port = $port ? $port : 1080;
        $this->forceOnPrivate = $forceOnPrivate;
    }

    public function getAddr() {
        return $this->addr;
    }

    public function getPort() {
        return $this->port;
    }

    public function shouldForceOnPrivate() {
        return $this->forceOnPrivate;
    }
}