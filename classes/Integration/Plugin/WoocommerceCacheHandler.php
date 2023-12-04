<?php

namespace NitroPack\Integration\Plugin;

class WoocommerceCacheHandler {
    const STAGE = "very_early";

    public static function isActive() {
        return class_exists("\Aelia\WC\Cache_Handler\Cache_Handler");
    }

    public function init($stage) {
    }
}
