<?php

namespace NitroPack\Integration\Hosting;

class SiteGround extends Hosting {
    const STAGE = "very_early";

    public static function detect() {
        if (strpos(gethostname(), "siteground.eu") !== false) return true;
        $configFilePath = nitropack_get_wpconfig_path();
        if (!$configFilePath) return false;
        return strpos(file_get_contents($configFilePath), 'Added by SiteGround WordPress management system') !== false;
    }

    public function init($stage) {
        if ($this->getHosting() == "siteground") {
            add_action('nitropack_execute_purge_url', [$this, 'purgeUrl']);
            add_action('nitropack_execute_purge_all', [$this, 'purgeAll']);
            add_action('nitropack_early_cache_headers', [$this, 'setCacheControl']);
            add_action('nitropack_cacheable_cache_headers', [$this, 'allowProxyCache']);
            add_action('nitropack_cachehit_cache_headers', [$this, 'allowProxyCache']);
        }
    }

    public function purgeUrl($url) {
        $urlObj = new \NitroPack\Url\Url($url);

        $host = preg_replace("/^www\./", "", $urlObj->getHost());
        $path = $urlObj->getPath();

        if ($urlObj->getQuery()) {
            $path .= "(.*)";
        }

        try {
            $sock_path = '/chroot/tmp/site-tools.sock';
            if ( ! file_exists( $sock_path ) ) {
                return false;
            }

            $sock = stream_socket_client( 'unix://' . $sock_path, $errno, $errstr, 5 );

            if ( false === $sock ) {
                return false;
            }

            $req = array(
                'api' => 'domain-all',
                'cmd' => 'update',
                'settings' => array( 'json' => 1 ),
                'params' => array(
                    'flush_cache' => '1',
                    'id'          => $host,
                    'path'        => $path,
                ),
            );

            fwrite( $sock, json_encode( $req, JSON_FORCE_OBJECT ) . "\n" );
            $response = fgets( $sock, 32 * 1024 );
            fclose( $sock );
            $result = @json_decode( $response, true );
            if ( false === $result || isset( $result['err_code'] ) ) {
                return false;
            }
        } catch (\Exception $e) {}

        return true;
    }

    public function purgeAll() {
        $siteConfig = nitropack_get_site_config();
        if ($siteConfig && !empty($siteConfig["home_url"])) {
            return $this->purgeUrl(nitropack_trailingslashit($siteConfig["home_url"]) . "/(.*)");
        }
        return false;
    }

    public function setCacheControl() {
        nitropack_header("Cache-Control: public, max-age=0, s-maxage=3600"); // needs to be like that instead of Cache-Control: no-cache in order to allow caching in the provided reverse proxy, but prevent the browsers from doing so
    }

    public function allowProxyCache() {
        $this->setCacheControl();
        nitropack_header('X-Cache-Enabled: True');
        nitropack_header('Vary: User-Agent');
    }
}
