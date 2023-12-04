<?php

namespace NitroPack\Integration\Plugin;

class Elementor {

	const STAGE = "late";

	public static function isActive() {
		$activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
		if (defined('ELEMENTOR_PRO_VERSION') || in_array( 'elementor-pro/elementor-pro.php', $activePlugins )) {
			return true;
		}
		return false;
	}

	public function init($stage) {
		if ( ! self::isActive() ) {
			return;
		}

		add_action( 'save_post', array($this, 'purge_cache_on_custom_code_snippet_update'), 10, 3 );
	}
	public function purge_cache_on_custom_code_snippet_update( $post_id, $post, $update ) {

		if ( 'elementor_snippet' !== $post->post_type || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || 'auto-draft' === $post->post_status ) {
			return;
		}

		if( strpos( wp_get_raw_referer(), 'post-new' ) > 0 ) {

			if ( empty( $_POST['code'] ) ) {
				return;
			}

			/* If new snippet is added */
			nitropack_sdk_invalidate(NULL, NULL, 'Elementor Custom Code Snippet Added');

		} else {

			/* If old snippet is Updated */
			nitropack_sdk_invalidate(NULL, NULL, 'Elementor Custom Code Snippet Updated');

		}

	}

}
