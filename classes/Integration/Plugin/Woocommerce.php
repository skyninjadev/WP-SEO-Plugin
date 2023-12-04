<?php

namespace NitroPack\Integration\Plugin;

class Woocommerce {
    const STAGE = "very_early";

    public static function isActive() {
        return defined('WC_PLUGIN_FILE');
    }

    public function init($stage) {
    }
}
