<?php

namespace NitroPack\Integration\Plugin;

use Jetpack;

class JetPackNP {
    use CommonHelpers;
    const STAGE = "late";
    public static function isActive() {
        $activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
        if (class_exists('Jetpack') || in_array( 'jetpack/jetpack.php', $activePlugins )) {
            return true;
        }
        return false;
    }
    public static function getSitemapURL() {
        if (class_exists('Jetpack') && Jetpack::is_module_active( 'sitemaps' )) {
            $sitemapURL    = get_home_url() . '/sitemap.xml';
	        return self::validateURL($sitemapURL, 'text/xml') ? $sitemapURL : false;
        }
        return false;
    }
    public function init($stage) {
    }
}
