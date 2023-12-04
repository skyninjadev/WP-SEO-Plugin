<?php

namespace NitroPack\Integration\Hosting;

class RocketNet extends Hosting {
    const STAGE = "early";

    public static function detect() {
        return defined("ROCKET_SITE_ID") || strpos(gethostname(), "onrocket.com") !== false;
    }

    public function init($stage) {
        if ($this->getHosting() == "rocketnet") {
            if (class_exists("CDN_Clear_Cache_Api")) {
                add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            }

            if (class_exists("CDN_Clear_Cache_Hooks")) {
                add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            }
        }
    }

    public function purgeUrl($url) {
        $urlObj = new \NitroPack\Url\Url($url);
        $entry = $urlObj->getPath();
        if ($urlObj->getQuery()) {
            $entry .= "?" . $urlObj->getQuery();
        }
        \CDN_Clear_Cache_Api::cache_api_call([$entry], 'purge');
    }

    public function purgeAll() {
        \CDN_Clear_Cache_Hooks::purge_cache();
    }
}
