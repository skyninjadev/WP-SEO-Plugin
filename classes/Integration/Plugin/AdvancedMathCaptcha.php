<?php
/**
 * AdvancedMathCaptcha Class
 *
 * @package nitropack
 */

namespace NitroPack\Integration\Plugin;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Class AdvancedMathCaptcha
 */
class AdvancedMathCaptcha {
	const STAGE = 'early';

	/**
	 * Check if plugin "The Events Calendar" is active
	 *
	 * @return bool
	 */
	public static function isActive() {     //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return is_plugin_active( 'wp-advanced-math-captcha/wp-math-captcha.php' );
	}

	/**
	 * Initialize the integration
	 *
	 * @param string $stage Stage.
	 *
	 * @return void
	 */
	public function init( $stage ) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		if ( $this->isActive() ) {
			add_action( 'init', array( $this, 'math_captcha_comments' ), 10 );
			add_action( 'init', array( $this, 'math_captcha_registration' ), 10 );
			add_action( 'init', array( $this, 'math_captcha_lost_password' ), 10 );
			add_action( 'init', array( $this, 'math_captcha_login' ), 10 );
			add_action( 'init', array( $this, 'math_captcha_bbpress' ), 10 );
			add_action( 'rest_api_init', array(  $this, 'register_rest_routes' ) );
		}
	}

	/**
	 * Register rest endpoint
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'nitropack',
			'/math_captcha',
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => [ $this, 'add_captcha_form_ajax' ],
			]
		);
	}

	/**
	 * Math Captcha Comments
	 *
	 * @return void
	 */
	public function math_captcha_comments() {

		if ( is_admin() ) {
			return;
		}

		if ( Math_Captcha()->options['general']['enable_for']['comment_form'] ) {

			// Check IP rules.
			if ( Math_Captcha()->options['general']['ip_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( $geo->checkIP_in_List( false, Math_Captcha()->options['general']['ip_rules_list'] ) ) {
					return; // Dont show captcha.
				}
			}
			// Check GEO rules.
			if ( Math_Captcha()->options['general']['geo_captcha_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( isset( Math_Captcha()->options['general']['hide_for_countries'][ $geo->getCountryByIP( false ) ] ) ) {
					return; // Dont show captcha.
				}
			}

			if ( ! is_user_logged_in() ) {
				remove_class_action( 'comment_form_after_fields', 'Math_Captcha_Core', 'add_captcha_form' );
				add_action( 'comment_form_after_fields', [ $this, 'add_captcha_form' ] );
			} elseif ( ! Math_Captcha()->options['general']['hide_for_logged_users'] ) {
				remove_class_action( 'comment_form_logged_in_after', 'Math_Captcha_Core', 'add_captcha_form' );
				add_action( 'comment_form_logged_in_after', [ $this, 'add_captcha_form' ] );
			}
		}
	}

	/**
	 * Math Captcha Registration
	 *
	 * @return void
	 */
	public function math_captcha_registration() {

		if ( is_admin() ) {
			return;
		}

		$action = isset( $_GET['action'] ) && '' !== $_GET['action'] ? sanitize_text_field(wp_unslash($_GET['action'])) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// registration.
		if ( Math_Captcha()->options['general']['enable_for']['registration_form'] && ( ! is_user_logged_in() || ( is_user_logged_in() && ! Math_Captcha()->options['general']['hide_for_logged_users'] ) ) && 'register' === $action ) {

			// Check IP rules.
			if ( Math_Captcha()->options['general']['ip_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( $geo->checkIP_in_List( false, Math_Captcha()->options['general']['ip_rules_list'] ) ) {
					return; // Dont show captcha.
				}
			}
			// Check GEO rules.
			if ( Math_Captcha()->options['general']['geo_captcha_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( isset( Math_Captcha()->options['general']['hide_for_countries'][ $geo->getCountryByIP( false ) ] ) ) {
					return; // Dont show captcha.
				}
			}

			remove_class_action( 'register_form', 'Math_Captcha_Core', 'add_captcha_form' );
			remove_class_action( 'signup_extra_fields', 'Math_Captcha_Core', 'add_captcha_form' );
			add_action( 'register_form', [ $this, 'add_captcha_form' ] );
			add_action( 'signup_extra_fields', [ $this, 'add_captcha_form' ] );
		}
	}

	/**
	 * Math Captcha Lost Password
	 *
	 * @return void
	 */
	public function math_captcha_lost_password() {

		if ( is_admin() ) {
			return;
		}

		$action = isset( $_GET['action'] ) && '' !== $_GET['action'] ? sanitize_text_field(wp_unslash($_GET['action'])) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// lost password.
		if ( Math_Captcha()->options['general']['enable_for']['reset_password_form'] && ( ! is_user_logged_in() || ( is_user_logged_in() && ! Math_Captcha()->options['general']['hide_for_logged_users'] ) ) && 'lostpassword' === $action ) {
			// Check IP rules.
			if ( Math_Captcha()->options['general']['ip_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( $geo->checkIP_in_List( false, Math_Captcha()->options['general']['ip_rules_list'] ) ) {
					return; // Dont show captcha.
				}
			}
			// Check GEO rules.
			if ( Math_Captcha()->options['general']['geo_captcha_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( isset( Math_Captcha()->options['general']['hide_for_countries'][ $geo->getCountryByIP( false ) ] ) ) {
					return; // Dont show captcha.
				}
			}

			remove_class_action( 'lostpassword_form', 'Math_Captcha_Core', 'add_captcha_form' );
			add_action( 'lostpassword_form', [ $this, 'add_captcha_form' ] );
		}
	}

	/**
	 * Math Captcha Login
	 *
	 * @return void
	 */
	public function math_captcha_login() {

		if ( is_admin() ) {
			return;
		}

		$action = isset( $_GET['action'] ) && '' !== $_GET['action'] ? sanitize_text_field(wp_unslash($_GET['action'])) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// login.
		if ( Math_Captcha()->options['general']['enable_for']['login_form'] && ( ! is_user_logged_in() || ( is_user_logged_in() && ! Math_Captcha()->options['general']['hide_for_logged_users'] ) ) && null === $action ) {
			// Check IP rules.
			if ( Math_Captcha()->options['general']['ip_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( $geo->checkIP_in_List( false, Math_Captcha()->options['general']['ip_rules_list'] ) ) {
					return; // Dont show captcha.
				}
			}
			// Check GEO rules.
			if ( Math_Captcha()->options['general']['geo_captcha_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( isset( Math_Captcha()->options['general']['hide_for_countries'][ $geo->getCountryByIP( false ) ] ) ) {
					return; // Dont show captcha.
				}
			}

			remove_class_action( 'login_form', 'Math_Captcha_Core', 'add_captcha_form' );
			add_action( 'login_form', [ $this, 'add_captcha_form' ] );
		}
	}

	/**
	 * Math Captcha BBPress
	 *
	 * @return void
	 */
	public function math_captcha_bbpress() {

		if ( is_admin() ) {
			return;
		}

		// bbPress.
		if ( Math_Captcha()->options['general']['enable_for']['bbpress'] && class_exists( 'bbPress' ) && ( ! is_user_logged_in() || ( is_user_logged_in() && ! Math_Captcha()->options['general']['hide_for_logged_users'] ) ) ) {
			// Check IP rules.
			if ( Math_Captcha()->options['general']['ip_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( $geo->checkIP_in_List( false, Math_Captcha()->options['general']['ip_rules_list'] ) ) {
					return; // Dont show captcha.
				}
			}
			// Check GEO rules.
			if ( Math_Captcha()->options['general']['geo_captcha_rules'] ) {
				$geo = new MathCaptcha_GEO();
				if ( isset( Math_Captcha()->options['general']['hide_for_countries'][ $geo->getCountryByIP( false ) ] ) ) {
					return; // Dont show captcha.
				}
			}

			remove_class_action( 'bbp_theme_after_reply_form_content', 'Math_Captcha_Core', 'add_bbp_captcha_form' );
			remove_class_action( 'bbp_theme_after_topic_form_content', 'Math_Captcha_Core', 'add_bbp_captcha_form' );
			add_action( 'bbp_theme_after_reply_form_content', [ $this, 'add_bbp_captcha_form' ] );
			add_action( 'bbp_theme_after_topic_form_content', [ $this, 'add_bbp_captcha_form' ] );
		}
	}

	/**
	 * Math Captcha CF7
	 *
	 * @return void
	 */
	public function math_captcha_cf7() {

		if ( is_admin() ) {
			return;
		}
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'nitropack-math-captcha-ajax-script', NITROPACK_PLUGIN_DIR_URL . 'view/javascript/math_captcha.js?np_v=' . NITROPACK_VERSION, array( 'jquery' ), NITROPACK_VERSION, true );

		$vars = [
			'root'  => esc_url_raw( untrailingslashit( rest_url() ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		];

		wp_localize_script( 'nitropack-math-captcha-ajax-script', 'nitropack_math_captcha_ajax', $vars );
	}

	/**
	 * Display and generate captcha.
	 *
	 * @return mixed
	 */
	public function add_captcha_form() {

		$this->enqueue_scripts();

		?>
		<div class="nitropack_math_captcha" data-form-type="default"><img src="<?php echo esc_url( NITROPACK_PLUGIN_DIR_URL . 'view/images/loading.gif' ); ?>" alt="loading" /></div>
		<?php
	}

	/**
	 * Display and generate div container
	 *
	 * @return void
	 */
	public function add_bbp_captcha_form() {

		$this->enqueue_scripts();

		?>
		<div class="nitropack_math_captcha" data-form-type="bbpress"><img src="<?php echo esc_url( NITROPACK_PLUGIN_DIR_URL . 'view/images/loading.gif' ); ?>" alt="loading" /></div>
		<?php
	}

	/**
	 * Display and generate captcha.
	 *
	 * @param WP_REST_Request $request  The request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_captcha_form_ajax( WP_REST_Request $request ) {

		if ( ! wp_verify_nonce( sanitize_text_field( $request->get_header( 'X-WP-Nonce' ) ), 'wp_rest' ) ) {
			return new WP_Error( 'invalid_request', __( 'Invalid request.', 'nitropack' ) );
		}

		$form_type = isset( $_GET['form-type'] ) ? sanitize_text_field( wp_unslash( $_GET['form-type'] ) ) : 'default';

		ob_start();

		$captcha_title = apply_filters( 'math_captcha_title', Math_Captcha()->options['general']['title'] );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '
		<p class="math-captcha-form">';
		if ( ! empty( $captcha_title ) ) {
			echo '
			<label>' . esc_html($captcha_title) . '<br/></label>';
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<span>' . ( new \Math_Captcha_Core() )->generate_captcha_phrase( $form_type ) . '</span></p>';

		$html = ob_get_clean();

		$response = array_merge(
			array( 'html' => $html ),
			array( 'code' => 'ok' )
		);

		return new WP_REST_Response( $response, 200 );
	}
}
