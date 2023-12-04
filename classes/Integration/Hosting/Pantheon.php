<?php
/**
 * Pantheon Class
 *
 * @package nitropack
 */

namespace NitroPack\Integration\Hosting;

use \NitroPack\SDK\Filesystem;

/**
 * Pantheon Class
 */
class Pantheon extends Hosting {
    const STAGE = "very_early";

    /**
     * Detect if Pantheon is active
     *
     * @return bool
     */
    public static function detect() {
        return isset( $_ENV['PANTHEON_ENVIRONMENT'] );
    }

    /**
     * Initialize Pantheon
     *
     * @param $stage
     * @return void
     */
    public function init($stage) {

    }
}
