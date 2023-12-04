<?php

namespace NitroPack\Integration\Plugin;

class WPRocket extends RC {

    private static $cpath = ['/wp-rocket/' => false,]; //We are only interested in the page cache (other cache dirs '/busting/', '/critical-css/', '/min/')

    public static function hasResidualCache() {
        $wpc_dir = self::getWPCacheDir();
        $curr_domain = self::getCurrentDomain();
        if ($wpc_dir && $curr_domain) {
            $prefix = defined('WP_ROCKET_CACHE_ROOT_PATH') ? nitropack_trailingslashit(WP_ROCKET_CACHE_ROOT_PATH) : $wpc_dir;
            foreach (self::$cpath as $cp => $recursive_scan) {
                $fcp = $prefix . $cp;
                if ($cp === '/wp-rocket/') {
                    $fcp = $prefix . $cp . $curr_domain;
                }
                return self::dirHasContents($fcp, $recursive_scan);
            }
        }
        return false;
    }

    public static function clearCache() {
        $wpc_dir = self::getWPCacheDir();
        $curr_domain = self::getCurrentDomain();
        $result = array();
        if ($wpc_dir && $curr_domain) {
            $prefix = defined('WP_ROCKET_CACHE_ROOT_PATH') ? nitropack_trailingslashit(WP_ROCKET_CACHE_ROOT_PATH) : $wpc_dir;
            foreach (self::$cpath as $cp => $recursive_scan) {
                $fcp = $prefix . $cp;
                if ($cp === '/wp-rocket/') {
                    $fcp = $prefix . $cp . $curr_domain;
                }
                $result[] = self::clearResidualCache($fcp);
            }
        }
        return $result;
    }
}
