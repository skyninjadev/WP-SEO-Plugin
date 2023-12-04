<?php

namespace NitroPack\Integration\Hosting;

class Pagely extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        return class_exists('\PagelyCachePurge') || isset($_SERVER["HTTP_X_PAGELY_SSL"]);
    }

    public function init($stage) {
        if ($this->getHosting() == "pagely") {
            switch ($stage) {
            case "very_early":
                add_action('nitropack_cacheable_cache_headers', [$this, 'addCacheControl']);
                add_action('nitropack_cachehit_cache_headers', [$this, 'addCacheControl']);
                add_filter('nitropack_can_serve_cache', [$this, 'canServeCache']);
                \NitroPack\Integration::initSemAcquire();
                return true;
            case "early":
                \NitroPack\Integration::initSemRelease();
                add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
                add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
                return true;
            default:
                return false;
            }
        }
    }

    public function purgeUrl($url) {
        try {
            $path = parse_url($url, PHP_URL_PATH);
            if (class_exists("\PagelyCachePurge")) { // We need to have this check for clients that switch hosts
                $pagely = new PagelyCachePurge();
                $pagely->purgePath($path . "(.*)");
            }
        } catch (\Exception $e) {
            // Pagely exception
        }
    }

    public function purgeAll() {
        try {
            if (class_exists("\PagelyCachePurge")) { // We need to have this check for clients that switch hosts
                $pagely = new PagelyCachePurge();
                $pagely->purgeAll();
            }
        } catch (\Exception $e) {
            // Pagely exception
        }
    }

    public function addCacheControl() {
        if ($this->isHealthcheckRequest()) {
            nitropack_header("Cache-Control: no-cache");
            nitropack_header("X-Nitro-Disabled: 1");
        } else {
            nitropack_header("Cache-Control: public, max-age=0, s-maxage=3600");
        }
    }

    public function canServeCache() {
        return !$this->isHealthcheckRequest();
    }

    public function isHealthcheckRequest() {
        return !empty($_SERVER['REQUEST_URI']) && trim(strtolower($_SERVER['REQUEST_URI']), "/") == "pagely/status";
    }
}
