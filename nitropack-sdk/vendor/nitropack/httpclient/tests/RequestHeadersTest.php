<?php

use NitroPack\Url\Url;
use NitroPack\HttpClient\HttpClient;
use PHPUnit\Framework\TestCase;

class RequestHeadersTest extends TestCase
{
    public function testValidUrl()
    {
        $urlString = 'http://localhost:2654/';

        $client = new HttpClient($urlString);
        $this->assertStringContainsString("host: localhost:2654", $client->getRequestHeaders());
    }
}
