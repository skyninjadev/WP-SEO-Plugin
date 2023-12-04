<?php

namespace NitroPack\Integration\Hosting;

class Pressable extends Hosting {
    const STAGE = "very_early";
    private $deviceTypes = ["mobile", "tablet", "desktop"];
    private $configuredCacheGroups = false;

    public static function detect() {
        return isset($_SERVER["PRESSABLE_PROXIED_REQUEST"]) || strpos(gethostname(), "atomicsites.net") !== false;
    }

    public function init($stage) {
        if ($this->getHosting() == "pressable") {
            switch ($stage) {
            case "very_early":
                $this->noCache();
                add_action('nitropack_cacheable_cache_headers', [$this, 'cacheShort']);
                add_action('nitropack_cachehit_cache_headers', [$this, 'cacheLong']);
                \NitroPack\Integration::initSemAcquire();
                return true;
            case "early":
                \NitroPack\Integration::initSemRelease();
                add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
                add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
                return;
            default:
                return;
            }
        }
    }

    public function noCache() {
        global $batcache;

        if (!empty($batcache) && is_object($batcache)) {
            $batcache->max_age = 0;
        }
    }

    public function cacheShort() {
        global $batcache;

        if (!empty($batcache) && is_object($batcache)) {
            $batcache->max_age = 30;
        }
    }

    public function cacheLong() {
        global $batcache;

        if (!empty($batcache) && is_object($batcache)) {
            $batcache->max_age = 300;
        }
    }

    public function purgeUrl($url) {
        global $batcache;

        if (!$batcache || !is_object($batcache)) return;

        $urlObj = new \NitroPack\Url\Url($url);
        if ($urlObj->getHost()) {
            parse_str($urlObj->getQuery(), $query);

            foreach ($batcache->ignored_query_args as $arg) {
                unset($query[$arg]);
            }
            ksort($query);

            $keys = array(
                'host' => $urlObj->getHost(),
                'method' => "GET",
                'path' => $urlObj->getPath(),
                'query' => $query,
                'extra' => []
            );

            if ( isset( $batcache->origin ) ) {
                $keys['origin'] = $batcache->origin;
            }

            if ( $urlObj->getScheme() == "https" )
                $keys['ssl'] = true;

            wp_cache_init();
            $batcache->configure_groups();

            foreach ($this->deviceTypes as $deviceType) {
                $keys["extra"] = [$deviceType];
                $url_key = md5(serialize($keys));

                wp_cache_delete( $url_key, $batcache->group );
            }
        }
    }

    public function purgeAll() {
        wp_cache_flush();
    }
}

