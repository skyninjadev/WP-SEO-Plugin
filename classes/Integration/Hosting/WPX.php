<?php

namespace NitroPack\Integration\Hosting;

class WPX extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        $hostname = gethostname();
        return $hostname && (preg_match("/wpx\.net$/", $hostname) || preg_match("/wpxhosting\.com$/", $hostname));
    }

    public static function isCDNEnabled() {
        $siteConfig = get_nitropack()->getSiteConfig();
        if ($siteConfig) {
            $urlObj = new \NitroPack\Url\Url($siteConfig["home_url"]);
            $ip = gethostbyname($urlObj->getHost());

            $wpxMin = ip2long("194.1.147.1");
            $wpxMax = ip2long("194.1.147.254");
            $clientIp = ip2long($ip);

            return $clientIp >= $wpxMin && $clientIp <= $wpxMax;
        }

        return false;
    }

    public function init($stage) {
        if ($this->getHosting() == "wpx" && self::isCDNEnabled()) {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            add_action('nitropack_cacheable_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cachehit_cache_headers', [$this, 'setCacheControl']);
        }
    }

    public function purgeUrl($url) {
        try {
            $siteConfig = get_nitropack()->getSiteConfig();
            if ($siteConfig) {
                $urlObj = new \NitroPack\Url\Url($siteConfig["home_url"]);
                $purgeUrl = "http://" . $urlObj->getHost() . ":6081" . $urlObj->getPath() . ".*";
                $purger = new \NitroPack\SDK\Integrations\ReverseProxy(array("127.0.0.1"), "PURGE");
                $purger->purge($purgeUrl);
            }
        } catch (\Exception $e) {
            // Exception
        }
    }

    public function purgeAll() {
        try {
            $siteConfig = get_nitropack()->getSiteConfig();
            if ($siteConfig) {
                $urlObj = new \NitroPack\Url\Url($siteConfig["home_url"]);
                $purgeUrl = "http://" . $urlObj->getHost() . ":6081/.*";
                $purger = new \NitroPack\SDK\Integrations\ReverseProxy(array("127.0.0.1"), "PURGE");
                $purger->purge($purgeUrl);
            }
        } catch (\Exception $e) {
            // Exception
        }
    }

    public function setCacheControl() {
        nitropack_header("Cache-Control: public, max-age=0, s-maxage=3600");
        nitropack_header("CDN-Cache-Control: public, max-age=3600");
    }
}

