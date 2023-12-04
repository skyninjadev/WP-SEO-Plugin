<?php

namespace NitroPack\Integration\Plugin;

class DownloadManager {
    const STAGE = NULL; // Don't run init

    public static function isActive() {
        return defined('DLM_VERSION');
    }

    public static function downloadingUrl() {
        $downloadingPage = get_option("dlm_dp_downloading_page");
        return $downloadingPage ? strtolower(get_permalink($downloadingPage)) : NULL;
    }

    public static function downloadEndpoint() {
        $downloadEndpoint = get_option("dlm_download_endpoint");
        return $downloadEndpoint ? strtolower(nitropack_trailingslashit(get_home_url()) . $downloadEndpoint) : NULL;
    }
}
