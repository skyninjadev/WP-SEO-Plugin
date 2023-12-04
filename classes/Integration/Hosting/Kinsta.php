<?php

namespace NitroPack\Integration\Hosting;

class Kinsta extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        return defined("KINSTAMU_VERSION");
    }

    public function init($stage) {
        if ($this->getHosting() == "kinsta") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
        }
    }

    public function purgeUrl($url) {
        try {
            $data = array(
                'single|nitropack' => preg_replace( '@^https?://@', '', $url)
            );
            $httpClient = new \NitroPack\HttpClient\HttpClient("https://localhost/kinsta-clear-cache/v2/immediate");
            $httpClient->setPostData($data);
            $httpClient->fetch(true, "POST");
            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    public function purgeAll() {
        try {
            $httpClient = new \NitroPack\HttpClient\HttpClient("https://localhost/kinsta-clear-cache-all");
            $httpClient->timeout = 5;
            $httpClient->fetch();
            return true;
        } catch (\Exception $e) {
        }

        return false;
    }
}
