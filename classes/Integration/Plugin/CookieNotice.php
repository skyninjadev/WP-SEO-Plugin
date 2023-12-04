<?php

namespace NitroPack\Integration\Plugin;

class CookieNotice {
    const STAGE = "late";

    public function init($stage) {
        # Cookie Notice plugin hack
        add_action( 'init', function() {
            if (function_exists("Cookie_Notice")) {
                $agent = Cookie_Notice()->bot_detect->get_user_agent();
                if ($agent) {
                    $replaced = str_replace('Nitro-Optimizer-Agent', '', $agent);
                    Cookie_Notice()->bot_detect->set_user_agent($replaced);
                }
            }
        }, 5);
    }
}
