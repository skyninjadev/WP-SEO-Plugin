<?php

namespace NitroPack\Integration\Plugin;
use NitroPack\WordPress\NitroPack;

class WPForms {

	const STAGE = "late";
	protected $np_wpform_cache_valid = false;

	public static function isActive() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		return function_exists( 'wpforms' ) || in_array( 'wpforms/wpforms.php', $active_plugins );
	}

	public function init( $stage ) {
		add_filter( 'wpforms_form_token_check_before_today', array( $this, 'extend_wpforms_token_expiration' ) );
	}

	public function extend_wpforms_token_expiration( $times ) {
		$nitro = NitroPack::getInstance();
		if ($nitro && $nitro->getSdk()) {
			$config = $nitro->getSdk()->getConfig();
			$cacheTtlDays = (int)($config->PageCache->ExpireTime / DAY_IN_SECONDS);
			for ($day = 1; $day <= $cacheTtlDays; $day++) {
				$times[] = $day * DAY_IN_SECONDS;
			}
		}
		return $times;
	}

}
