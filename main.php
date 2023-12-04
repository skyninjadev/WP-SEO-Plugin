<?php
/*
Plugin Name:  NitroPack
Plugin URI:   https://nitropack.io/platform/wordpress
Description:  Automatic optimization for site speed and Core Web Vitals. Use 35+ features, including Caching, image optimization, critical CSS, and Cloudflare CDN.
Version:      1.10.4
Author:       NitroPack Inc.
Author URI:   https://nitropack.io/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  nitropack
Domain Path:  /languages
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$np_basePath = dirname(__FILE__) . '/';
require_once $np_basePath . 'functions.php';
require_once $np_basePath . 'helpers.php';
require_once $np_basePath . 'diagnostics.php';

if (nitropack_is_wp_cli()) {
    require_once $np_basePath . 'wp-cli.php';
}

if ( \NitroPack\Integration\Plugin\Ezoic::isActive() ) {
    if (!nitropack_is_optimizer_request()) {
        // We need to serve the cached content after Ezoic's output buffering has started at plugins_loaded,0
        add_action( 'plugins_loaded', function() {
            add_filter( 'home_url', ['\NitroPack\Integration\Plugin\Ezoic', 'getHomeUrl'] );
            nitropack_handle_request("plugin-ezoic");
            remove_filter( 'home_url', ['\NitroPack\Integration\Plugin\Ezoic', 'getHomeUrl'] );
        }, 1 );
    } else {
        add_action( 'plugins_loaded', ['\NitroPack\Integration\Plugin\Ezoic', 'disable'], 1);
    }
} else {
    nitropack_handle_request("plugin");
}

add_filter( 'nitro_script_output', function($script) {
    $canPrintScripts = !nitropack_is_amp_page() // Make sure we don't accidentally print a non-amp compatible script to an amp page
        && (!isset($_SERVER['HTTP_SEC_FETCH_DEST']) || $_SERVER['HTTP_SEC_FETCH_DEST'] === 'document') 
        && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest');
    if ($canPrintScripts) {
        return $script;
    } else {
        return "";
    }
});
add_action( 'pre_post_update', 'nitropack_log_post_pre_update', 10, 3);
add_filter( 'woocommerce_rest_pre_insert_product_object', 'nitropack_log_product_pre_api_update', 10, 3 );
// add_action( 'clean_post_cache', 'nitropack_post_updated', 10, 3);
// add_action( 'post_updated', 'nitropack_post_updated', 10, 3);
// add_action( 'save_post', 'nitropack_post_updated', 10, 3);
// add_action( 'edited_term_taxonomy', 'nitropack_post_updated', 10, 3);
// add_action( 'woocommerce_update_product', 'nitropack_postmeta_updated', 10, 4);
add_action( 'updated_postmeta', 'nitropack_postmeta_updated', 10, 4);
add_action( 'set_object_terms', 'nitropack_sot', 10, 6);
add_action( 'transition_post_status', 'nitropack_handle_post_transition', 10, 3);
add_action( 'publish_post', 'nitropack_handle_first_publish', 10, 1);
add_action( 'transition_comment_status', 'nitropack_handle_comment_transition', 10, 3);
add_action( 'comment_post', 'nitropack_handle_comment_post', 10, 2);
add_action( 'switch_theme', 'nitropack_theme_handler' );
register_shutdown_function('nitropack_execute_purges');
register_shutdown_function('nitropack_execute_invalidations');
register_shutdown_function('nitropack_execute_warmups');

add_action( 'woocommerce_product_object_updated_props', 'nitropack_handle_product_updates', 0, 2);
add_action( 'woocommerce_rest_insert_product', function($post, $request, $creating) {
    if (!$creating) {
        nitropack_detect_changes_and_clean_post_cache($post);
    }
}, 10, 3);
add_action( 'woocommerce_rest_insert_product_object', function($product, $request, $creating) {
    if (!$creating) {

        $post = get_post($product->get_id());
        nitropack_detect_changes_and_clean_post_cache($post);
    }
}, 10, 3);

add_action('wcml_set_client_currency', function($currency) {
    setcookie('np_wc_currency', $currency, time() + (86400 * 7), "/");
});

if (nitropack_has_advanced_cache()) {
    // Handle automated updates
    if (!defined("NITROPACK_ADVANCED_CACHE_VERSION") || NITROPACK_VERSION != NITROPACK_ADVANCED_CACHE_VERSION) {
        add_action( 'plugins_loaded', 'nitropack_install_advanced_cache' );
    }
}

add_action('wp_footer', 'nitropack_print_heartbeat_script');
add_action('admin_footer', 'nitropack_print_heartbeat_script');
add_action('get_footer', 'nitropack_print_heartbeat_script');

add_action('wp_footer', 'nitropack_print_cookie_handler_script');
add_action('admin_footer', 'nitropack_print_cookie_handler_script');
add_action('admin_footer', function() {
    nitropack_setcookie("nitroCachedPage", 0, time() - 86400);
}); // Clear the nitroCachePage cookie
add_action('get_footer', 'nitropack_print_cookie_handler_script');

if ( is_admin() ) {
    add_action( 'admin_menu', 'nitropack_menu' );
    add_action( 'admin_init', 'register_nitropack_settings' );
    add_action( 'admin_notices', 'nitropack_admin_notices' );
    add_action( 'network_admin_notices', 'nitropack_admin_notices' );
    add_action( 'wp_ajax_nitropack_purge_cache', 'nitropack_purge_cache' );
    add_action( 'wp_ajax_nitropack_invalidate_cache', 'nitropack_invalidate_cache' );
    add_action( 'wp_ajax_nitropack_clear_residual_cache', 'nitropack_clear_residual_cache' );
    add_action( 'wp_ajax_nitropack_verify_connect', 'nitropack_verify_connect_ajax' );
    add_action( 'wp_ajax_nitropack_disconnect', 'nitropack_disconnect' );
    add_action( 'wp_ajax_nitropack_test_compression_ajax', 'nitropack_test_compression_ajax' );
    add_action( 'wp_ajax_nitropack_set_compression_ajax', 'nitropack_set_compression_ajax' );
    add_action( 'wp_ajax_nitropack_set_auto_cache_purge_ajax', 'nitropack_set_auto_cache_purge_ajax' );
    add_action( 'wp_ajax_nitropack_set_cart_cache_ajax', 'nitropack_set_cart_cache_ajax' );
    add_action( 'wp_ajax_nitropack_set_bb_cache_purge_sync_ajax', 'nitropack_set_bb_cache_purge_sync_ajax' );
    add_action( 'wp_ajax_nitropack_set_legacy_purge_ajax', 'nitropack_set_legacy_purge_ajax' );
    add_action( 'wp_ajax_nitropack_set_cacheable_post_types', 'nitropack_set_cacheable_post_types' );
    add_action( 'wp_ajax_nitropack_enable_warmup', 'nitropack_enable_warmup' );
    add_action( 'wp_ajax_nitropack_disable_warmup', 'nitropack_disable_warmup' );
    add_action( 'wp_ajax_nitropack_warmup_stats', 'nitropack_warmup_stats' );
    add_action( 'wp_ajax_nitropack_estimate_warmup', 'nitropack_estimate_warmup' );
    add_action( 'wp_ajax_nitropack_run_warmup', 'nitropack_run_warmup' );
    add_action( 'wp_ajax_nitropack_purge_single_cache', 'nitropack_purge_single_cache' );
    add_action( 'wp_ajax_nitropack_invalidate_single_cache', 'nitropack_invalidate_single_cache' );
    add_action( 'wp_ajax_nitropack_purge_entire_cache', 'nitropack_purge_entire_cache' );
    add_action( 'wp_ajax_nitropack_dismiss_hosting_notice', 'nitropack_dismiss_hosting_notice' );
    add_action( 'wp_ajax_nitropack_dismiss_woocommerce_notice', 'nitropack_dismiss_woocommerce_notice' );
    add_action( 'wp_ajax_nitropack_reconfigure_webhooks', 'nitropack_reconfigure_webhooks' );
    add_action( 'wp_ajax_nitropack_generate_report', 'nitropack_generate_report' );//diag_ajax_hook
    add_action( 'wp_ajax_nitropack_enable_safemode', 'nitropack_enable_safemode' );
    add_action( 'wp_ajax_nitropack_disable_safemode', 'nitropack_disable_safemode' );
    add_action( 'wp_ajax_nitropack_safemode_status', 'nitropack_safemode_status' );
    add_action( 'wp_ajax_nitropack_rml_notification', 'nitropack_rml_notification' );
    add_action( 'activated_plugin', 'nitropack_upgrade_handler' );
    add_action( 'deactivated_plugin', 'nitropack_upgrade_handler' );
    add_action( 'upgrader_process_complete', 'nitropack_upgrade_handler');
    add_action( 'update_option_nitropack-enableCompression', 'nitropack_handle_compression_toggle', 10, 2 );
    add_action( 'add_meta_boxes', 'nitropack_add_meta_box' );
    add_action( 'plugins_loaded', 'nitropack_offer_safemode');

	add_filter('get_nitropack_notifications', 'nitropack_ignore_dismissed_notifications', 10, 2);

    register_activation_hook( __FILE__, 'nitropack_activate' );
    register_deactivation_hook( __FILE__, 'nitropack_deactivate' );
} else {
    if (null !== $nitro = get_nitropack_sdk()) {
        $GLOBALS["NitroPack.instance"] = $nitro;
        if (get_option('nitropack-enableCompression') == 1) {
            $nitro->enableCompression();
        }
        add_action( 'wp', 'nitropack_init' );
    }
}

function nitropack_menu() {
    add_options_page( 'NitroPack Options', 'NitroPack', 'manage_options', 'nitropack', 'nitropack_options' );
    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'nitropack_action_links' );
}

function nitropack_action_links ( $links ) {
    $nitroLinks = array(
        '<a href="https://support.nitropack.io/hc/en-us/categories/360005122034-Frequently-Asked-Questions-FAQs-" target="_blank" rel="noopener noreferrer">FAQ</a>',
        '<a href="https://support.nitropack.io/hc/en-us" target="_blank" rel="noopener noreferrer">Docs</a>',
        '<a href="https://support.nitropack.io/hc/en-us/requests/new" target="_blank" rel="noopener noreferrer">Support</a>',
    );

    if (get_nitropack()->getDistribution() == "oneclick") {
        $nitroLinks = apply_filters("nitropack_oneclick_action_links", $nitroLinks);
    }
    
    array_unshift($nitroLinks, '<a href="' . admin_url( 'options-general.php?page=nitropack' ) . '" rel="noopener noreferrer">Settings</a>');

    return array_merge( $nitroLinks, $links );
}

add_action( 'init', function() {
    if (current_user_can( 'manage_options' )) {

        // Enqueue font awesome
        add_action( 'wp_enqueue_scripts', 'nitropack_enqueue_load_fa');
        add_action( 'admin_enqueue_scripts', 'nitropack_enqueue_load_fa');

        // Enqueue admin bar menu custom stylesheet
        add_action( 'wp_enqueue_scripts', 'enqueue_nitropack_admin_bar_menu_stylesheet');
        add_action( 'admin_enqueue_scripts', 'enqueue_nitropack_admin_bar_menu_stylesheet');

        // Enqueue admin menu custom javascript
        add_action( 'wp_enqueue_scripts', 'nitropack_admin_bar_script' );
	    add_action( 'admin_enqueue_scripts', 'nitropack_admin_bar_script' );

        // Add our admin menu bar entry
        add_action('admin_bar_menu', 'nitropack_admin_bar_menu', PHP_INT_MAX - 10 );
        add_action('plugins_loaded', 'nitropack_plugin_notices'); // Run the checks early, because we need to set some headers. The results from the checks will be cached, so future calls will work as expected.

	    add_action( 'updated_option', 'nitropack_updated_option', ~PHP_INT_MAX, 3 );

        \NitroPack\PluginStateHandler::init();

        add_action( 'admin_enqueue_scripts', function() {
            wp_enqueue_script('nitropack_notices_js', plugin_dir_url(__FILE__) . 'view/javascript/np_notices.js?np_v=' . NITROPACK_VERSION);
        });

	    add_action('in_admin_header', function() {
		    $screen = get_current_screen();
		    if ($screen->id === 'settings_page_nitropack') {
			    remove_all_actions( 'user_admin_notices' );
			    remove_all_actions( 'admin_notices' );
			    remove_all_actions( 'all_admin_notices' );
		    }
	    }, 99);
    }
});

/**
 * Load text domain for translations
 *
 * @return void
 */
function nitropack_load_textdomain() {
	global $l10n;

	$domain = 'nitropack';

	if ( isset( $l10n[ $domain ] ) ) {
		return;
	}

	load_plugin_textdomain( $domain, false, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded','nitropack_load_textdomain' );
