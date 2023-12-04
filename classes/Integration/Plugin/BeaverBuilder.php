<?php

namespace NitroPack\Integration\Plugin;

class BeaverBuilder {
    const STAGE = "late";

    public static function isActive() {
        return class_exists('\FLBuilder');
    }

    public function init($stage) {
        if (self::isActive() && get_option("nitropack-bbCacheSyncPurge", 0)) {
            add_action('fl_builder_cache_cleared', [$this, 'cachePurgeSync']);
            return true;
        }
    }

    public function cachePurgeSync() {
        nitropack_purge(NULL, NULL, sprintf("Full cache purge due to cache sync with Beaver Builder."));
    }
}
