<?php

namespace NitroPack\Integration\Hosting;

class Flywheel extends Hosting {
    const STAGE = NULL;

    public static function detect() {
        return defined("FLYWHEEL_PLUGIN_DIR");
    }
}
