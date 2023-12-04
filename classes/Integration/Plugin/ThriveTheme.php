<?php

namespace NitroPack\Integration\Plugin;

class ThriveTheme {
    const STAGE = "very_early";

    public static function isActive() {
        return get_current_theme() == "Thrive Theme Builder";
    }

    public function init($stage) {
        add_filter('tve_dash_is_crawler_override', function($current_value) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'Nitro-Optimizer-Agent') !== false) {
                return false;
            }
            return $current_value;
        });
        return NULL;
    }
}