<?php

namespace NitroPack\Integration\Plugin;

use WPSEO_Options;

class YoastSEO {
    use CommonHelpers;
    const STAGE = "early";
    public static function isActive() {
        $activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
        if (class_exists('WPSEO_Options') || in_array( 'wordpress-seo/wp-seo.php', $activePlugins )) {
            return true;
        }
        return false;
    }
    public static function getSitemapURL() {
        if (class_exists('WPSEO_Options') && WPSEO_Options::get( 'enable_xml_sitemap' )) {
            $sitemapURL = get_home_url() . '/sitemap_index.xml';
	        return self::validateURL($sitemapURL, 'text/xml') ? $sitemapURL : false;
        }
        return false;
    }
    public function init($stage) {
    }
}
