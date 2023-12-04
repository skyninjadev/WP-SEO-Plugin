<?php
namespace NitroPack\SDK\Url;

use NitroPack\Url\Url as BaseUrl;

class Embedjs extends BaseUrl {
    public function __construct() {
        $hostname = getenv("NITROPACKIO_HOST");
        if (!$hostname) {
            $hostname = "nitropack.io";
        }

        parent::__construct("https://{$hostname}/asset/js/embed.js");
    }
}
