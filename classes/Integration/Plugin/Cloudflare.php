<?php

namespace NitroPack\Integration\Plugin;

class Cloudflare {
    const STAGE = "very_early";
    private $siteConfig = NULL;

    public static function isApoActive() {
        if (self::canUseCloudflareHelper()) {
            $cfHelper = new CF_Helper();
            return $cfHelper->isApoEnabled();
        } else {
            return false;
        }
    }

    public static function isApoCacheByDeviceTypeEnabled() {
        if (self::canUseCloudflareHelper()) {
            $cfHelper = new CF_Helper();
            return $cfHelper->isApoCacheByDeviceTypeEnabled();
        } else {
            return false;
        }
    }

    public function init($stage) {
        switch ($stage) {
        case "very_early":
            $this->siteConfig = get_nitropack()->getSiteConfig();
            if ($this->siteConfig && !empty($this->siteConfig["isApoActive"])) {
	            add_filter( 'cloudflare_use_cache', function ($useCache){
					return false;
	            });
                add_action('nitropack_early_cache_headers', [$this, 'preventApoCache'], PHP_INT_MAX);
                add_action( 'nitropack_cacheable_cache_headers', [$this, 'allowApoCache'], PHP_INT_MAX );
                add_action( 'nitropack_cachehit_cache_headers', [$this, 'allowApoCache'], PHP_INT_MAX );
                \NitroPack\Integration::initSemAcquire();
            }
            return true;
        case "late":
            if ($this->siteConfig && !empty($this->siteConfig["isApoActive"])) {
                \NitroPack\Integration::initSemRelease();
                add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
                add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            }
        default:
            return false;
        }
    }

    public function purgeUrl($url) {
        if (self::canUseCloudflareHelper()) {
            $cfHelper = new CF_Helper();
            $cfHelper->purgeUrl($url);
        }
    }

    public function purgeAll() {
        if (self::canUseCloudflareHelper()) {
            $cfHelper = new CF_Helper();
            $cfHelper->purgeCacheEverything();
        }
    }

    public function allowApoCache() {
        nitropack_header("cf-edge-cache: cache,platform=wordpress");
    }

    public function preventApoCache() {
        nitropack_header("cf-edge-cache: no-cache");
    }

	private static function canUseCloudflareHelper()
	{
		return defined('CLOUDFLARE_PLUGIN_DIR') && class_exists('\CF\WordPress\Hooks');
	}
}
