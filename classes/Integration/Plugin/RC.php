<?php

namespace NitroPack\Integration\Plugin;

class RC {
    public static $modules = [//Key should match the value from nitropack_get_conflicting_plugins() assigned to the $clashingPlugins array
        'WP-Rocket' => 'NitroPack\Integration\Plugin\WPRocket',
    ];

    public static function clearCache() {}

    public static function dirHasContents($dir_path, $recursive_scan) {
        $exclude_list = ['.', '..', '.htaccess', 'index.html'];
        if (is_dir($dir_path)) {
            $dir_path = nitropack_trailingslashit($dir_path);
            $dir_contents = scandir($dir_path);
            foreach ($dir_contents as $current_item) {
                if (!in_array($current_item, $exclude_list)) {
                    $current_item_path = nitropack_trailingslashit($dir_path.$current_item);
                    if (is_dir($current_item_path)) {
                        if ($recursive_scan) {
                            if (self::dirHasContents($current_item_path, true)) return true;
                        } else {
                            return true;
                        }
                    } else {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function clearResidualCache($full_cache_path) {
        try {
            if (file_exists($full_cache_path)) {
                if (is_dir($full_cache_path) && is_writable($full_cache_path)) {
                    $diskStorage = new \NitroPack\SDK\StorageDriver\Disk();
                    $diskStorage->deleteDir($full_cache_path);
                    return true;
                }
                return false;
            }
        } catch (\Exception $e) {
            //TODO: Log the exception in a NP log
            return false;
        }
    }

    public static function getCurrentDomain() {
        $url = new \NitroPack\Url\Url(get_site_url());
        return $url ? $url->getHost() : NULL;
    }

    public static function getWPCacheDir() {
        $wpc_dir = nitropack_trailingslashit(defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : (defined('ABSPATH') ? ABSPATH . '/wp-content' : 'Undefined')) . 'cache';
        if (file_exists($wpc_dir)) {
            return $wpc_dir;
        }
        return false;
    }

    public static function isConflictingPluginActive($cp_name) {
        $active_cp = nitropack_get_conflicting_plugins();
        if (in_array($cp_name, $active_cp)) {
            return true;
        }
        return false;
    }

    public static function detectThirdPartyCaches() {
        $residual_cache = array();

        foreach (self::$modules as $module_name => $module) {
            if (!self::isConflictingPluginActive($module_name) && $module::hasResidualCache()) {
                $residual_cache[] = $module_name;
            }
        }
    
        return $residual_cache;
    }
}
