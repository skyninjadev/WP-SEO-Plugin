<?php

namespace NitroPack\Integration\Server;

// We need this to control Cloudflare in addition to any other proxy potentially provided by the origin host company
class Cloudflare {
    const STAGE = "very_early";

    public static function detect() {
        return (!empty($_SERVER["HTTP_CF_CONNECTING_IP"]) && $_SERVER["HTTP_CF_CONNECTING_IP"] != "0.0.0.0")
            || !empty($_SERVER["HTTP_CF_RAY"])
            || !empty($_SERVER["HTTP_CF_IPCOUNTRY"]);
    }

    public static function isCacheEnabled() {
        $siteConfig = get_nitropack()->getSiteConfig();
        if ($siteConfig && !empty($siteConfig["hosting"]) && $siteConfig["hosting"] == "rocketnet") {
            return self::detect();
        }
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
        $siteConfig = get_nitropack()->getSiteConfig();
        if ($siteConfig && !empty($siteConfig["hosting"]) && $siteConfig["hosting"] == "rocketnet") {
	        nitropack_header( "Cloudflare-CDN-Cache-Control: public, max-age=0, s-maxage=300, stale-while-revalidate=3600" );
        } else if ($siteConfig && !empty($siteConfig["isApoActive"])) {
	        nitropack_header("Cloudflare-CDN-Cache-Control: public, max-age=0, s-maxage=3600, stale-while-revalidate=3600");
		} else {
            nitropack_header("Vary: sec-ch-ua-mobile");
            nitropack_header("Cloudflare-CDN-Cache-Control: public, max-age=0, s-maxage=15, stale-while-revalidate=3600");
        }
    }

    public function preventProxyCache() {
        nitropack_header("Cloudflare-CDN-Cache-Control: no-cache");
    }
}
