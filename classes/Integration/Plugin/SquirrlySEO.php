<?php

namespace NitroPack\Integration\Plugin;

use SQ_Classes_Helpers_Tools;

class SquirrlySEO {
    use CommonHelpers;
    const STAGE = "early";
    public static function isActive() {
        $activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
        if (class_exists('SQ_Classes_Helpers_Tools') || in_array('squirrly-seo/squirrly.php', $activePlugins)) {
            return true;
        }
        return false;
    }
    public static function getSitemapURL() {
        if (class_exists('SQ_Classes_Helpers_Tools') && SQ_Classes_Helpers_Tools::getOption('sq_auto_sitemap')) {
            $sitemapURL = get_home_url() . '/sitemap.xml';
	        return self::validateURL($sitemapURL, 'text/xml') ? $sitemapURL : false;
        }
        return false;
    }
    public function init($stage)
    {
    }
}
