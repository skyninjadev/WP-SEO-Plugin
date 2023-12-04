<?php

namespace NitroPack\SDK;

use \NitroPack\SDK\Website as Website;

class Api
{
    private $allowedWebhooks = array('config', 'cache_clear', 'cache_ready', 'sitemap');
    private $children;

    public function __construct($siteId, $siteSecret)
    {
        $this->children = [];
        $this->children["cache"] = new Api\Cache($siteId, $siteSecret);
        $this->children["tagger"] = new Api\Tagger($siteId, $siteSecret);
        $this->children["url"] = new Api\Url($siteId, $siteSecret);
        $this->children["stats"] = new Api\Stats($siteId, $siteSecret);
        $this->children["webhook"] = new Api\Webhook($siteId, $siteSecret);
        $this->children["warmup"] = new Api\Warmup($siteId, $siteSecret);
        $this->children["integration"] = new Api\Integration($siteId, $siteSecret);
        $this->children["variation_cookie"] = new Api\VariationCookie($siteId, $siteSecret);
        $this->children["safe_mode"] = new Api\SafeMode($siteId, $siteSecret);
        $this->children["request_maker"] = new Api\RequestMaker($siteId);
        $this->children["secure_request_maker"] = new Api\SecureRequestMaker($siteId, $siteSecret);
        $this->children["varnish"] = new Api\Varnish($siteId, $siteSecret);
        $this->children["excludedurls"] = new Api\ExcludedUrls($siteId, $siteSecret);
        $this->children["additionaldomains"] = new Api\AdditionalDomains($siteId, $siteSecret);
        $this->children["excludes"] = new Api\Excludes($siteId, $siteSecret);
    }

    public function __get($prop)
    {
        return !empty($this->children[$prop]) ? $this->children[$prop] : NULL;
    }

    public function setBacklog($backlog)
    {
        foreach ($this->children as $child) {
            $child->setBacklog($backlog);
        }
    }

    public function setNitroPack($nitropack)
    {
        foreach ($this->children as $child) {
            $child->setNitroPack($nitropack);
        }
    }

    public function getCache($url, $userAgent, $cookies, $isAjax, $layout, $referer)
    {
        return $this->cache->get($url, $userAgent, $cookies, $isAjax, $layout, NULL, $referer);
    }

    public function getCacheMulti($urls, $userAgent, $cookies, $isAjax, $layout, $referer)
    {
        return $this->cache->getMulti($urls, $userAgent, $cookies, $isAjax, $layout, NULL, $referer);
    }

    public function getLastCachePurge()
    {
        return $this->cache->getLastPurge();
    }

    public function purgeCache($url = NULL, $pagecacheOnly = false, $reason = NULL, $lightPurge = false)
    {
        return $this->cache->purge($url, $pagecacheOnly, $reason, $lightPurge);
    }

    public function purgeCacheByTag($tag, $reason = NULL, $lightPurge = false)
    {
        return $this->cache->purgeByTag($tag, $reason, $lightPurge);
    }

    public function getUrls($page = 1, $limit = 250, $search = NULL, $deviceType = NULL, $status = NULL)
    {
        return $this->url->get($page, $limit, $search, $deviceType, $status);
    }

    public function getUrlsCount($search = NULL, $deviceType = NULL, $status = NULL)
    {
        $resp = $this->url->count($search, $deviceType, $status);
        return (int)$resp["count"];
    }

    public function getPendingUrls($page = 1, $limit = 250, $priority = NULL)
    {
        return $this->url->getpending($page, $limit, $priority);
    }

    public function getPendingUrlsCount($priority = NULL)
    {
        $resp = $this->url->pendingCount($priority);
        return (int)$resp["count"];
    }

    public function tagUrl($url, $tag)
    {
        $resp = @json_decode($this->tagger->tag($url, $tag)->getBody());
        if ($resp && !$resp->success) {
            $msg = $resp->error ? $resp->error : "Unable to tag URL";
            throw new \RuntimeException($msg);
        }

        return true;
    }

    public function untagUrl($url, $tag)
    {
        $resp = json_decode($this->tagger->remove($url, $tag)->getBody(), true);
        return $resp['removed'];
    }

    public function getTaggedUrls($tag, $page = 1, $limit = 250)
    {
        return $this->tagger->getUrls($tag, $page, $limit);
    }

    public function getTaggedUrlsCount($tag)
    {
        $resp = $this->tagger->getUrlsCount($tag);
        return (int)$resp["count"];
    }

    public function getTags($url = NULL, $page = 1, $limit = 250)
    {
        return $this->tagger->getTags($url, $page, $limit);
    }

    public function getSavings()
    {
        return $this->stats->getSavings();
    }

    public function getDiskUsage()
    {
        return $this->stats->getDiskUsage();
    }

    public function getRequestUsage()
    {
        return $this->stats->getRequestUsage();
    }

    public function resetSavingsStats()
    {
        return $this->stats->resetSavings();
    }

    public function enableWarmup()
    {
        return $this->warmup->enable();
    }

    public function disableWarmup()
    {
        return $this->warmup->disable();
    }

    public function resetWarmup()
    {
        return $this->warmup->reset();
    }

    public function setWarmupSitemap($url)
    {
        return $this->warmup->setSitemap($url);
    }

    public function unsetWarmupSitemap()
    {
        return $this->warmup->setSitemap(NULL);
    }

    public function setWarmupHomepage($url)
    {
        return $this->warmup->setHomepage($url);
    }

    public function unsetWarmupHomepage()
    {
        return $this->warmup->setHomepage(NULL);
    }

    public function estimateWarmup($id = NULL, $urls = NULL)
    {
        $resp = $this->warmup->estimate($id, $urls);
        if ($id) {
            return (int)$resp["count"];
        } else {
            return $resp["id"];
        }
    }

    public function runWarmup($urls = NULL, $force = false)
    {
        return $this->warmup->run($urls, $force);
    }

    public function getWarmupStats()
    {
        return $this->warmup->stats();
    }

    public function unsetWebhook($type)
    {
        if (!in_array($type, $this->allowedWebhooks)) {
            throw new WebhookException("The webhook type '$type' is not supported!");
        }

        try {
            $this->webhook->set($type, null);
        } catch (\RuntimeException $e) {
            throw new WebhookException($e->getMessage());
        }
    }

    public function setWebhook($type, \NitroPack\Url\Url $url)
    {
        if (!in_array($type, $this->allowedWebhooks)) {
            throw new WebhookException("The webhook type '$type' is not supported!");
        }

        if (!$url->isValid()) {
            throw new WebhookException(sprintf("The webhook URL '%s' is invalid!", $url->getUrl()));
        }

        try {
            $this->webhook->set($type, $url->getUrl());
        } catch (\RuntimeException $e) {
            throw new WebhookException($e->getMessage());
        }
    }

    public function getWebhook($type)
    {
        if (!in_array($type, $this->allowedWebhooks)) {
            throw new WebhookException("The webhook type '$type' is not supported!");
        }

        try {
            $resp = $this->webhook->get($type);
            return $resp["url"];
        } catch (\RuntimeException $e) {
            throw new WebhookException($e->getMessage());
        }
    }

    public function setVariationCookie($name, $values = array(), $group = null)
    {
        if (!is_array($values) && !is_string($values)) {
            throw new VariationCookieException("The provided values is not an array or a string.");
        } else if (is_array($values)) {
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new VariationCookieException("A non-string cookie value has been detected.");
                }
            }
        }

        if (!is_string($name) || trim($name) == "") {
            throw new VariationCookieException("The provided cookie name is not a string or is empty.");
        }

        if (null !== $group && !filter_var($group, FILTER_VALIDATE_INT)) {
            throw new VariationCookieException("The provided group is not null, and it is not a positive integer.");
        }

        try {
            return $this->variation_cookie->set($name, $values, $group);
        } catch (\RuntimeException $e) {
            throw new VariationCookieException($e->getMessage());
        }
    }

    public function unsetVariationCookie($name)
    {
        if (!is_string($name) || trim($name) == "") {
            throw new VariationCookieException("The provided cookie name is not a string or is empty.");
        }

        try {
            return $this->variation_cookie->delete($name);
        } catch (\RuntimeException $e) {
            throw new VariationCookieException($e->getMessage());
        }
    }

    public function getVariationCookies()
    {
        try {
            return $this->variation_cookie->get();
        } catch (\RuntimeException $e) {
            throw new VariationCookieException($e->getMessage());
        }
    }

    public function createWebsite(Website $website)
    {
        return $this->integration->create($website);
    }

    public function updateWebsite(Website $website)
    {
        return $this->integration->update($website);
    }

    public function removeWebsite($apikey)
    {
        return $this->integration->remove($apikey);
    }

    // Get a single website via API key
    public function getWebsiteByAPIKey($apikey)
    {
        return $this->integration->readByAPIKey($apikey);
    }

    // Get all websites, along with a corresponding pagination
    public function getWebsitesPaginated($page, $limit = 250)
    {
        return $this->integration->readPaginated($page, $limit);
    }

    public function isSafeModeEnabled()
    {
        $status = $this->safe_mode->status();
        return !empty($status->isEnabled);
    }

    public function enableCartCache()
    {
        $this->cache->enableCartCache();
    }

    public function disableCartCache()
    {
        $this->cache->disableCartCache();
    }

    public function enableVarnishIntegration()
    {
        $this->varnish->enable();
    }

    public function disableVarnishIntegration()
    {
        $this->varnish->disable();
    }

    public function configureVarnishIntegration($settings)
    {
        $this->varnish->configure($settings);
    }

    public function enableExcludedUrls()
    {
        $this->excludedurls->enable();
    }

    public function disableExcludedUrls()
    {
        $this->excludedurls->disable();
    }

    public function addExcludedUrl($urlPattern)
    {
        $this->excludedurls->add($urlPattern);
    }

    public function removeExcludedUrl($urlPattern)
    {
        $this->excludedurls->remove($urlPattern);
    }

    public function enableAdditionalDomains()
    {
        $this->additionaldomains->enable();
    }

    public function disableAdditionalDomains()
    {
        $this->additionaldomains->disable();
    }

    public function addAdditionalDomain($domain)
    {
        $this->additionaldomains->add($domain);
    }

    public function removeAdditionalDomain($domain)
    {
        $this->additionaldomains->remove($domain);
    }

    public function enableExcludes()
    {
        return $this->excludes->enable();
    }

    public function disableExcludes()
    {
        return $this->excludes->disable();
    }

    public function getExcludes()
    {
        return $this->excludes->get();
    }

    public function setExcludes($excludes)
    {
        return $this->excludes->set($excludes);
    }
}
