<?php

defined( 'ABSPATH' ) or die( 'Someone made a boo boo!' );

use \NitroPack\SDK\Api\ResponseStatus;

if (!function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$np_diag_functions = array(
    'general-info-status' => 'npdiag_get_general_info',
    'active-plugins-status' => 'npdiag_get_active_plugins',
    'conflicting-plugins-status' => 'npdiag_get_conflicting_plugins',
    'user-config-status' => 'npdiag_get_user_config',
    'dir-info-status' => 'npdiag_get_dir_info',
    'getexternalcache' => 'npdiag_detect_third_party_cache'

);

function npdiag_helper_trailingslashit($string) {
    return rtrim( $string, '/\\' ) . '/';
}

function npdiag_compare_webhooks($nitro_sdk) {
    try {
        $siteConfig = nitropack_get_site_config();
        if (!empty($siteConfig['siteId'])) { 
            $WHToken = nitropack_generate_webhook_token($siteConfig['siteId']);
            $constructedWH = new \NitroPack\Url\Url(strtolower(get_home_url())) . '?nitroWebhook=config&token=' . $WHToken;
            $storedWH = $nitro_sdk->getApi()->getWebhook("config");
            $matchResult = ($constructedWH == $storedWH) ? __( 'OK', 'nitropack' ) : __( 'Warning: Webhooks do not match this site', 'nitropack' );
        } else {
            $debugMsg = empty($_SERVER["HTTP_HOST"]) ? "HTTP_HOST is not defined. " : "";
            $debugMsg .= empty($_SERVER["REQUEST_URI"]) ? "REQUEST_URI is not defined. " : "";
            $debugMsg .= empty($debugMsg) ? 'URL used to match config was: ' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] : "";
            $matchResult = __( 'Site config cannot be found, because ', 'nitropack' ) . $debugMsg;
        }
        return $matchResult;
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function npdiag_poll_api($nitro_sdk) {
    $pollResult = array(
        ResponseStatus::OK => __( 'OK', 'nitropack' ),
        ResponseStatus::ACCEPTED => __( 'OK', 'nitropack' ),
        ResponseStatus::BAD_REQUEST => __( 'Bad request.', 'nitropack' ),
        ResponseStatus::PAYMENT_REQUIRED => __( 'Payment required. Please, contact NP support for details.', 'nitropack' ),
        ResponseStatus::FORBIDDEN => __( 'Site disabled. Please, contact NP support for details.', 'nitropack' ),
        ResponseStatus::NOT_FOUND => __( 'URL used for the API poll request returned 404. Please ignore this.', 'nitropack' ),
        ResponseStatus::CONFLICT => __( 'Conflict. There is another operation, which prevents accepting optimization requests at the moment. Please, contact NP support for details.', 'nitropack' ),
        ResponseStatus::RUNTIME_ERROR => __( 'Runtime error.', 'nitropack' ),
        ResponseStatus::SERVICE_UNAVAILABLE => __( 'Service unavailable.', 'nitropack' ),
        ResponseStatus::UNKNOWN => __( 'Unknown.', 'nitropack' )
    );

    try {
		$referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';
        $apiResponseCode = $nitro_sdk->getApi()->getCache(get_home_url(), __( 'NitroPack Diagnostic Agent', 'nitropack' ), array(), false, 'default', $referer)->getStatus();
        return $pollResult[$apiResponseCode];
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }

}

function npdiag_backlog_status($nitro_sdk) {
        return $nitro_sdk->backlog->exists() ? 'Warning' : 'OK';
}

function npdiag_get_general_info() {
    global $wp_version;
    if (null !== $nitro = get_nitropack_sdk()) {
        $probe_result = "OK";
        try {		
            $nitro->fetchConfig();
        } catch (\Exception $e) {
            $probe_result = __( 'Error: ', 'nitropack' ) . $e->getMessage();
        }
    } else {
        $probe_result = __( 'Error: Cannot get an SDK instance', 'nitropack' );
    }

    $third_party_residual_cache = npdiag_detect_third_party_cache();

    $info = array(
        'Nitro_WP_version' => !empty($wp_version) ? $wp_version : get_bloginfo('version'),
        'Nitro_Version' => defined('NITROPACK_VERSION') ? NITROPACK_VERSION : __( 'Undefined', 'nitropack' ),
        'Nitro_SDK_Connection' => $probe_result,
        'Nitro_API_Polling' => $nitro ? npdiag_poll_api($nitro) : __( 'Error: Cannot get an SDK instance', 'nitropack' ),
        'Nitro_SDK_Version' => defined('NitroPack\SDK\Nitropack::VERSION') ? NitroPack\SDK\Nitropack::VERSION : __( 'Undefined', 'nitropack' ),
        'Nitro_WP_Cache' => defined('WP_CACHE') ? (WP_CACHE ? __( 'OK for drop-in', 'nitropack' ) : __( 'Turned off', 'nitropack' )) : __( 'Undefined', 'nitropack' ),
        'Advanced_Cache_Version' => defined('NITROPACK_ADVANCED_CACHE_VERSION') ? NITROPACK_ADVANCED_CACHE_VERSION : __( 'Undefined', 'nitropack' ),
        'Nitro_Absolute_Path' => defined('ABSPATH') ? ABSPATH : __( 'Undefined', 'nitropack' ),
        'Nitro_Plugin_Directory' => defined('NITROPACK_PLUGIN_DIR') ? NITROPACK_PLUGIN_DIR : dirname(__FILE__),
        'Nitro_Data_Directory' => defined('NITROPACK_DATA_DIR') ? NITROPACK_DATA_DIR : __( 'Undefined', 'nitropack' ),
        'Nitro_Config_File' => defined('NITROPACK_CONFIG_FILE') ? NITROPACK_CONFIG_FILE : __( 'Undefined', 'nitropack' ),
        'Nitro_Backlog_File_Status' => $nitro ? npdiag_backlog_status($nitro) : __( 'Error: Cannot get an SDK instance', 'nitropack' ),
        'Nitro_Webhooks' => $nitro ? npdiag_compare_webhooks($nitro) : __( 'Error: Cannot get an SDK instance', 'nitropack' ),
        'Nitro_Connectivity_Requirements' => nitropack_check_func_availability('stream_socket_client') ? __( 'OK', 'nitropack' ) : __( 'Warning: "stream_socket_client" function is disabled.', 'nitropack' ),
        'Residual_Cache_Found_For' => $third_party_residual_cache,
    );

    if (defined("NITROPACK_VERSION") && defined("NITROPACK_ADVANCED_CACHE_VERSION") && NITROPACK_VERSION == NITROPACK_ADVANCED_CACHE_VERSION && nitropack_is_dropin_cache_allowed()) {
        $info['Nitro_Cache_Method'] = 'drop-in';
    } elseif ( defined('EZOIC_INTEGRATION_VERSION') ) {
        $info['Nitro_Cache_Method'] = 'plugin-ezoic';
    } else {
        $info['Nitro_Cache_Method'] = 'plugin';
    }

    return $info;
}

function npdiag_get_active_plugins() {
    if (is_admin()) {
        $info = array();
        $raw_installed_list = get_plugins();
        $raw_active_list = get_option('active_plugins');
        foreach ($raw_installed_list as $pkey => $pval) {
            if ( in_array($pkey, $raw_active_list) ) {
                $info[$pval['Name']] = $pval['Version'];
            }
        }
    }

    return $info;
}

function npdiag_get_user_config() {
    if (defined('NITROPACK_CONFIG_FILE')) {
        if (file_exists(NITROPACK_CONFIG_FILE)) {
            $info = json_decode(file_get_contents(NITROPACK_CONFIG_FILE));
            if (!$info) {
                $info = __( 'Config found, but unable to get contents.', 'nitropack' );
            }
        } else {
            $info = __( 'Config file not found.', 'nitropack' );
        }
    } else {
        $info = __( 'Config file constant is not defined.', 'nitropack' );
    }
    
    return $info;
}

function npdiag_get_dir_info() {
    $siteConfig = nitropack_get_site_config();
    $siteID = $siteConfig['siteId'];
    // DoI = Directories of Interest
    $DoI = array(
        'WP_Content_Dir_Writable' => defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : (defined('ABSPATH') ? ABSPATH . '/wp-content' : __( 'Undefined', 'nitropack' )),
        'Nitro_Data_Dir_Writable' => defined('NITROPACK_DATA_DIR') ? NITROPACK_DATA_DIR : npdiag_helper_trailingslashit(WP_CONTENT_DIR) . 'nitropack',
        'Nitro_siteID_Dir_Writable' => defined('NITROPACK_DATA_DIR') ? NITROPACK_DATA_DIR . "/$siteID" : npdiag_helper_trailingslashit(WP_CONTENT_DIR) . "nitropack/$siteID",				 
        'Nitro_Plugin_Dir_Writable' => defined('NITROPACK_PLUGIN_DIR') ? NITROPACK_PLUGIN_DIR : dirname(__FILE__)
    ); 

    $info = array();
    foreach ($DoI as $doi_dir => $dpath) {
        if (is_dir($dpath)) {
            $info[$doi_dir] = is_writeable($dpath) ? true : false;
        } else if (is_file($dpath)) {
            $info[$doi_dir] = $dpath. __( ' is a file not a directory', 'nitropack' );
        } else {
            $info[$doi_dir] =  __( 'Directory not found', 'nitropack' );
        }
    }

    return $info;
}

function npdiag_get_conflicting_plugins() {
    $info = nitropack_get_conflicting_plugins();
    if ( !empty($info) ) {
        return $info;
    } else {
        return $info = __( 'None detected', 'nitropack' );
    }
}

function npdiag_detect_third_party_cache() {
    $info = \NitroPack\Integration\Plugin\RC::detectThirdPartyCaches();
    if ( !empty($info) ) {
        return $info;
    } else {
        return $info = __( 'Not found', 'nitropack' );
    }
}

function nitropack_generate_report() {
    global $np_diag_functions;
    nitropack_verify_ajax_nonce($_REQUEST);
    try {
        $ar = !empty($_POST["toggled"]) ? $_POST["toggled"] : NULL;		
        if ($ar !== NULL) {
            $diag_data = array('report-time-stamp' => date("Y-m-d H:i:s"));
            foreach ($ar as $func_name => $func_allowed) {			
                if ((boolean)$func_allowed) {
                    $diag_data[$func_name] = call_user_func($np_diag_functions[$func_name]);
                }
            }
            $str = json_encode($diag_data, JSON_PRETTY_PRINT);
            $filename = 'nitropack_diag_file.txt';
            nitropack_header('Content-Disposition: attachment; filename="'.$filename.'"');
            nitropack_header("Content-Type: text/plain");
            nitropack_header("Content-Length: " . strlen($str));
            echo $str;
            exit;
        }
    } catch (\Exception $e) {
        //exception handling here
    }

}
