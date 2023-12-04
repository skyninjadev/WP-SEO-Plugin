<?php
/**
 * WCML Class
 *
 * @package nitropack
 */

namespace NitroPack\Integration\Plugin;

/**
 * WCML Class
 */
class WCML {
	const STAGE = 'late';

	/**
	 * Check if WooCommerce Multilingual is active
	 *
	 * @return bool
	 */
	public static function isActive() {     //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return is_plugin_active( 'woocommerce-multilingual/wpml-woocommerce.php' );
	}

	/**
	 * Init function
	 *
	 * @param string $stage Stage.
	 *
	 * @return void
	 */
	public function init( $stage ) {    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		if ( self::isActive() ) {
			add_action( 'wcml_switch_currency', array( $this, 'wcml_set_custom_currency_cookie' ) );
			add_action( 'woocommerce_init', array( $this, 'wcml_set_custom_currency_cookie' ) );
			add_action( 'woocommerce_init', array( $this, 'wcml_set_custom_language_cookie' ) );
		}
	}

	/**
	 * Set custom currency cookie
	 *
	 * @param string $currency Currency code.
	 *
	 * @return void
	 */
	public function wcml_set_custom_currency_cookie( $currency = false ) {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		if ( ! empty( $currency ) ) {
			setcookie( 'np_wc_currency', $currency, time() + 60 * 60 * 24 * 7, '/' );
			return;
		}
		if ( function_exists( 'get_woocommerce_currency' ) ) {
			setcookie( 'np_wc_currency', get_woocommerce_currency(), time() + 60 * 60 * 24 * 7, '/' );
		}
	}

	/**
	 * Set custom language cookie
	 *
	 * @return void
	 */
	public function wcml_set_custom_language_cookie() {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$wcCurrencyLanguage = (!is_admin() && isset(WC()->session) && WC()->session->has_session()) ? WC()->session->get("client_currency_language") : 0;
		setcookie('np_wc_currency_language', $wcCurrencyLanguage, time() + (86400 * 7), "/");
	}
}
