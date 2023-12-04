<?php

namespace NitroPack\Integration\Plugin;

class WPCacheHelper {
    use CommonHelpers;
    const STAGE = "late";

    public function init($stage) {
        if (class_exists("\WC_Cache_Helper")) {
            remove_action('template_redirect', array('WC_Cache_Helper', 'geolocation_ajax_redirect'));
        }
    }
    public static function getSitemapURL() {
        $sitemapURL = get_home_url() . '/wp-sitemap.xml';
	    return self::validateURL($sitemapURL, 'text/xml') ? $sitemapURL : false;
    }
}
