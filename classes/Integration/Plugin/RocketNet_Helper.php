<?php

namespace NitroPack\Integration\Plugin;

class RocketNet_Helper extends \Rocket_Wordpress {
    public static function purgeUrl($url) {
        $urlObj = new \NitroPack\Url($url);
        $entry = $urlObj->getPath();
        if ($urlObj->getQuery()) {
            $entry .= "?" . $urlObj->getQuery();
        }
        self::cache_api_call([$entry], "purge");
    }
}

