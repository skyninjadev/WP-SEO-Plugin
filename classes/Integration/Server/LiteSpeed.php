<?php

namespace NitroPack\Integration\Server;
use NitroPack\WordPress\NitroPack;
use NitroPack\Url\Url;
use NitroPack\SDK\Device;
use NitroPack\Integration\Hosting\WPX;

class LiteSpeed {
    const STAGE = "very_early";

    public static function detect() {
        return !empty($_SERVER["X-LSCACHE"]) || ( !empty($_SERVER["SERVER_SOFTWARE"]) && strtolower($_SERVER["SERVER_SOFTWARE"]) == "litespeed" );
    }

    public static function isCacheEnabled() {
        return
            !empty($_SERVER["X-LSCACHE"]) &&
            in_array("on", array_map("trim", explode(",", $_SERVER["X-LSCACHE"]))) &&
            !empty($_SERVER['LSCACHE_VARY_VALUE']) &&
            in_array($_SERVER['LSCACHE_VARY_VALUE'], array("nitrodesktop", "nitrotablet", "nitromobile"));
    }

    public static function sendCacheHeader($maxAge = NULL) {
        if (!$maxAge) {
            nitropack_header("X-LiteSpeed-Cache-Control: public");
        } else if (is_numeric($maxAge)) {
            nitropack_header("X-LiteSpeed-Cache-Control: public,max-age=" . (int)$maxAge);
        }
    }

    public static function purge($url = NULL, $tag = NULL) {
        if ($url || $tag) {
            $headerValues = [];

            if ($url) {
                $urlObj = new Url((new Url($url))->getNormalized());
                if (!$urlObj->getQuery()) {
                    $headerValues[] = "uri=" . md5($urlObj->getPath());
                } else {
                    $headerValues[] = "uri=" . md5($urlObj->getPath() . "?" . $urlObj->getQuery());
                }
            }

            if ($tag) {
                $headerValues[] = $tag;
            }

            nitropack_header("X-LiteSpeed-Purge: " . implode(", ", $headerValues), false);
        } else {
            nitropack_header("X-LiteSpeed-Purge: *", false);
        }
    }

    public function init($stage) {
        if (self::detect()) {
            add_filter('nitropack_should_modify_htaccess', function() { return true; });
            add_filter('nitropack_htaccess_rules', [$this, 'getHtaccessRules'], 10);

            if (self::isCacheEnabled()) {
                add_action('nitropack_integration_purge_url', [$this, 'purgeUrl']);
                add_action('nitropack_integration_purge_all', [$this, 'purgeAll']);
                add_action('nitropack_early_cache_headers', [$this, 'setupVary']);
                add_action('nitropack_early_cache_headers', [$this, 'preventProxyCache']);
                add_action('nitropack_cacheable_cache_headers', [$this, 'allowProxyCache']);
                add_filter('nocache_headers', function($headers) {
                    $headers["X-LiteSpeed-Cache-Control"] = "no-cache";
                    return $headers;
                });
            } else {
                add_filter('nitropack_needs_htaccess_changes', function() { return true; });
            }
        }
    }

    public function purgeUrl($url) {
        self::purge($url);
    }

    public function purgeAll() {
        self::purge();
    }

    public function setupVary() {
        $cookies = []; // Configure vary based on NitroPack's variation cookies.

        $nitro = get_nitropack()->getSdk();
        if ($nitro && !empty($nitro->getConfig()->PageCache->SupportedCookies)) {
            $cookies = $nitro->getConfig()->PageCache->SupportedCookies;
        }

        $deviceStr = "nitrodesktop";
        if ( ! empty($_SERVER["HTTP_USER_AGENT"]) ) {
            $device = new Device($_SERVER["HTTP_USER_AGENT"]);

            if ($device->isMobile()) {
                $deviceStr = "nitromobile";
            } else if ($device->isTablet()) {
                $deviceStr = "nitrotablet";
            }
        }

        $varyStr = "";

        if ($cookies) {
            $varyStr = implode(",", array_map(function($name) { return "cookie=$name"; }, $cookies)); // Vary on multiple cookies should look like this: cookie=name1,cookie=name2
        }

        $varyStr .= ($varyStr ? ", " : "") . "value=$deviceStr";

        nitropack_header("X-LiteSpeed-Vary: $varyStr");
    }

    public function preventProxyCache() {
        nitropack_header("X-LiteSpeed-Cache-Control: no-cache");
    }

    public function allowProxyCache() {
        self::sendCacheHeader(3600);
        $urlObj = new Url(NitroPack::getInstance()->getSdk()->getUrl());
        if (!$urlObj->getQuery()) {
            $uri = md5($urlObj->getPath());
        } else {
            $uri = md5($urlObj->getPath() . "?" . $urlObj->getQuery());
        }

        nitropack_header("X-LiteSpeed-Tag: uri=$uri");

        // TODO: Add LSC-Cookie headers for variation cookies - https://docs.litespeedtech.com/lscache/devguide/controls/#lsc-cookie
    }

    public function getHtaccessRules($ruleLines) {
        $rules = "
<IfModule LiteSpeed>
RewriteEngine on
CacheLookup on

RewriteRule .* - [E=NitroPackHtaccessVersion:NITROPACK_VERSION]
RewriteRule .* - [E=Cache-Control:vary=nitrodesktop]

RewriteCond %{HTTP_USER_AGENT} Android|iPad|RIM\ Tablet|hp-tablet|Kindle\ Fire [NC]
RewriteRule .* - [E=Cache-Control:vary=nitrotablet]

RewriteCond %{HTTP_USER_AGENT} iPod|iPhone|MobileSafari|webOS|BlackBerry|windows\ phone|symbian|vodafone|opera\ mini|windows\ ce|smartphone|palm|midp [NC,OR]
RewriteCond %{HTTP_USER_AGENT} Android.*Mobile [NC,OR]
RewriteCond %{HTTP_USER_AGENT} Mobile.*Android [NC]
RewriteRule .* - [E=Cache-Control:vary=nitromobile]

RewriteCond %{HTTP_COOKIE} COOKIEBYPASS
RewriteRule .* - [E=Cache-Control:no-cache]

# QSDROP

</IfModule>
";

        $rules = str_replace("NITROPACK_VERSION", NITROPACK_VERSION, $rules);

        $nitro = get_nitropack()->getSdk();

        $bypassCookies = ["wordpress_logged_in", "comment_author", "wp-postpass_", "woocommerce_items_in_cart="];
        if (defined('NITROPACK_LOGGED_IN_COOKIE') || defined('LOGGED_IN_COOKIE')) {
            $bypassCookies[] = (defined('NITROPACK_LOGGED_IN_COOKIE') ? NITROPACK_LOGGED_IN_COOKIE : (defined('LOGGED_IN_COOKIE') ? LOGGED_IN_COOKIE : '')) . "="; // Add "=" for exact match
        }
        if ($nitro && !!$nitro->getConfig()->ExcludedCookies->Status && !empty($nitro->getConfig()->ExcludedCookies->Cookies)) {
            foreach ($nitro->getConfig()->ExcludedCookies->Cookies as $excludedCookie) {
                if ($excludedCookie->values) {
                    $bypassCookies[] = $excludedCookie->name . "=(" . implode("|", $excludedCookie->values) . ')\s*(;|$)';
                } else {
                    $bypassCookies[] = $excludedCookie->name . "=";
                }
            }
        }
        $rules = str_replace("COOKIEBYPASS", "(" . implode("|", array_map(function($cookie){ return "(^|\;\s*)$cookie"; }, $bypassCookies)) . ")", $rules);

        if ($nitro && !empty($nitro->getConfig()->IgnoredParams)) {
            $rules = str_replace("# QSDROP", implode("\n", array_map(function($param) { return "CacheKeyModify -qs:$param"; }, array_filter($nitro->getConfig()->IgnoredParams, function($param) { return $param != "ignorenitro"; }))), $rules);
        }

        return array_merge($ruleLines, explode("\n", $rules));
    }
}
