<?php

namespace NitroPack\Integration\Hosting;

class DreamHost extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        // Detect DreamPress
        return preg_match("/^dp-.+/", gethostname());
    }

    public function init($stage) {
        if ($this->getHosting() == "dreamhost") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            add_action('nitropack_early_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cacheable_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cachehit_cache_headers', [$this, 'setCacheControl']);
        }
    }

    public function purgeUrl($url) {
        try {
            $urlObj = new \NitroPack\Url\Url($url);
            $purgeUrl = "https://" . $urlObj->getHost() . $urlObj->getPath();
            if ($urlObj->getQuery()) {
                $purger = new \NitroPack\SDK\Integrations\Varnish(array($_SERVER["SERVER_ADDR"]), "PURGE", ["x-purge-method" => "regex"]);
                $purgeUrl .= ".*";
            } else {
                $purger = new \NitroPack\SDK\Integrations\Varnish(array($_SERVER["SERVER_ADDR"]), "PURGE", ["x-purge-method" => "default"]);
            }
            $purger->purge($purgeUrl);
        } catch (\Exception $e) {
            // Exception
        }
    }

    public function purgeAll() {
        try {
            $siteConfig = nitropack_get_site_config();
            if(!empty($siteConfig['home_url'])) {
                $homepage = nitropack_trailingslashit($siteConfig['home_url']) . '.*';
                $purger = new \NitroPack\SDK\Integrations\Varnish(array($_SERVER["SERVER_ADDR"]), "PURGE", ["x-purge-method" => "regex"]);
                $purger->purge($homepage);
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
            nitropack_header("Cache-Control: no-cache");
            return;
        }
    }
}
