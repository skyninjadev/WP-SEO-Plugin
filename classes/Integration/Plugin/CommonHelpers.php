<?php

namespace NitroPack\Integration\Plugin;
use \NitroPack\HttpClient\HttpClient;

trait CommonHelpers {
    public static function validateURL($url, $content_type = false) {
        $httpClient = new HttpClient($url);
        $httpClient->fetch();
        if ($httpClient->getStatusCode() == 200 )
	        return $content_type ? self::validateContentType( $httpClient->getHeaders(), $content_type ) : true;

        return false;
    }
	public static function validateContentType($headers, $content_type) {
		return strpos($headers['content-type'], $content_type) !== false;
	}
}
