<?php

namespace NitroPack\Integration\Hosting;

class Vimexx extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        $hostname = gethostname();
        return (!empty($hostname) && strpos($hostname, 'zxcs') !== false) || !empty($_SERVER['HTTP_X_ZXCS_VHOST']);
    }

    public function init($stage) {
        if ($this->getHosting() == "vimexx") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
        }
    }

    public function purgeUrl($url) {
        try {
            $http = new \NitroPack\HttpClient\HttpClient($url);
            $http->setHeader("X-Purge-ZXCS", "true");
            $http->setHeader("host-ZXCS", $_SERVER['HTTP_HOST']);

            $http->fetch(false, "PURGE");
        } catch (\Exception $e) {
            // Exception
        }
    }

    public function purgeAll() {
        try {
            $siteConfig = nitropack_get_site_config();

            if(!empty($siteConfig['home_url'])) {
                $homepage = $siteConfig['home_url'];
                $this->purgeUrl(nitropack_trailingslashit($homepage) . '?purgeAll');
            }
        } catch (\Exception $e) {
            // Exception
        }
    }
}

