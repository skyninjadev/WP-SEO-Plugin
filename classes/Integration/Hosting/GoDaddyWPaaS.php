<?php

namespace NitroPack\Integration\Hosting;

class GoDaddyWPaaS extends Hosting {
    const STAGE = "early";

    public static function detect() {
        return class_exists('\WPaaS\Plugin');
    }

    public function init($stage) {
        if ($this->getHosting() == "godaddy_wpaas") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
        }
    }

    public function purgeUrl($url) {
        if (class_exists('\WPaaS\Plugin')) {
            update_option( 'gd_system_last_cache_flush', time() );
            $hosts = [\WPaaS\Plugin::vip()];
            $url = preg_replace("/^https:\/\//", "http://", $url);
            $purger = new \NitroPack\SDK\Integrations\Varnish($hosts, 'BAN');
            $purger->purge($url);
            return true;
        }

        return false;
    }

    public function purgeAll() {
        $siteConfig = nitropack_get_site_config();
        if ($siteConfig && !empty($siteConfig["home_url"])) {
            return $this->purgeUrl($siteConfig["home_url"]);
        }
        return false;
    }
}
