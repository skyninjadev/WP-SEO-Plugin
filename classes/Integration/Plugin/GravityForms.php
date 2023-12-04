<?php
/**
 * GravityForms Class
 *
 * @package nitropack
 */

namespace NitroPack\Integration\Plugin;

use WP_Block_Type_Registry;
use WP_block;

/**
 * GravityForms Class
 */
class GravityForms {

	const STAGE = 'late';


	/**
	 * Check if plugin "Gravity Forms" is active
	 *
	 * @return bool
	 */
	public static function isActive() {     //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

		return class_exists( '\GFForms' ) ;
	}

	/**
	 * Initialize the integration
	 *
	 * @param string $stage Stage.
	 *
	 * @return void
	 */
	public function init( $stage ) {  //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		if ($this -> isActive()) {

			add_filter( 'template_redirect', [$this, 'output_filter']);

			if (!wp_doing_ajax() && !wp_is_json_request()) {
				add_filter( 'register_block_type_args', [$this, 'gf_block_type_args'], 999 ,2 );

				add_action( 'init', function() {
					remove_shortcode('gravityform');
					remove_shortcode('gravityforms');
					add_shortcode('gravityform', [$this, 'modify_gf_shortcode']);
					add_shortcode('gravityforms', [$this, 'modify_gf_shortcode']);
				}, 99);
			} else {
				add_action( 'wp_ajax_nitropack_gf_block_output_ajax', [$this, 'block_output_ajax'] );
				add_action( 'wp_ajax_nopriv_nitropack_gf_block_output_ajax', [$this, 'block_output_ajax'] );
				add_action( 'wp_ajax_nitropack_gf_shortcode_output_ajax', [$this, 'shortcode_output_ajax'] );
				add_action( 'wp_ajax_nopriv_nitropack_gf_shortcode_output_ajax', [$this, 'shortcode_output_ajax'] );
			}
		}
	}

	/**
	 * Filter for output content.
	 *
	 * @return void
	 */
	public function output_filter() {

		global $post;

		// Running only for single posts (any type) and pages.
		if ( ! is_singular() ) {
			return;
		}

		// Gravity Forms form detected? Enqueue scripts and exit.
		if ( false !== $this->check_gf( $post ) ) {

			wp_enqueue_script( 'nitropack-gf-ajax-script', NITROPACK_PLUGIN_DIR_URL . 'view/javascript/gravity_forms.js?np_v=' . NITROPACK_VERSION, array('jquery'), NITROPACK_VERSION, true );
			wp_localize_script( 'nitropack-gf-ajax-script', 'nitropack_gf_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			return;
		}
	}

	/**
	 * Check if post/page has a GF shortcode or block.
	 *
	 * @param object $post Post Object.
	 */
	public function check_gf( $post ) {

		// Check for GF shortcode.
		if ( true === $this->find_gf_shortcode( $post->post_content ) ) {
			return true;
		}

		// Check for a GF block or GF form in a reusable block.
		if ( function_exists( 'has_block' ) && true === has_blocks( $post->ID ) ) {

			// Check for GF blocks.
			if ( true === $this->find_gf_block( $post->post_content ) ) {
				return true;
			}

			// Additional check for GF forms in reusable blocks.
			$blocks = parse_blocks( $post->post_content );

			foreach ( $blocks as $block ) {

				// Skip block if empty or not a core/block.
				if ( empty( $block['blockName'] ) || 'core/block' !== $block['blockName'] || empty( $block['attrs']['ref'] ) ) {
					continue;
				}

				// Check core/block found.
				$reusable_block = get_post( $block['attrs']['ref'] );

				if ( empty( $reusable_block ) || 'wp_block' !== $reusable_block->post_type ) {
					continue;
				}

				if ( true === $this->find_gf_shortcode( $reusable_block->post_content ) || true === $this->find_gf_block( $reusable_block->post_content ) ) {
					return true;
				}
			}
		}

		// If we're here, no form was detected.
		return false;
	}

	/**
	 * Check post content provided for a GF shortcode.
	 *
	 * @param string $post_content      Post content.
	 */
	public function find_gf_shortcode( $post_content ) {

		// Check for a GF shortcode.
		if ( has_shortcode( $post_content, 'gravityform' ) || has_shortcode( $post_content, 'gravityforms')) {
			// Shortcode found!
			return true;
		}
		// If we're here, there's no GF shortcode.
		return false;
	}

	/**
	 * Check post content provided for a GF block.
	 *
	 * @param string $post_content      Post content.
	 */
	public function find_gf_block( $post_content ) {

		// Get GF blocks registered.
		$gf_blocks = $this -> get_block_registered_names_list();

		// Checking for GF blocks.
		foreach ( $gf_blocks as $gf_block ) {

			if ( has_block( $gf_block, $post_content ) ) {
				// Block found!
				return true;
			}
		}
		// If we're here, there's no GF block.
		return false;
	}

	/**
	 * Override gravity forms block render callback
	 *
	 * @param array $args Arguments for block.
	 * @param string $name Name of block.
	 *
	 * @return mixed
	 */
	public function gf_block_type_args( $args, $name ) {

		if ( strpos( $name, 'gravityforms/' ) !== false) {

			$args['render_callback'] = [$this, 'modify_gf_block'];
		}
		return $args;
	}

	/**
	 * Modify gravity forms block render callback
	 *
	 * @param array    $attributes The block attrbutes.
	 * @param string   $content    The block content.
	 * @param WP_block $block   The block object.
	 *
	 * @return mixed
	 */
	public function modify_gf_block($attributes, $content, WP_block $block = null) {

		return '<div class="nitropack-gravityforms-block" data-block-name="'.esc_attr($block -> name).'" data-block-attributes="'.esc_attr(json_encode($attributes)).'"><img src="'.esc_url(NITROPACK_PLUGIN_DIR_URL . 'view/images/loading.gif').'" alt="loading" /></div>';
	}

	/**
	 * Get an array of the names of all registered blocks of Gravity Forms
	 *
	 * @return array $pattern_names
	 */
	private function get_block_registered_names_list() {

		$get_patterns  = WP_Block_Type_Registry::get_instance()->get_all_registered();

		$pattern_names = [];

		if ($get_patterns) {
			foreach ($get_patterns as $pattern) {

				$block_name = ((array)$pattern)['name'];

				if (strpos($block_name, 'gravityforms/') !== false) {
					$pattern_names[] = $block_name;
				}
			}
		}

		return $pattern_names;
	}

	/**
	 * Output ajax for Gravity Forms block
	 *
	 * @return void
	 */
	public function block_output_ajax(){

		$block_name = isset($_GET['block_name']) ? sanitize_text_field(wp_unslash($_GET['block_name'])) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$block_attributes = isset($_GET['block_attributes']) ? sanitize_text_field(wp_unslash($_GET['block_attributes'])) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if (!empty($block_name) && !empty($block_attributes)) {

			$block_type = WP_Block_Type_Registry::get_instance()->get_registered( $block_name );

			if ( $block_type && !empty( $block_type ) ) {

				$block_attributes = json_decode($block_attributes, true);

				$block_attributes['ajax'] = 'true';

				$block_shortcode = $block_type -> render($block_attributes, '');

				echo do_shortcode( $block_shortcode);

			}
		}

		wp_die();
	}


	/**
	 * Override gravity forms shortcode render callback
	 *
	 * @param array $atts Attributes for shortcode.
	 * @param string $content Content of shortcode.
	 *
	 * @return string
	 */
	public function modify_gf_shortcode($atts, $content = null ) {

		return '<div class="nitropack-gravityforms-shortcode" data-shortcode-attributes="'.esc_attr(json_encode($atts)).'"><img src="'.esc_url(NITROPACK_PLUGIN_DIR_URL . 'view/images/loading.gif').'" alt="loading" /></div>';
	}

	/**
	 * Output ajax for Gravity Forms shortcode
	 *
	 * @return void
	 */
	public function shortcode_output_ajax(){

		$shortcode_attributes = isset($_GET['shortcode-attributes']) ? sanitize_text_field(wp_unslash($_GET['shortcode-attributes'])) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$shortcode_attributes = json_decode($shortcode_attributes, true);

		if (!empty($shortcode_attributes)) {

			$shortcode_attributes['ajax'] = 'true';

			$shortcode_attribute_string = implode(' ', array_map(fn($k, $v) => "$k=\"$v\"", array_keys($shortcode_attributes), $shortcode_attributes));

			echo do_shortcode( '[gravityform '.$shortcode_attribute_string.']');
		}

		wp_die();
	}
}
