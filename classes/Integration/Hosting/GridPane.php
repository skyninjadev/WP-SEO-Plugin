<?php

namespace NitroPack\Integration\Hosting;

class GridPane extends Hosting {
    const STAGE = NULL;

    public static function detect() {
        $configFilePath = nitropack_get_wpconfig_path();
        if (!$configFilePath) return false;
        return strpos(file_get_contents($configFilePath), 'GridPane Cache Settings') !== false;
    }
}


