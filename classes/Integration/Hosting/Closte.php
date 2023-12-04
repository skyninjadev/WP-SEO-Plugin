<?php

namespace NitroPack\Integration\Hosting;

class Closte extends Hosting {
    const STAGE = NULL;

    public static function detect() {
        return defined("CLOSTE_APP_ID");
    }
}

