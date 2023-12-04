<?php

namespace NitroPack\Integration\Plugin;

class WPBakeryNP {

	const STAGE = "late";
	public static function isActive() {
		global $vc_manager;

		$activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
		if ($vc_manager || in_array( 'js_composer/js_composer.php', $activePlugins )) {
			return true;
		}
		return false;
	}

	public function init($stage) {
		if ( ! WPBakeryNP::isActive() ) {
			return;
		}

		add_action('update_option_wpb_js_custom_css', array ($this, 'purge_cache_on_custom_css_update') );
	}

	public function purge_cache_on_custom_css_update() {
		nitropack_sdk_purge(NULL, NULL, 'WPBakery Custom CSS Updated');
	}

}