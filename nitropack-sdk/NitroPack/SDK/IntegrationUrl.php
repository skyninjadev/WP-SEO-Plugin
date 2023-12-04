<?php
namespace NitroPack\SDK;

class IntegrationUrl {
    private $path;

    public static function getBaseUrl() {
        $hostname = getenv("NITROPACKIO_HOST");
        if (!$hostname) {
            $hostname = "nitropack.io";
        }

        return "https://{$hostname}/integration/";
    }

    public function __construct($widget, $siteId, $siteSecret, $version = null, $additional_params = array()) {
        $timestamp = time();
        $query = array(
            "site_id" => $siteId,
            "timestamp" => $timestamp,
        );
        $nonce = $this->getNonce($query, $siteSecret);
        $query["nonce"] = $nonce;
        
        if ($version !== null) {
            $query["ver"] = $version;
        }

        $query = array_merge($query, $additional_params);

        $this->path = $widget . "?" . http_build_query($query);
    }

    public function getNonce($query, $secret) {
        return hash_hmac("sha256", http_build_query($query), $secret);
    }

    public function getUrl() {
        return self::getBaseUrl() . $this->path;
    }

    public function getPath() {
        return $this->path;
    }
}
