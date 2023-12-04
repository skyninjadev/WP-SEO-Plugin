<?php

namespace NitroPack\Integration\Plugin;

class Ezoic {
    const STAGE = NULL; // Don't run init

    public static function isActive() {
        return defined('EZOIC_INTEGRATION_VERSION');
    }

    public static function disable() {
        global $wp_filter;
        $hook = "shutdown";

        if ( isset( $wp_filter[$hook]->callbacks ) ) {      
            array_walk( $wp_filter[$hook]->callbacks, function( $callbacks, $priority ) use ( &$hooks ) {           
                foreach ( $callbacks as $id => $callback ) {
                    $cb = $callback["function"];
                    if (is_callable($cb) && is_array($cb) && $cb[1] == "ez_buffer_end") {
                        remove_filter("shutdown", $cb, $priority);
                        register_shutdown_function('ob_end_flush');
                    }
                }
            });         
        }
    }

    public static function getHomeUrl($url) {
        $siteConfig = nitropack_get_site_config();
        if ( $siteConfig && null !== $nitro = get_nitropack_sdk($siteConfig["siteId"], $siteConfig["siteSecret"]) ) {
            $nitroUrl = $nitro->getUrl();
            $queryStart = strpos($nitroUrl, "?");
            if ($queryStart !== false) {
                return substr($nitroUrl, 0, $queryStart);
            } else {
                return $nitroUrl;
            }
        }

        return $url;
    }
}

