<?php

namespace NitroPack\Integration\Server;

class Fastly {
    const STAGE = "very_early";

    public static function detect() {
        return !empty($_SERVER["HTTP_SURROGATE_CONTROL"]);
    }

    public static function isCacheEnabled() {
        return self::detect() && !empty($_SERVER["HTTP_SEC_CH_UA_MOBILE"]);
    }

    public function init($stage) {
        if (self::detect()) {
            nitropack_header("Accept-CH: Sec-CH-UA-Mobile");

            if (self::isCacheEnabled()) {
                add_action('nitropack_early_cache_headers', [$this, 'allowProxyCache']);
            } else {
                add_action('nitropack_early_cache_headers', [$this, 'preventProxyCache']);
            }
        }
    }

    public function allowProxyCache() {
        nitropack_header("Vary: sec-ch-ua-mobile");
        nitropack_header("Surrogate-Control: max-age=5, stale-while-revalidate=3600");
    }

    public function preventProxyCache() {
        nitropack_header("Surrogate-Control: max-age=0, must-revalidate");
    }
}

