<?php
/**
 * Raidboxes Class
 *
 * @package nitropack
 */

namespace NitroPack\Integration\Hosting;

use \NitroPack\SDK\Filesystem;

/**
 * Raidboxes Class
 */
class Raidboxes extends Hosting {
    const STAGE = "very_early";

    private $isPurgeNeeded = false;

    /**
     * Detect if Raidboxes is active
     *
     * @return bool
     */
    public static function detect() {
        return substr(gethostname(), 0, 4) == "box-" && Filesystem::fileExists(nitropack_trailingslashit(ABSPATH) . 'rb-plugins');
    }

    /**
     * Initialize Raidboxes
     *
     * @param $stage
     * @return void
     */
    public function init($stage) {
        if (self::detect()) {
            switch ($stage) {
                case "very_early":
                    \NitroPack\Integration::initSemAcquire();
                    \NitroPack\Integration::onShutdown([$this, 'purgeCache']);
                    return true;
                case "late":
                    \NitroPack\Integration::initSemRelease();
                    break;
            }

            add_action('nitropack_execute_purge_url', [$this, 'logPurgeNeed']);
            add_action('nitropack_execute_purge_all', [$this, 'logPurgeNeed']);
        }
    }

    public function logPurgeNeed() {
        $this->isPurgeNeeded = true;
    }

    public function purgeCache() {
        if ($this->isPurgeNeeded) {
            // There isn't a way to purge the cache for a single URL
            // So we are purging the entire cache :(
            $rbNginx = new \RaidboxesNginxCacheFunctions();
            $rbNginx->purge_cache();
        }
    }
}
