<?php

use NitroPack\Url\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testValidUrl()
    {
        $urlString = 'https://nitropack.io:80/test-page/?query=true&query2=false#top';

        $url = new Url($urlString);
        $this->assertEquals($urlString, $url->getUrl());
        $this->assertEquals('https', $url->getScheme());
        $this->assertEquals('nitropack.io', $url->getHost());
        $this->assertEquals('/test-page/', $url->getPath());
        $this->assertEquals('top', $url->getHash());
        $this->assertEquals('query=true&query2=false', $url->getQuery());
        $this->assertEquals('80', $url->getPort());
    }

    public function testSchemeToLower()
    {
        $url = new Url('HTTPS://nitropack.io/');
        $this->assertEquals('https', $url->getScheme());
    }

    public function testHostToLower()
    {
        $url = new Url('https://NITROPACK.IO/');
        $this->assertEquals('nitropack.io', $url->getHost());

        $url2 = new Url('NITROPACK.IO');
        $this->assertEquals('nitropack.io', $url2->getHost());

        $url3 = new Url('NITROPACK.IO/test');
        $this->assertEquals('nitropack.io', $url3->getHost());
    }

    public function testDetectAsHost()
    {
        $url = new Url('nitropack.io');
        $this->assertEquals('nitropack.io', $url->getHost());
    }

    public function testDetectAsPath()
    {
        $url = new Url('/test-page');
        $this->assertEquals('/test-page', $url->getPath());
    }

    public function testNormalization()
    {
        $url = new Url('test-page');
        $url->setBaseUrl('nitropack.io');

        $this->assertEquals('http://nitropack.io/', $url->getBaseUrl());
        $this->assertEquals('http://nitropack.io/test-page', $url->getNormalized());
        $this->assertEquals('test-page', $url->getPath());

        $url2 = new Url('Test.woff');
        $url2->setBaseUrl("https://example.com/");
        $this->assertEquals('https://example.com/Test.woff', $url2->getNormalized());
    }

    public function testPercentEncodingToUpper()
    {
        // only deals with the path part of the URL
        // format: test URL => expected result
        $testUrlSet = [
            'https://www.example.com/%rf' => 'https://www.example.com/%rf',
            'https://www.example.com/%D8%AA%D8%B5%D9%86%D9%8A%D9%81/%d8%a8%d9%88%d9%8a%d8%a7%d8%aa-%d9%88%d8%a3%d9%83%d8%b3%d8%b3%d9%88%d8%a7%d8%b1%d8%a7%d8%aa/' => 
            'https://www.example.com/%D8%AA%D8%B5%D9%86%D9%8A%D9%81/%D8%A8%D9%88%D9%8A%D8%A7%D8%AA-%D9%88%D8%A3%D9%83%D8%B3%D8%B3%D9%88%D8%A7%D8%B1%D8%A7%D8%AA/',
            'https://www.example.co.uk/%d7%90%d7%99%d7%aa%d7%95%d7%a8-%d7%a0%d7%96%d7%99%d7%9c%d7%95%d7%aa-%d7%9e%d7%aa%d7%97%d7%aa-%d7%9c%d7%a8%d7%a6%d7%a4%d7%94/' => 
            'https://www.example.co.uk/%D7%90%D7%99%D7%AA%D7%95%D7%A8-%D7%A0%D7%96%D7%99%D7%9C%D7%95%D7%AA-%D7%9E%D7%AA%D7%97%D7%AA-%D7%9C%D7%A8%D7%A6%D7%A4%D7%94/',
            'https://www.example.com' => 'https://www.example.com/',
            'https://www.example.com/%A8Untitled' => 'https://www.example.com/%A8Untitled',
            'https://example.com/%25' => 'https://example.com/%25',
            'https://example.com/?param01=%d0%ba%d0%be%d0%ba%d0%be&param02=%d0%b4%d0%b6%d1%8a%d0%bc%d0%b1%d0%be' => 'https://example.com/?param01=%D0%BA%D0%BE%D0%BA%D0%BE&param02=%D0%B4%D0%B6%D1%8A%D0%BC%D0%B1%D0%BE',
            'https://example.com?%d0%ba%d0%be%d0%ba%d0%be=%d0%b4%d0%b6%d1%8a%d0%bc%d0%b1%d0%be' => 'https://example.com/?%D0%BA%D0%BE%D0%BA%D0%BE=%D0%B4%D0%B6%D1%8A%D0%BC%D0%B1%D0%BE',
            'https://example.com?%d0%ba%d0%be%d0%ba%d0%be=' => 'https://example.com/?%D0%BA%D0%BE%D0%BA%D0%BE=',
            'https://example.com?%d0%ba%d0%be%d0%ba%d0%be' => 'https://example.com/?%D0%BA%D0%BE%D0%BA%D0%BE',
        ];

        foreach ($testUrlSet as $inputUrl => $expectedResult) {
            $testUrl = new Url($inputUrl);
            $this->assertEquals($expectedResult, $testUrl->getNormalized());
        }
    }

    public function testUrlValidation()
    {
        $testUrlSet = [
            "http://sub_domain.example.com/path/path.html" => true,
            "http://sub>domain.example.com/" => false,
            "https://7486822895993461897%6fe6c0fbdf0d210eecb7e5d644a411d037c435af.example.com/" => false,
            "https://sub*domain.example.com/" => false,
            "/index.html" => false,
        ];

        foreach ($testUrlSet as $inputUrl => $expectedResult) {
            $testUrl = new Url($inputUrl);
            $this->assertEquals($expectedResult, $testUrl->isValid());
        }
    }
}