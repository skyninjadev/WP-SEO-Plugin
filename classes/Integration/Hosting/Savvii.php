<?php

namespace NitroPack\Integration\Hosting;

class Savvii extends Hosting
{
    const STAGE = "very_early";

    public static function detect()
    {
        return isset($_SERVER['WARPDRIVE_API']) && $_SERVER['WARPDRIVE_API'] == 'https://api.savvii.services';
    }

    public function init($stage)
    {
        if ($this->getHosting() == "savvii") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            add_action('nitropack_early_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cacheable_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cachehit_cache_headers', [$this, 'setCacheControl']);
        }
    }

    public function purgeUrl($url)
    {
        try {
            $siteConfig = nitropack_get_site_config();
            if ($siteConfig && !empty($siteConfig['home_url'])) {
                $urlObject = new \NitroPack\Url\Url($url);

                $http = new \NitroPack\HttpClient\HttpClient(nitropack_trailingslashit($siteConfig['home_url']) . 'purge');
                $http->setHeader('X-PURGE-HOST', $urlObject->getHost());
                $http->setHeader('X-PURGE-PATH-REGEX', $urlObject->getPath() . '.*');
                $http->fetch(false, "PURGE");
            }
        } catch (\Exception $e) {
            // Breeze exception
        }
    }

    public function purgeAll() {
        try {
            $siteConfig = nitropack_get_site_config();
            if ($siteConfig && !empty($siteConfig['home_url'])) {
                $url = new \NitroPack\Url\Url($siteConfig['home_url']);

                $http = new \NitroPack\HttpClient\HttpClient($url->getNormalized() . 'purge');
                $http->setHeader('X-PURGE-HOST', $url->getHost());
                $http->fetch(false, "PURGE");
            }
        } catch (\Exception $e) {
            // Exception
        }
    }

    public function setCacheControl() {
        nitropack_header("Vary: sec-ch-ua-mobile");
        if (isset($_SERVER["HTTP_SEC_CH_UA_MOBILE"])) {
            nitropack_header("Cache-Control: public, max-age=0, s-maxage=3600"); // needs to be like that instead of Cache-Control: no-cache in order to allow caching in the provided reverse proxy, but prevent the browsers from doing so
        } else {
            return;
        }
    }
}
