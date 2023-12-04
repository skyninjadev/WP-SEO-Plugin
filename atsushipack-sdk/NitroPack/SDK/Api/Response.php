<?php
namespace NitroPack\SDK\Api;

class Response {
    private $status;
    private $headers;
    private $body;

    public function __construct($status, $body, $headers = array()) {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getBody() {
        return $this->body;
    }

    public function getHeaders() {
        return $this->headers;
    }
}
