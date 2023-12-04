<?php
/**
 * WPmudev Class
 *
 * @package nitropack
 */

namespace NitroPack\Integration\Hosting;

/**
 * WPmudev Class
 */
class WPmudev extends Hosting {
    const STAGE = "very_early";

    /**
     * Detect if WPmudev is active
     *
     * @return bool
     */
    public static function detect() {
        return isset( $_SERVER['WPMUDEV_HOSTED'] );
    }

    /**
     * Initialize WPmudev
     *
     * @param $stage
     * @return void
     */
    public function init($stage) {
        if (self::detect()) {
            switch ($stage) {
                case "very_early":
                    \NitroPack\Integration::initSemAcquire();
                    return true;
                case "early":
                    add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
                    add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
                    \NitroPack\Integration::initSemRelease();
                    break;
            }
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
        }
    }

    /**
     * Purge URL
     *
     * @param $url
     * @return void
     */
    public function purgeUrl($url) {
        try {
            if (function_exists("wpmudev_hosting_purge_static_cache")) {
                $parts = parse_url($url);
                $clear = wpmudev_hosting_purge_static_cache( $parts['path'] );
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * Purge all
     *
     * @return void
     */
    public function purgeAll() {
        try {
            if (function_exists("wpmudev_hosting_purge_static_cache")) {
                $clear = wpmudev_hosting_purge_static_cache();
            }
        } catch (\Exception $e) {

        }
    }
}
