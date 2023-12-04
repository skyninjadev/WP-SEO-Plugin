<?php

require_once dirname(__FILE__) . "/autoload.php";

use \NitroPack\Url\Url;

class NitroPack_CookieStore {
    static $cookies = array();
}

NitroPack_CookieStore::$cookies = $_COOKIE;
/* You must define these constants before requiring this file for easier installation of SDK updates
define("NITROPACK_HOME_URL", "Your home page URL");
define("NITROPACK_SITE_ID", "your site ID");
define("NITROPACK_SITE_SECRET", "your site secret");
 */
if (!defined("NITROPACK_ENABLE_COMPRESSION")) define("NITROPACK_ENABLE_COMPRESSION", false); // Set this to true to enable compression. Only do this if your server does not already have compression enabled
if (!defined("NITROPACK_WEBHOOK_TOKEN")) define("NITROPACK_WEBHOOK_TOKEN", md5(__FILE__)); // Feel free to set this to a value of your liking
if (!defined("NITROPACK_USE_QUICK_PURGE")) define("NITROPACK_USE_QUICK_PURGE", false); // Feel free to set this to a value of your liking
if (!defined("NITROPACK_STRIP_IGNORENITRO")) define("NITROPACK_STRIP_IGNORENITRO", false); //Change value to true if ignorenitro parameter needs to be removed from REQUEST_URI
if (!defined("NITROPACK_USE_REDIS")) define("NITROPACK_USE_REDIS", false); // Set this to true to enable storing cache in Redis
if (!defined("NITROPACK_REDIS_HOST")) define("NITROPACK_REDIS_HOST", "127.0.0.1"); // Set this to the IP of your Redis server
if (!defined("NITROPACK_REDIS_PORT")) define("NITROPACK_REDIS_PORT", 6379); // Set this to the port of your Redis server
if (!defined("NITROPACK_REDIS_PASS")) define("NITROPACK_REDIS_PASS", NULL); // Set this to the password of your redis server if authentication is needed
if (!defined("NITROPACK_REDIS_DB")) define("NITROPACK_REDIS_DB", NULL); // Set this to the number of the Redis DB if you'd like to not use the default one
if (!defined("NITROPACK_DATA_DIR")) define("NITROPACK_DATA_DIR", NULL); // Set this to the number of the Redis DB if you'd like to not use the default one
if (!defined("NITROPACK_DISABLE_BACKLOG")) define("NITROPACK_DISABLE_BACKLOG", true); // Only allow backlog use if you've prepared a way to replay backlogged entries. Otherwise you might end up with a permanently disabled cache layer and an infinitely growing backlog file

if (NITROPACK_USE_REDIS) {
    NitroPack\SDK\Filesystem::setStorageDriver(new NitroPack\SDK\StorageDriver\Redis(
        NITROPACK_REDIS_HOST,
        NITROPACK_REDIS_PORT,
        NITROPACK_REDIS_PASS,
        NITROPACK_REDIS_DB
    ));
}

function nitropack_filter_non_original_cookies(&$cookies) {
    $ogNames = is_array(NitroPack_CookieStore::$cookies) ? array_keys(NitroPack_CookieStore::$cookies) : array();
    foreach ($cookies as $name=>$val) {
        if (!in_array($name, $ogNames)) {
            unset($cookies[$name]);
        }
    }
}

function nitropack_get_instance($siteId = NULL, $siteSecret = NULL, $url = NULL) {
    static $instances = [];
    $key = $url ? $url : "auto";
    if (empty($instances[$key])) {
        try {
            NitroPack\SDK\NitroPack::addCookieFilter("nitropack_filter_non_original_cookies");
            $siteId = $siteId !== NULL ? $siteId : NITROPACK_SITE_ID;
            $siteSecret = $siteSecret !== NULL ? $siteSecret : NITROPACK_SITE_SECRET;
            if (NITROPACK_DATA_DIR) {
                $instances[$key] = new NitroPack\SDK\NitroPack($siteId, $siteSecret, NULL, $url, NITROPACK_DATA_DIR);
            } else {
                $instances[$key] = new NitroPack\SDK\NitroPack($siteId, $siteSecret, NULL, $url);
            }
        } catch(\Exception $e) {
            $instances[$key] = NULL;
        }
    }

    return $instances[$key];
}

function nitropack_removeCacheBustParam($content) {
    $content = preg_replace("/(\?|%26|&#0?38;|&#x0?26;|&(amp;)?)ignorenitro(%3D|=)[a-fA-F0-9]{32}(?!%26|&#0?38;|&#x0?26;|&(amp;)?)\/?/mu", "", $content);
    return preg_replace("/(\?|%26|&#0?38;|&#x0?26;|&(amp;)?)ignorenitro(%3D|=)[a-fA-F0-9]{32}(%26|&#0?38;|&#x0?26;|&(amp;)?)/mu", "$1", $content);
}

function nitropack_handle_request() {
    if (isset($_GET["ignorenitro"])) {
        unset($_GET["ignorenitro"]);
    }

    if (defined("NITROPACK_STRIP_IGNORENITRO") && NITROPACK_STRIP_IGNORENITRO && $_SERVER['REQUEST_URI'] != '') {
        $_SERVER['REQUEST_URI'] = nitropack_removeCacheBustParam($_SERVER['REQUEST_URI']);
    }

    header('Cache-Control: no-cache');
    header('X-Nitro-Cache: MISS');
    if ( !empty($_SERVER["HTTP_HOST"]) && !empty($_SERVER["REQUEST_URI"]) ) {
        try {
            if (is_valid_nitropack_webhook()) {
                nitropack_handle_webhook();
            } else {
                if (is_valid_nitropack_beacon()) {
                    nitropack_handle_beacon();
                } else {
                    if ( null !== $nitro = nitropack_get_instance() ) {
                        if ($nitro->isCacheAllowed()) {
                            if (NITROPACK_ENABLE_COMPRESSION) {
                                $nitro->enableCompression();
                            }

                            if ($nitro->hasLocalCache()) {
                                header('X-Nitro-Cache: HIT');
                                setcookie("nitroCache", "HIT", time() + 10);
                                $nitro->pageCache->readfile();
                                exit;
                            } else {
                                // We need the following if..else block to handle bot requests which will not be firing our beacon
                                if (nitropack_is_warmup_request()) {
                                    $nitro->hasRemoteCache("default"); // Only ping the API letting our service know that this page must be cached.
                                    exit; // No need to continue handling this request. The response is not important.
                                } else if (nitropack_is_lighthouse_request() || nitropack_is_gtmetrix_request() || nitropack_is_pingdom_request()) {
                                    $nitro->hasRemoteCache("default"); // Ping the API letting our service know that this page must be cached.
                                }

                                $nitro->pageCache->useInvalidated(true);
                                if ($nitro->hasLocalCache()) {
                                    header('X-Nitro-Cache: STALE');
                                    $nitro->pageCache->readfile();
                                    exit;
                                } else {
                                    $nitro->pageCache->useInvalidated(false);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Fail silently
        }
    }
}

function nitropack_validate_webhook_token($token) {
    return preg_match("/^([abcdef0-9]{32})$/", strtolower($token)) && $token == NITROPACK_WEBHOOK_TOKEN;
}

function is_valid_nitropack_webhook() {
    return !empty($_GET["nitroWebhook"]) && !empty($_GET["token"]) && nitropack_validate_webhook_token($_GET["token"]);
}

function is_valid_nitropack_beacon() {
    if (!isset($_POST["nitroBeaconUrl"]) || !isset($_POST["nitroBeaconHash"])) return false;

    if (function_exists("hash_hmac") && function_exists("hash_equals")) {
        $url = base64_decode($_POST["nitroBeaconUrl"]);
        $cookiesJson = !empty($_POST["nitroBeaconCookies"]) ? base64_decode($_POST["nitroBeaconCookies"]) : ""; // We need to fall back to empty string to remain backwards compatible. Otherwise cache files invalidated before an upgrade will never get updated :(
        $localHash = hash_hmac("sha512", $url.$cookiesJson, NITROPACK_SITE_SECRET);
        return hash_equals($_POST["nitroBeaconHash"], $localHash);
    } else {
        return !empty($_POST["nitroBeaconUrl"]);
    }
}

function nitropack_handle_beacon() {
    if (!empty($_POST["nitroBeaconUrl"])) {
        $url = base64_decode($_POST["nitroBeaconUrl"]);

        if (!empty($_POST["nitroBeaconCookies"])) {
            NitroPack_CookieStore::$cookies = json_decode(base64_decode($_POST["nitroBeaconCookies"]), true);
        }

        if (null !== $nitro = nitropack_get_instance(NITROPACK_SITE_ID, NITROPACK_SITE_SECRET, $url) ) {
            try {
                if (!$nitro->hasLocalCache(false)) {
                    header("X-Nitro-Beacon: FORWARD");
                    $hasCache = $nitro->hasRemoteCache("default", false); // Download the new cache file
                    $nitro->purgeProxyCache($url);
                    printf("Cache %s", $hasCache ? "fetched" : "requested");
                } else {
                    header("X-Nitro-Beacon: SKIP");
                    printf("Cache exists already");
                }
            } catch (Exception $e) {
                // not a critical error, do nothing
            }
        }
    }
    exit;
}

function nitropack_handle_webhook() {
    if (NITROPACK_WEBHOOK_TOKEN == $_GET["token"]) {
        switch($_GET["nitroWebhook"]) {
        case "config":
            if (null !== $nitro = nitropack_get_instance() ) {
                try {
                    $nitro->fetchConfig();
                } catch (\Exception $e) {}
            }
            break;
        case "cache_ready":
            if (!empty($_POST["url"])) {
                $readyUrl = nitropack_sanitize_url_input($_POST["url"]);

                if ($readyUrl && null !== $nitro = nitropack_get_instance(NITROPACK_SITE_ID, NITROPACK_SITE_SECRET, $readyUrl) ) {
                    $hasCache = $nitro->hasRemoteCache("default", false); // Download the new cache file
                    $nitro->purgeProxyCache($readyUrl);
                }
            }
            break;
        case "cache_clear":
            if (!empty($_POST["url"])) {
                $urls = is_array($_POST["url"]) ? $_POST["url"] : array($_POST["url"]);
                foreach ($urls as $url) {
                    $sanitizedUrl = nitropack_sanitize_url_input($url);
                    nitropack_sdk_purge_local($sanitizedUrl);
                }
            } else {
                nitropack_sdk_purge_local();
                nitropack_sdk_delete_backlog();
            }
            break;
        }
    }
    exit;
}

function nitropack_sanitize_url_input($url) {
    $result = NULL;
    $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
    $urlObj = new \NitroPack\Url\Url($url);
    if ($sanitizedUrl !== false && $urlObj->isValid() !== false) {
        $result = $sanitizedUrl;
    }

    return $result;
}

function nitropack_sdk_invalidate($url = NULL, $tag = NULL, $reason = NULL) {
    if (null !== $nitro = nitropack_get_instance()) {
        try {
            if ($tag) {
                if (is_array($tag)) {
                    $tag = array_map('nitropack_filter_tag', $tag);
                } else {
                    $tag = nitropack_filter_tag($tag);
                }
            }

            if ($tag != "pageType:home") {
                $nitro->invalidateCache(NITROPACK_HOME_URL, "pageType:home", $reason);
            }

            $nitro->invalidateCache($url, $tag, $reason);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    return false;
}

function nitropack_sdk_purge($url = NULL, $tag = NULL, $reason = NULL, $type = \NitroPack\SDK\PurgeType::COMPLETE) {
    if (null !== $nitro = nitropack_get_instance()) {
        try {
            if ($tag) {
                if (is_array($tag)) {
                    $tag = array_map('nitropack_filter_tag', $tag);
                } else {
                    $tag = nitropack_filter_tag($tag);
                }
            }

            if ($tag != "pageType:home") {
                $nitro->invalidateCache(NITROPACK_HOME_URL, "pageType:home", $reason);
            }

            $nitro->purgeCache($url, $tag, $type, $reason);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    return false;
}

function nitropack_sdk_purge_local($url = NULL) {
    if (null !== $nitro = nitropack_get_instance()) {
        try {
            if ($url) {
                $nitro->purgeLocalUrlCache($url);
            } else {
                $nitro->purgeLocalCache(NITROPACK_USE_QUICK_PURGE);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    return false;
}

function nitropack_sdk_delete_backlog() {
    if (null !== $nitro = nitropack_get_instance()) {
        try {
            if ($nitro->backlog->exists()) {
                $nitro->backlog->delete();
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    return false;
}

function nitropack_filter_tag($tag) {
    return preg_replace("/[^a-zA-Z0-9:]/", ":", $tag);
}

function nitropack_is_warmup_request() {
    return !empty($_SERVER["HTTP_X_NITRO_WARMUP"]);
}

function nitropack_is_lighthouse_request() {
    return !empty($_SERVER["HTTP_USER_AGENT"]) && stripos($_SERVER["HTTP_USER_AGENT"], "lighthouse") !== false;
}

function nitropack_is_gtmetrix_request() {
    return !empty($_SERVER["HTTP_USER_AGENT"]) && stripos($_SERVER["HTTP_USER_AGENT"], "gtmetrix") !== false;
}

function nitropack_is_pingdom_request() {
    return !empty($_SERVER["HTTP_USER_AGENT"]) && stripos($_SERVER["HTTP_USER_AGENT"], "pingdom") !== false;
}

function nitropack_is_optimizer_request() {
    return isset($_SERVER["HTTP_X_NITROPACK_REQUEST"]);
}

function nitropack_add_tag($tag = NULL, $flush = false) {
    static $addedTags = [];

    if ($tag) {
        $addedTags[] = $tag;
    }

    if ($flush) {
        if (null !== $nitro = nitropack_get_instance()) {
            try {
                // Check whether this is the home page and tag this URL with pageType:home
                $nitro->getApi()->tagUrl($nitro->getUrl(), array_map("nitropack_filter_tag", $addedTags));
            } catch (\Exception $e) {}
        }
    }
}

function nitropack_get_beacon_script() {
    if (null !== $nitro = nitropack_get_instance() ) {
        $url = $nitro->getUrl();
        $cookiesJson = json_encode($nitro->supportedCookiesFilter(NitroPack\SDK\NitroPack::getCookies()));

        if (function_exists("hash_hmac") && function_exists("hash_equals")) {
            $hash = hash_hmac("sha512", $url.$cookiesJson, NITROPACK_SITE_SECRET);
        } else {
            $hash = "";
        }
        $url = base64_encode($url); // We want only ASCII
        $cookiesb64 = base64_encode($cookiesJson);

        return "<script nitro-exclude>if (document.cookie.indexOf('nitroCache=HIT') == -1) {var nitroData = new FormData(); nitroData.append('nitroBeaconUrl', '$url'); nitroData.append('nitroBeaconCookies', '$cookiesb64'); nitroData.append('nitroBeaconHash', '$hash'); navigator.sendBeacon(location.href, nitroData);} document.cookie = 'nitroCache=HIT; expires=Thu, 01 Jan 1970 00:00:01 GMT;';</script>";
    }
}

function nitropack_init_webhooks() {
    $webhookFile = dirname(__FILE__) . "/nitropack_webhooks";

    if (!NitroPack\SDK\Filesystem::fileExists($webhookFile)) {
        NitroPack\SDK\Filesystem::filePutContents($webhookFile, time());
        if (null !== $nitro = nitropack_get_instance() ) {
            try {
                $configUrl = new Url(NITROPACK_HOME_URL . "?nitroWebhook=config&token=" . NITROPACK_WEBHOOK_TOKEN);
                $cacheClearUrl = new Url(NITROPACK_HOME_URL . "?nitroWebhook=cache_clear&token=" . NITROPACK_WEBHOOK_TOKEN);
                $cacheReadyUrl = new Url(NITROPACK_HOME_URL . "?nitroWebhook=cache_ready&token=" . NITROPACK_WEBHOOK_TOKEN);

                $nitro->getApi()->setWebhook("config", $configUrl);
                $nitro->getApi()->setWebhook("cache_clear", $cacheClearUrl);
                $nitro->getApi()->setWebhook("cache_ready", $cacheReadyUrl);
            } catch (\Exception $e) {}
        }
    }
}

nitropack_handle_request();
if ( null !== $nitro = nitropack_get_instance() ) {
    if ($nitro->isAllowedUrl($nitro->getUrl()) && $nitro->isAllowedRequest(true)) {
        nitropack_init_webhooks();
        ob_start(function($buffer) {
            if (nitropack_is_optimizer_request()) {
                nitropack_add_tag(NULL, true); // Flush registered tags
            }

            // Remove BOM from output
            $bom = pack('H*','EFBBBF');
            $buffer = preg_replace("/^($bom)*/", '', $buffer);

            // Get the content type
            $respHeaders = headers_list();
            $contentType = NULL;
            foreach ($respHeaders as $respHeader) {
                if (stripos(trim($respHeader), 'Content-Type:') === 0) {
                    $contentType = $respHeader;
                }
            }
    
            // If the content type header was detected and it's value does not contain 'text/html',
            // don't attach the beacon script.
            if ($contentType !== NULL && stripos($contentType, 'text/html') === false) {
                return $buffer;
            }

            if (!preg_match("/<html.*?\s(amp|⚡)(\s|=|>)/", $buffer)) {
                $buffer = str_replace("</body", nitropack_get_beacon_script() . "</body", $buffer);
            }

            return $buffer;
        }, 0, PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
    } else {
        header("X-Nitro-Disabled: 1");
    }
}