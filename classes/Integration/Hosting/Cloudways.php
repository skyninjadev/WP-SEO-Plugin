<?php

namespace NitroPack\Integration\Hosting;

class Cloudways extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        return array_key_exists("cw_allowed_ip", $_SERVER) || preg_match("~/home/.*?cloudways.*~", __FILE__);
    }

    public function init($stage) {
        if ($this->getHosting() == "cloudways") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            add_action('nitropack_early_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cacheable_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cachehit_cache_headers', [$this, 'setCacheControl']);
        }
    }

    public function purgeUrl($url) {
        try {
            $purger = new \NitroPack\SDK\Integrations\Varnish(array("127.0.0.1"), "URLPURGE");
            $purger->purge($url);
        } catch (\Exception $e) {
            // Exception
        }
    }

    public function purgeAll() {
        try {
            $siteConfig = nitropack_get_site_config();
            if(!empty($siteConfig['home_url'])) {
                $homepage = nitropack_trailingslashit($siteConfig['home_url']) . '.*';
            }
            $purger = new \NitroPack\SDK\Integrations\Varnish(array("127.0.0.1"), "PURGE");
            $purger->purge($homepage);
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
