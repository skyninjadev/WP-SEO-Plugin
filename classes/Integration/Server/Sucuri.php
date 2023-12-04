<?php

namespace NitroPack\Integration\Server;

// We need this to control Sucuri in addition to any other proxy potentially provided by the origin host company
class Sucuri {
    const STAGE = "very_early";

    public static function detect() {
        return !empty($_SERVER["HTTP_X_SUCURI_CLIENTIP"]) || !empty($_SERVER["HTTP_X_SUCURI_COUNTRY"]);
    }

    public static function isCacheEnabled() {
        return self::detect() && !empty($_SERVER["HTTP_SEC_CH_UA_MOBILE"]);
    }

    public function init($stage) {
        if (self::detect()) {
            nitropack_header("Accept-CH: Sec-CH-UA-Mobile");

            if (self::isCacheEnabled()) {
                add_action('nitropack_cacheable_cache_headers', [$this, 'allowProxyCache'], PHP_INT_MAX-1);
                add_action('nitropack_cachehit_cache_headers', [$this, 'allowProxyCache'], PHP_INT_MAX-1);
            } else {
                add_action('nitropack_cacheable_cache_headers', [$this, 'preventProxyCache'], PHP_INT_MAX-1);
                add_action('nitropack_cachehit_cache_headers', [$this, 'preventProxyCache'], PHP_INT_MAX-1);
            }
        }
    }

    public function allowProxyCache() {
        nitropack_header("Vary: sec-ch-ua-mobile");
        nitropack_header("Cache-Control: public, max-age=0, s-maxage=15, stale-while-revalidate=3600");
    }

    public function preventProxyCache() {
        nitropack_header("Cache-Control: no-cache");
    }
}

