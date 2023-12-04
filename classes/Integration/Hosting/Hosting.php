<?php

namespace NitroPack\Integration\Hosting;

class Hosting {
    protected function getHosting() {
        $siteConfig = nitropack_get_site_config();
        if ($siteConfig && !empty($siteConfig["hosting"])) {
            return $siteConfig["hosting"];
        } else {
            return NULL;
        }
    }
}
