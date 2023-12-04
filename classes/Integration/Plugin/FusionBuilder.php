<?php

namespace NitroPack\Integration\Plugin;

class FusionBuilder {
    const STAGE = "late";

    public function init($stage) {
        add_action( 'init', function() {
            if (defined('FUSION_BUILDER_VERSION')) {
                add_filter("jetpack_device_detection_get_info", function($info, $ua, $ua_info) {
                    $agent = $ua_info->useragent;
                    if ($agent
                        && stripos($agent, "Nitro-Optimizer-Agent") !== false
                        && stripos($agent, "Android") === false
                        && stripos($agent, "iPad") === false
                    ) {
                        $info["is_phone"] = false;
                        $info["is_phone_matched_ua"] = "";
                        $info["is_handheld"] = false;
                        $info["is_desktop"] = true;
                    }
                    return $info;
                }, 10, 3);
            }
        }, 5);
    }
}
