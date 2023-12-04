<?php
namespace NitroPack\SDK\Api;

class ResponseStatus {
    const OK                  = 200;
    const ACCEPTED            = 202; // There is no cache, but the request for creating cache has been accepted.
    const BAD_REQUEST         = 400;
    const PAYMENT_REQUIRED    = 402;
    const FORBIDDEN           = 403;
    const NOT_FOUND           = 404;
    const CONFLICT            = 409;
    const RUNTIME_ERROR       = 500;
    const SERVICE_UNAVAILABLE = 503;
    const UNKNOWN             = -1;

    public static function getStatus($code) {
        if (isset(self::$codeToStatus[$code])) {
            return self::$codeToStatus[$code];
        } else {
            return ResponseStatus::UNKNOWN;
        }
    }

    private static $codeToStatus = array(
        "200" => ResponseStatus::OK,
        "202" => ResponseStatus::ACCEPTED,
        "400" => ResponseStatus::BAD_REQUEST,
        "402" => ResponseStatus::PAYMENT_REQUIRED,
        "403" => ResponseStatus::FORBIDDEN,
        "404" => ResponseStatus::NOT_FOUND,
        "409" => ResponseStatus::CONFLICT,
        "500" => ResponseStatus::RUNTIME_ERROR,
        "503" => ResponseStatus::SERVICE_UNAVAILABLE
    );
}
