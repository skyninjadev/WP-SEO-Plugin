<?php

namespace NitroPack\Integration\Plugin;

use \RankMath\Helper;
class RankMathNP {
	use CommonHelpers;
	const STAGE = "late";
	public static function isActive() {
		$activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
		if (class_exists('RankMath') || in_array( 'seo-by-rank-math/rank-math.php', $activePlugins )) {
			return true;
		}
		return false;
	}
	public static function getSitemapURL() {
		if (class_exists('RankMath\Helper') && Helper::is_module_active( 'sitemap' )) {
			$sitemapURL    = get_home_url() . '/sitemap_index.xml';
			return self::validateURL($sitemapURL, 'text/xml') ? $sitemapURL : false;
		}
		return false;
	}
	public function init($stage) {
	}
}