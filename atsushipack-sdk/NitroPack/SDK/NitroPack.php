<?php
namespace NitroPack\SDK;

use \NitroPack\Url\Url;
use \NitroPack\SDK\Url\Embedjs;

class NitroPack {
    const VERSION = '0.55.0';
    const PAGECACHE_LOCK_EXPIRATION_TIME = 300; // in seconds
    private $dataDir;
    private $cachePath = array('data', 'pagecache');
    private $configFile = array('data', 'config.json');
    private $healthStatusFile = array('data', 'service-health');
    private $timestampFile = array('data', 'time.mark');
    private $pageCacheLockFile = array('data', 'get_cache.lock');
    private $statefulCacheRevisionsFile = array('data', 'element-revision.json');
    private $cachePathSuffix = NULL;
    private $configTTL; // In seconds

    private $siteId;
    private $siteSecret;
    private $userAgent; // Defaults to desktop Chrome

    private $url;
    private $config;
    private $device;
    private $api;
    private $varnishProxyCacheHeaders = [];
    private $referer;

    public $backlog;
    public $elementRevision;
    public $healthStatus;
    public $pageCache; // TODO: consider better ways of protecting/providing this outside the class
    public $useCompression;

    private static $cachePrefixes = array();
    private static $cookieFilters = array();

    public static function getRemoteAddr() {
        // IP check order is: CloudFlare, Proxy, Client IP
        $ipKeys = ["HTTP_X_FORWARDED_FOR", "HTTP_CF_CONNECTING_IP", "REMOTE_ADDR"];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        return NULL;
    }

    public static function getCookies() {
        $cookies = array();

        foreach ($_COOKIE as $name=>$value) {
            if (is_array($value)) {
                foreach ($value as $k=>$v) {
                    $key = $name . "[$k]";
                    $cookies[$key] = $v;
                }
            } else {
                $cookies[$name] = $value;
            }
        }

        foreach (self::$cookieFilters as $cookieFilter) {
            call_user_func_array($cookieFilter, array(&$cookies));
        }

        return $cookies;
    }

    public static function addCookieFilter($callback) {
        if (is_callable($callback)) {
            if (!in_array($callback, self::$cookieFilters, true)) {
                self::$cookieFilters[] = $callback;
            }
        } else {
            throw new \RuntimeException("Non-callable callback passed to " . __FUNCTION__);
        }
    }

    public static function addCustomCachePrefix($prefix = "") {
        self::$cachePrefixes[] = $prefix;
    }

    public static function getCustomCachePrefix() {
        return implode("-", self::$cachePrefixes);
    }

    public static function wildcardToRegex($str, $delim = "/") {
        return implode(".*?", array_map(function($input) use ($delim) { return preg_quote($input, $delim); }, explode("*", $str)));
    }

    private function nitro_parse_str($qStr, &$resArr) {
        if (strpos($qStr, '?') !== false) {
            $tmpArr = explode('?', $qStr, 2);
            parse_str($tmpArr[0], $resArr);
            $completeValue = end($resArr) . $tmpArr[1];
            $resArr[key($resArr)] = $completeValue;
            reset($resArr);
        } else {
            parse_str($qStr, $resArr);
        }
    }

    public function __construct($siteId, $siteSecret, $userAgent = NULL, $url = NULL, $dataDir = __DIR__, $referer = NULL) {
        $this->configTTL = 3600;
        $this->siteId = $siteId;
        $this->siteSecret = $siteSecret;
        $this->dataDir = $dataDir;
        $this->referer = $referer;
        $this->backlog = new Backlog($dataDir, $this);
        $this->elementRevision = new ElementRevision($siteId, $this->getStatefulCacheRevisionFile());
        $this->healthStatus = HealthStatus::HEALTHY;
        $this->loadHealthStatus();

        if (empty($userAgent)) {
            $this->userAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36';
        } else {
            $this->userAgent = $userAgent;
        }

        $this->loadConfig($siteId, $siteSecret);
        $this->device = new Device($this->userAgent);

        if(empty($url)) {
            $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "example.com";
            $uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "/";
            $url = $this->getScheme() . $host . $uri;
        }

        $queryStr = parse_url($url, PHP_URL_QUERY);

        if ($queryStr) {
            $this->nitro_parse_str($queryStr, $queryParams);

            if ($queryParams) {
                if ($this->config->IgnoredParams) {
                    foreach ($this->config->IgnoredParams as $ignorePattern) {
                        $regex = "/^" . self::wildcardToRegex($ignorePattern) . "$/";
                        foreach($queryParams as $paramName => $paramValue) {
                            if (preg_match($regex, $paramName)) {
                                unset($queryParams[$paramName]);
                            }
                        }
                    }
                }

                ksort($queryParams);
                $url = str_replace($queryStr, http_build_query($queryParams), $url);
            }
        }

        $urlInfo = new Url($url);
        $this->url = $urlInfo->getNormalized();

        $this->pageCache = new Pagecache($this->url, $this->userAgent, $this->supportedCookiesFilter(self::getCookies()), $this->config->PageCache->SupportedCookies, $this->isAJAXRequest());
        $this->pageCache->setCookiesProvider([$this, "getPagecacheCookies"]);
        if ($this->isAJAXRequest() && $this->isAllowedAJAXUrl($this->url) && !empty($_SERVER["HTTP_REFERER"]) && !$this->isAllowedStandaloneAJAXUrl($url)) {
            $refererInfo = new Url($_SERVER["HTTP_REFERER"]);
            $this->pageCache->setReferer($refererInfo->getNormalized());
        }
        if (!empty($this->config->URLPathVersion)) {
            $this->pageCache->setUrlPathVersion($this->config->URLPathVersion);
        }


        $this->api = new Api($this->siteId, $siteSecret);
        $this->api->setBacklog($this->backlog);
        $this->api->setNitroPack($this);

        $this->pageCache->setDataDir($this->getCacheDir());

        $this->useCompression = false;
    }

    public function setReferer($referer) {
        $refererInfo = new Url($referer);
        $this->referer = $refererInfo->getNormalized();
        $this->pageCache->setReferer($this->referer);
    }

    public function getPagecacheCookies() {
        return $this->supportedCookiesFilter(self::getCookies());
    }

    public function supportedCookiesFilter($cookies) {
        $supportedCookies = array();
        foreach ($cookies as $cookieName=>$cookieValue) {
            foreach ($this->config->PageCache->SupportedCookies as $cookie) {
                if (preg_match('/^' . self::wildcardToRegex($cookie) . '$/', $cookieName)) {
                    $supportedCookies[$cookieName] = $cookieValue;
                }
            }
        }
        return $supportedCookies;
    }

    public function tagUrl($url, $tag) {
        if ($this->isAllowedUrl($url)) {
            return $this->api->tagUrl($url, $tag);
        } else {
            return false;
        }
    }

    public function setCachePathSuffix($suffix) {
        $this->cachePathSuffix = $suffix;
        $this->pageCache->setDataDir($this->getCacheDir());
    }

    public function enableCompression() {
        $this->pageCache->enableCompression();
    }

    public function disableCompression() {
        $this->pageCache->disableCompression();
    }

    public function getUrl() {
        return $this->url;
    }

    public function getApi() {
        return $this->api;
    }

    public function getSiteId() {
        return $this->siteId;
    }

    public function getConfig() {
        return $this->config;
    }

    public function getCacheDir() {
        $cachePath = $this->cachePath;
        array_unshift($cachePath, $this->dataDir);
        if ($this->cachePathSuffix) {
            $cachePath[] = $this->cachePathSuffix;
        }
        return Filesystem::getOsPath($cachePath);
    }

    public function getStatefulCacheRevisionFile() {
        $revisionFile = $this->statefulCacheRevisionsFile;
        array_unshift($revisionFile, $this->dataDir);
        return Filesystem::getOsPath($revisionFile);
    }

    public function getHealthStatus() {
        return $this->healthStatus;
    }

    public function getHealthStatusFile() {
        $healthStatusFile = $this->healthStatusFile;
        array_unshift($healthStatusFile, $this->dataDir);
        return Filesystem::getOsPath($healthStatusFile);
    }

    public function setHealthStatus($status) {
        $this->healthStatus = $status;
        Filesystem::filePutContents($this->getHealthStatusFile(), $status);
    }

    public function loadHealthStatus() {
        if (defined("NITROPACK_DISABLE_BACKLOG")) return;
        if (Filesystem::fileExists($this->getHealthStatusFile())) {
            $this->healthStatus = Filesystem::fileGetContents($this->getHealthStatusFile());
        } else {
            $this->healthStatus = HealthStatus::HEALTHY;
        }
    }

    public function checkHealthStatus() {
        try {
            // TODO: Potentially replace this with a dedicated method in the API
            $this->fetchConfig(true);
            $this->setHealthStatus(HealthStatus::HEALTHY);
            return HealthStatus::HEALTHY;
        } catch (\Exception $e) {
            $this->setHealthStatus(HealthStatus::SICK);
            return HealthStatus::SICK;
        }
    }

    public function getTimestampFile() {
        $timestampFile = $this->timestampFile;
        array_unshift($timestampFile, $this->dataDir);
        return Filesystem::getOsPath($timestampFile);
    }

    public function loadTimeMarks() {
        if (Filesystem::fileExists($this->getTimestampFile())) {
            try {
                $marks = @json_decode(Filesystem::fileGetContents($this->getTimestampFile()), true);
                if (!$marks) {
                    $marks = [];
                }
            } catch (\Exception $e) {
                $marks = [];
            }
        } else {
            $marks = [];
        }

        return $marks;
    }

    public function setTimeMark($name, $time = NULL) {
        if ($time === NULL) $time = microtime(true);
        $marks = $this->loadTimeMarks();
        $marks[$name] = $time;
        Filesystem::filePutContents($this->getTimestampFile(), json_encode($marks));
    }

    public function unsetTimeMark($name) {
        $marks = $this->loadTimeMarks();
        if (isset($marks[$name])) {
            unset($marks[$name]);
        }
        Filesystem::filePutContents($this->getTimestampFile(), json_encode($marks));
    }

    public function getTimeMark($name) {
        $marks = $this->loadTimeMarks();
        if (!empty($marks[$name])) {
            return $marks[$name];
        } else {
            return NULL;
        }
    }

    public function getRemainingCacheTtl() {
        return $this->pageCache->getRemainingTtl($this->config->PageCache->ExpireTime);
    }

    public function hasCache($layout = 'default') {
        if ($this->hasLocalCache()) {
            return true;
        } else {
            return $this->hasRemoteCache($layout);
        }
    }

    public function hasLocalCache($checkIfRequestIsAllowed = true) {
        if ($this->backlog->exists()) return false;
        if (!$this->isAllowedUrl($this->url) || ($checkIfRequestIsAllowed && !$this->isAllowedRequest())) return false;
        $cacheRevision = !empty($this->config->RevisionHash) ? $this->config->RevisionHash : NULL;
        if ($this->getHealthStatus() !== HealthStatus::HEALTHY) {
            return false;
        }

        if (!$this->pageCache->getUseInvalidated()) {
            $ttl = $this->config->PageCache->ExpireTime;
        } else {
            $ttl = $this->config->PageCache->StaleExpireTime;
        }

        return $this->pageCache->hasCache() && !$this->pageCache->hasExpired($ttl, $cacheRevision);
    }

    public function hasRemoteCache($layout, $checkIfRequestIsAllowed = true) {
        if ($this->backlog->exists()) return false;
        if (
            !$this->isAllowedUrl($this->url) ||
            ($checkIfRequestIsAllowed && !$this->isAllowedRequest()) ||
            ($this->pageCache->getParent() && !$this->pageCache->getParent()->hasCache()) ||
            $this->isPageCacheLocked()
        ) return false;

        $resp = $this->api->getCache($this->url, $this->userAgent, $this->supportedCookiesFilter(self::getCookies()), $this->isAJAXRequest(), $layout, $this->referer);
        if ($resp->getStatus() == Api\ResponseStatus::OK) {// We have cache response

            // Check for invalidated cache and delete it if such is found
            $this->pageCache->useInvalidated(true);
            if ($this->pageCache->hasCache()) {
                $path = $this->pageCache->getCachefilePath();
                Filesystem::deleteFile($path);
                Filesystem::deleteFile($path . ".gz");
                Filesystem::deleteFile($path . ".stale");
                Filesystem::deleteFile($path . ".stale.gz");
                if (Filesystem::isDirEmpty(dirname($path))) {
                    Filesystem::deleteDir(dirname($path));
                }
            }
            $this->pageCache->useInvalidated(false);
            // End of check

            list($headers, $content) = Filesystem::explodeByHeaders($resp->getBody());
            $this->pageCache->setContent($content, $headers);
            return true;
        } else {
            // The goal is to serve cache at all times even when it is slightly outdated. This approach should be ok because new cache has been requested and it should be ready soon
            if ($this->pageCache->hasCache()) {
                return true;
            } else {
                // Check for invalidated cache
                $this->pageCache->useInvalidated(true);
                if ($this->hasLocalCache(false)) {
                    return true;
                } else {
                    $this->pageCache->useInvalidated(false);
                }
            }

            return false;
        }
    }

    public function hasRemoteCacheMulti($urls, $layout, $checkIfRequestIsAllowed = true) {
        if ($this->backlog->exists()) return false;

        foreach ($urls as $url) {
            if (
                !$this->isAllowedUrl($url) ||
                ($checkIfRequestIsAllowed && !$this->isAllowedRequest()) ||
                ($this->pageCache->getParent() && !$this->pageCache->getParent()->hasCache()) ||    // This check is works correctly despite the fact that it doesn't check the current iteration's url because the check is based on headers and all of the headers for the URLs are the same for batched webhooks
                $this->isPageCacheLocked()
            ) return false;
        }        

        $hasCache = true;   // hasCache is false if even one of the URLs doesn't have a cache
        $responses = $this->api->getCacheMulti($urls, $this->userAgent, $this->supportedCookiesFilter(self::getCookies()), $this->isAJAXRequest(), $layout, $this->referer);
        
        $originalURL = $this->url;
        $originalPageCache = $this->pageCache;

        foreach ($responses as $url => $resp) {
            // Overwrite the url
            $urlInfo = new Url($url);
            $this->url = $urlInfo->getNormalized();
    
            // Overwrite the page cache
            $this->pageCache = new Pagecache($this->url, $this->userAgent, $this->supportedCookiesFilter(self::getCookies()), $this->config->PageCache->SupportedCookies, $this->isAJAXRequest());
            $this->pageCache->setCookiesProvider([$this, "getPagecacheCookies"]);
            if ($this->isAJAXRequest() && $this->isAllowedAJAXUrl($this->url) && !empty($_SERVER["HTTP_REFERER"]) && !$this->isAllowedStandaloneAJAXUrl($url)) {
                $refererInfo = new Url($_SERVER["HTTP_REFERER"]);
                $this->pageCache->setReferer($refererInfo->getNormalized());
            }
            if (!empty($this->config->URLPathVersion)) {
                $this->pageCache->setUrlPathVersion($this->config->URLPathVersion);
            }
            $this->pageCache->setDataDir($this->getCacheDir());

            // Process the response
            if ($resp->getStatus() == Api\ResponseStatus::OK) {// We have cache response

                // Check for invalidated cache and delete it if such is found
                $this->pageCache->useInvalidated(true);
                if ($this->pageCache->hasCache()) {
                    $path = $this->pageCache->getCachefilePath();
                    Filesystem::deleteFile($path);
                    Filesystem::deleteFile($path . ".gz");
                    Filesystem::deleteFile($path . ".stale");
                    Filesystem::deleteFile($path . ".stale.gz");
                    if (Filesystem::isDirEmpty(dirname($path))) {
                        Filesystem::deleteDir(dirname($path));
                    }
                }
                $this->pageCache->useInvalidated(false);
                // End of check

                list($headers, $content) = Filesystem::explodeByHeaders($resp->getBody());
                $this->pageCache->setContent($content, $headers);
                continue;
            } else {
                // The goal is to serve cache at all times even when it is slightly outdated. This approach should be ok because new cache has been requested and it should be ready soon
                if ($this->pageCache->hasCache()) {
                    continue;
                } else {
                    // Check for invalidated cache
                    $this->pageCache->useInvalidated(true);
                    if ($this->hasLocalCache(false)) {
                        continue;
                    } else {
                        $this->pageCache->useInvalidated(false);
                    }
                }
    
                $hasCache = false;
                continue;
            }
        }

        // Restore the url and page cache
        $this->url = $originalURL;
        $this->pageCache = $originalPageCache;
        
        return $hasCache;
    }

    public function invalidateCache($url = NULL, $tag = NULL, $reason = NULL) {
        return $this->purgeCache($url, $tag, PurgeType::INVALIDATE | PurgeType::PAGECACHE_ONLY, $reason);
    }

    public function clearPageCache($reason = NULL) {
        return $this->purgeCache(NULL, NULL, PurgeType::PAGECACHE_ONLY, $reason);
    }

    public function purgeCache($url = NULL, $tag = NULL, $purgeType = PurgeType::COMPLETE, $reason = NULL) {
        @set_time_limit(0);
        $this->lockPageCache(); // Set the page cache lock, expires after self::PAGECACHE_LOCK_EXPIRATION_TIME seconds

        try {
            $invalidate = !!($purgeType & PurgeType::INVALIDATE);
            $pageCacheOnly = !!($purgeType & PurgeType::PAGECACHE_ONLY);
            $lightPurge = !!($purgeType & PurgeType::LIGHT_PURGE);

            if ($url || $tag) {
                $localResult = true;
                $apiResult = true;
                if ($url) {
                    if (is_array($url)) {
                        foreach ($url as &$urlLink) {
                            $urlLink = $this->normalizeUrl($urlLink);
                            if ($invalidate) {
                                $localResult &= $this->invalidateLocalUrlCache($urlLink);
                            } else {
                                $localResult &= $this->purgeLocalUrlCache($urlLink);
                            }
                        }
                    } else {
                        $url = $this->normalizeUrl($url);
                        if ($invalidate) {
                            $localResult &= $this->invalidateLocalUrlCache($url);
                        } else {
                            $localResult &= $this->purgeLocalUrlCache($url);
                        }
                    }

                    try {
                        $apiResult &= $this->api->purgeCache($url, false, $reason, $lightPurge);
                    } catch (ServiceDownException $e) {
                        $apiResult = false;
                        // TODO: Potentially log this
                    }
                }

                if ($tag) {
                    $attemptsLeft = 10;
                    $purgedUrls = array();
                    do {
                        $hadError = false;

                        try {
                            $purgedUrls = $this->api->purgeCacheByTag($tag, $reason);

                            foreach ($purgedUrls as $url) {
                                if ($invalidate) {
                                    $localResult &= $this->invalidateLocalUrlCache($url);
                                } else {
                                    $localResult &= $this->purgeLocalUrlCache($url);
                                }
                            }
                        } catch (ServiceDownException $e) {
                            $this->purgeLocalCache(true); // TODO: This will leave stale cache files. Think of a way to delete them on systems that do not have a heartbeat (i.e custom integrations).
                            $apiResult = false;
                            // TODO: Log this
                            break;
                        } catch (\Exception $e) {
                            $hadError = true;
                            $attemptsLeft--;
                            sleep(3);
                        }
                    } while ($attemptsLeft > 0 && count($purgedUrls) > 0);
                }
            } else {
                if ($invalidate) {
                    $localResult = $this->invalidateLocalCache();
                    $apiResult = $this->api->purgeCache(NULL, $pageCacheOnly, $reason, $lightPurge); // delete only page cache
                } else {
                    $staleCacheDir = $this->purgeLocalCache(true);

                    // Call the cache purge method
                    $apiResult = $this->api->purgeCache(NULL, $pageCacheOnly, $reason, $lightPurge);

                    // Finally, delete the files of the stale directory
                    Filesystem::deleteDir($staleCacheDir);

                    $localResult = true; // We do not care if $staleCacheDir was not deleted successfully
                }

                $this->elementRevision->refresh();
            }

            $this->unlockPageCache(); // Purge cache is done, we can now unlock
        } catch (\Exception $e) {
            $this->unlockPageCache(); // Purge cache had an error, so just unlock

            throw $e;
        }

        return $apiResult && $localResult;
    }

    public function purgeLocalCache($quick = false) {
        $staleCacheDir = $this->getCacheDir() . '.stale.' . md5(microtime(true));
        $staleCacheDirSuffix = "";
        $counter = 0;
        while (Filesystem::fileExists($staleCacheDir . $staleCacheDirSuffix)) {
            $counter++;
            $staleCacheDirSuffix = "_" . $counter;
        }
        $staleCacheDir .= $staleCacheDirSuffix;
        $this->purgeProxyCache();
        $this->config->LastFetch = 0;
        $this->setConfig($this->config);
        
        // Rename cache files directory
        if (Filesystem::fileExists($this->getCacheDir()) && !Filesystem::rename($this->getCacheDir(), $staleCacheDir)) {
            throw new \Exception("No write permissions to rename the directory: " . $this->getCacheDir());
        }

        // Create a new empty directory
        if (!Filesystem::createDir($this->getCacheDir())) {
            throw new \Exception("No write permissions to create the directory: " . $this->getCacheDir());
        }

        if (!$quick) {
            // Finally, delete the files of the stale directory
            Filesystem::deleteDir($staleCacheDir);
        }

        $this->elementRevision->refresh();

        return $staleCacheDir;
    }

    public function fetchConfig($ignoreHealthStatus = false) {
        // TODO: Record failures and repeat with a delay
        $fetcher = new Api\RemoteConfigFetcher($this->siteId, $this->siteSecret);

        if (!$ignoreHealthStatus) {
            $fetcher->setBacklog($this->backlog);
            $fetcher->setNitroPack($this);
        }

        try {
            $configContents = $fetcher->get(); // this can throw in case of http errors or validation failures
        } catch (\Exception $e) {
            // TODO: Record this failure, possibly in the backlog
            throw $e;
        }

        $config = json_decode($configContents);
        if ($config) {
            $config->SDKVersion = NitroPack::VERSION;
            $config->LastFetch = time();

            $this->setConfig($config);
            return true;
        } else {
            throw new EmptyConfigException("Config response was empty");
        }
    }

    public function setConfig($config) {
        $file = $this->getConfigFile();
        if (Filesystem::createDir(dirname($file))) {
            if (Filesystem::filePutContents($file, json_encode($config))) {
                return true;
            } else {
                throw new StorageException(sprintf("Config file %s cannot be saved to disk", $file));
            }
        } else {
            throw new StorageException(sprintf("Storage directory %s cannot be created", dirname($file)));
        }
    }

    public function setVarnishProxyCacheHeaders($newHeaders) {
        $this->varnishProxyCacheHeaders = $newHeaders;
    }

    public function purgeProxyCache($url = NULL) {
        if (!empty($this->config->CacheIntegrations)) {
            if (!empty($this->config->CacheIntegrations->Varnish)) {
                if ($url) {
                    $url = $this->normalizeUrl($url);
                    $varnish = new Integrations\Varnish(
                        $this->config->CacheIntegrations->Varnish->Servers,
                        $this->config->CacheIntegrations->Varnish->PurgeSingleMethod,
                        $this->varnishProxyCacheHeaders
                    );
                    $varnish->purge($url);
                } else {
                    $varnish = new Integrations\Varnish(
                        $this->config->CacheIntegrations->Varnish->Servers,
                        $this->config->CacheIntegrations->Varnish->PurgeAllMethod,
                        $this->varnishProxyCacheHeaders
                    );
                    $varnish->purge($this->config->CacheIntegrations->Varnish->PurgeAllUrl);
                }
            }

            //if (!empty($this->config->CacheIntegrations->LiteSpeed) && php_sapi_name() !== "cli") {
            //    if ($url) {
            //        $urlObj = new Url($url);
            //        $liteSpeedPath = $urlObj->getPath();
            //        if ($urlObj->getQuery()) {
            //            $liteSpeedPath .= "?" . $urlObj->getQuery();
            //        }
            //        header("X-LiteSpeed-Purge: $liteSpeedPath", false);
            //    } else {
            //        header("X-LiteSpeed-Purge: *", false);
            //    }
            //}
        }
    }

    public function isAllowedUrl($url) {
        if (strpos($url, 'sucurianticache=') !== false) return false;

        if ($this->config->EnabledURLs->Status) {
            if (!empty($this->config->EnabledURLs->URLs)) {
                foreach ($this->config->EnabledURLs->URLs as $enabledUrl) {
                    $enabledUrlModified = preg_replace("/^(https?:)?\/\//", "*", $enabledUrl);

                    if (preg_match('/^' . self::wildcardToRegex($enabledUrlModified) . '$/', $url)) {
                        return true;
                    }
                }

                return false;
            }
        } else if ($this->config->DisabledURLs->Status) {
            if (!empty($this->config->DisabledURLs->URLs)) {
                foreach ($this->config->DisabledURLs->URLs as $disabledUrl) {
                    $disabledUrlModified = preg_replace("/^(https?:)?\/\//", "*", $disabledUrl);

                    if (preg_match('/^' . self::wildcardToRegex($disabledUrlModified) . '$/', $url)) {
                        return false; // don't cache disabled URLs
                    }
                }
            }
        }

        return true;
    }

    public function isAllowedRequest($allowServiceRequests = false) {
        if (($this->isAJAXRequest() && !$this->isAllowedAJAX()) || !($this->isRequestMethod("GET") || $this->isRequestMethod("HEAD"))) {// TODO: Allow URLs which match a pattern in the AJAX URL whitelist
            return false; // don't cache ajax or not GET requests
        }

        if (!$allowServiceRequests && isset($_SERVER["HTTP_X_NITROPACK_REQUEST"])) { // Skip requests coming from NitroPack
            return false;
        }

        if (isset($_GET["nonitro"])) { // Skip requests having ?nonitro
            return false;
        }

        if (!$this->isAllowedBrowser()) {
            return false;
        }

        if (isset($this->config->ExcludedCookies) && $this->config->ExcludedCookies->Status) {
            foreach ($this->config->ExcludedCookies->Cookies as $cookieExclude) {
                foreach (self::getCookies() as $cookieName => $cookieValue) {
                    if (preg_match('/^' . self::wildcardToRegex($cookieExclude->name) . '$/', $cookieName)) {
                        if (count($cookieExclude->values) == 0) {
                            return false; // no excluded cookie values entered, reject all values
                        } else {
                            foreach ($cookieExclude->values as $val) {
                                if (preg_match('/^' . self::wildcardToRegex($val) . '$/', $cookieValue)) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function isAllowedBrowser() {
        if (empty($_SERVER["HTTP_USER_AGENT"])) return true;

        if (preg_match("~MSIE|Internet Explorer~i", $_SERVER["HTTP_USER_AGENT"]) || strpos($_SERVER["HTTP_USER_AGENT"], "Trident/7.0; rv:11.0") !== false) { // Skip IE
            return false;
        }

        return true;
    }

    public function getScheme() {
        return $this->isSecure() ? 'https://' : 'http://';
    }

    public function isSecure() {
        $result = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && in_array("https", array_map("strtolower", array_map("trim", explode(",", $_SERVER['HTTP_X_FORWARDED_PROTO']))))) ||
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_SSL_FLAG']) && $_SERVER['HTTP_SSL_FLAG'] == 'SSL');

        if (!$result && !empty($_SERVER['HTTP_CF_VISITOR'])) {
            $visitor = json_decode($_SERVER['HTTP_CF_VISITOR']);
            $result = $visitor && property_exists($visitor, "scheme") && strtolower($visitor->scheme) == "https";
        }

        return $result;
    }

    public function isAJAXRequest() { 
        return 
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            $this->isAllowedAJAXUrl($this->url) ||
            $this->isAllowedStandaloneAJAXUrl($this->url);
    }

    public function isRequestMethod($method) {
        return empty($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] == $method;
    }

    public function isAllowedAJAX() {
        if (!$this->isAllowedStandaloneAJAXUrl($this->url)) {
            if (!$this->pageCache->getParent()) return false;
            if (!$this->pageCache->getParent()->hasCache() || $this->pageCache->getParent()->hasExpired($this->config->PageCache->ExpireTime)) return false;
        }
        return true;
    }

    public function isAllowedAJAXUrl($url) {
        if ($this->config->AjaxURLs->Status) {
            if (!empty($this->config->AjaxURLs->URLs)) {
                foreach ($this->config->AjaxURLs->URLs as $ajaxUrl) {
                    $ajaxUrlModified = preg_replace("/^(https?:)?\/\//", "*", $ajaxUrl);
                    if (preg_match('/^' . self::wildcardToRegex($ajaxUrlModified) . '$/', $url)) {
                        return true;
                    }
                }
                return false;
            }
        }
        return false;
    }

    public function isAllowedStandaloneAJAXUrl($url)
    {
        if ($this->config->AjaxURLs->Status) {
            if (!empty($this->config->AjaxURLs->StandaloneURLs)) {
                foreach ($this->config->AjaxURLs->StandaloneURLs as $ajaxUrl) {
                    $ajaxUrlModified = preg_replace("/^(https?:)?\/\//", "*", $ajaxUrl);
                    if (preg_match('/^' . self::wildcardToRegex($ajaxUrlModified) . '$/', $url)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function isCacheAllowed() {
        return $this->isAllowedRequest() && $this->isAllowedUrl($this->url);
    }

    public function isStatefulCacheSatisfied($type = NULL) {
        if ($this->config->StatefulCache->Status) {
            $foundTypeSelectors = false;
            foreach ($this->config->StatefulCache->Selectors as $selector) {
                if ($type !== NULL) {
                    if ($selector->type === $type) {
                        $foundTypeSelectors = true;
                    } else {
                        continue;
                    }
                }

                $selectorEncoded = str_replace("=", "", base64_encode($selector->string)); // base64 can produce == at the end which breaks is invalid for a cookie name, hence why we need to remove it
                $cookieKey = 'np-' . $selector->type . '-' . $selectorEncoded . '-override';
                if (empty($_COOKIE[$cookieKey]) || $_COOKIE[$cookieKey] != $this->elementRevision->get()) {
                    return false;
                }
            }

            return $type !== NULL ? $foundTypeSelectors : true;
        }

        return false;
    }

    public function purgeLocalUrlCache($url) {
        $url = $this->normalizeUrl($url);
        $this->purgeProxyCache($url);
        $localResult = true;
        $cacheDir = $this->getCacheDir();
        $knownDeviceTypes = Device::getKnownTypes();
        foreach ($knownDeviceTypes as $deviceType) {
            $urlDir = PageCache::getUrlDir($cacheDir . "/" . $deviceType, $url, false, $this->config->URLPathVersion);
            $invalidatedUrlDir = PageCache::getUrlDir($cacheDir . "/" . $deviceType, $url, true, $this->config->URLPathVersion);
            $localResult &= Filesystem::deleteDir($urlDir);
            $localResult &= Filesystem::deleteDir($invalidatedUrlDir);
        }
        return $localResult;
    }

    public function invalidateLocalUrlCache($url) {
        $url = $this->normalizeUrl($url);
        $this->purgeProxyCache($url);
        $localResult = true;
        $cacheDir = $this->getCacheDir();
        $knownDeviceTypes = Device::getKnownTypes();
        foreach ($knownDeviceTypes as $deviceType) {
            $urlDir = PageCache::getUrlDir($cacheDir . "/" . $deviceType, $url, false, $this->config->URLPathVersion);
            $urlDirInvalid = PageCache::getUrlDir($cacheDir . "/" . $deviceType, $url, true, $this->config->URLPathVersion);

            $this->invalidateDir($urlDir, $urlDirInvalid);
        }
        return $localResult;
    }

    public function invalidateLocalCache() {
        $this->purgeProxyCache();
        $this->config->LastFetch = 0;
        $this->setConfig($this->config);

        $cacheDir = $this->getCacheDir();
        $knownDeviceTypes = Device::getKnownTypes();
        foreach ($knownDeviceTypes as $deviceType) {
            $deviceTypeDir = $cacheDir . "/" . $deviceType;
            Filesystem::dirForeach($deviceTypeDir, function($urlDir) {
                if (substr($urlDir, -2) !== "_i") {
                    $this->invalidateDir($urlDir, $urlDir . "_i");
                }
            });
        }
        return true;
    }

    private function invalidateDir($urlDir, $urlDirInvalid) {
        if (Filesystem::fileExists($urlDirInvalid)) {
            Filesystem::dirForeach($urlDir, function($file) use ($urlDirInvalid) {
                Filesystem::rename($file, $urlDirInvalid . "/" . basename($file));
            });

            Filesystem::deleteDir($urlDir);
        } else {
            Filesystem::rename($urlDir, $urlDirInvalid);
        }
        Filesystem::touch($urlDirInvalid);
    }

    public function integrationUrl($widget, $version = null) {
        $integration = new IntegrationUrl($widget, $this->siteId, $this->siteSecret, $version);

        return $integration->getUrl();
    }

    public function embedJsUrl() {
        $embedjs = new Embedjs();

        return $embedjs->getUrl();
    }

    public function enableSafeMode() {
        $this->api->safe_mode->enable();
        $this->fetchConfig();
    }

    public function disableSafeMode() {
        $this->api->safe_mode->disable();
        $this->fetchConfig();
    }

    public function enableCartCache() {
        $this->api->enableCartCache();
    }

    public function disableCartCache() {
        $this->api->disableCartCache();
    }

    public function getStatefulCacheHandlerScript() {
        if ($this->config->StatefulCache->Status && $this->config->StatefulCache->HandlerScript) {
            $keyRevision = $this->elementRevision->get();
            return '<script id="nitro-stateful-cache" nitro-exclude>' . str_replace("KEY_REVISION", $keyRevision, $this->config->StatefulCache->HandlerScript) . '</script>';
        }

        return "";
    }

    private function loadConfig() {
        $file = $this->getConfigFile();

        $config = array();
        if (Filesystem::fileExists($file) || $this->fetchConfig(true)) {
            $config = json_decode(Filesystem::fileGetContents($file));
            if (empty($config->SDKVersion) || $config->SDKVersion !== NitroPack::VERSION || empty($config->LastFetch) || time() - $config->LastFetch >= $this->configTTL) {
                if ($this->getHealthStatus() === HealthStatus::HEALTHY) {
                    if ($this->fetchConfig()) {
                        $config = json_decode(Filesystem::fileGetContents($file));
                    } else {
                        throw new NoConfigException("Can't load config file");
                    }
                }
            }
            $this->config = $config;
        } else {
            throw new NoConfigException("Can't load config file");
        }
    }

    private function getConfigFile() {
        $configFile = $this->configFile;

        $filename = array_pop($configFile);

        $filename = $this->siteId . '-' . $filename;

        array_push($configFile, $filename);
        array_unshift($configFile, $this->dataDir);

        return Filesystem::getOsPath($configFile);
    }

    private function lockPageCache() {
        $filename = $this->getPageCacheLockFilename();

        if (Filesystem::fileExists($filename)) {
            $sem = 1 + (int)Filesystem::fileGetContents($filename);
        } else {
            $sem = 1;
        }

        return !!Filesystem::filePutContents($filename, $sem);
    }

    private function unlockPageCache() {
        $filename = $this->getPageCacheLockFilename();

        if (Filesystem::fileExists($filename)) {
            $sem = (int)Filesystem::fileGetContents($filename);

            $sem--;

            if ($sem <= 0) {
                return !!Filesystem::deleteFile($filename);
            } else {
                return !!Filesystem::filePutContents($filename, $sem);
            }
        }

        return false;
    }

    private function isPageCacheLocked() {
        $filename = $this->getPageCacheLockFilename();

        if (!Filesystem::fileExists($filename)) {
            return false;
        } else {
            if (time() - Filesystem::fileMTime($filename) <= self::PAGECACHE_LOCK_EXPIRATION_TIME) {
                return true;
            } else {
                Filesystem::deleteFile($filename);

                return false;
            }
        }

        // We should never get here, so consider this a default return value in case of future changes
        return false;
    }

    private function getPageCacheLockFilename() {
        $pageCacheLockFile = $this->pageCacheLockFile;
        array_unshift($pageCacheLockFile, $this->dataDir);
        return Filesystem::getOsPath($pageCacheLockFile);
    }

    private function normalizeUrl($url) {
        $urlObj = new Url($url);
        return $urlObj->getNormalized();
    }
}
