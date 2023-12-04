<?php

namespace NitroPack\Integration\Plugin;

class CF_Helper extends \CF\WordPress\Hooks {
    public function isApoEnabled() {
        if (method_exists($this, "isAutomaticPlatformOptimizationEnabled")) {
            return $this->isAutomaticPlatformOptimizationEnabled();
        } else {
            return false;
        }
    }

	public function isApoCacheByDeviceTypeEnabled()
	{
		if (method_exists($this, "isAutomaticPlatformOptimizationCacheByDeviceTypeEnabled")) {
			return $this->isAutomaticPlatformOptimizationCacheByDeviceTypeEnabled();
		} else {
			return false;
		}
	}

    public function purgeUrl($url) {
        $wpDomainList = $this->integrationAPI->getDomainList();
        if (!count($wpDomainList)) {
            return;
        }
        $wpDomain = $wpDomainList[0];
        $urls = [$url];

        $zoneTag = $this->api->getZoneTag($wpDomain);

        if (isset($zoneTag) && !empty($urls)) {
            $chunks = array_chunk($urls, 30);

            foreach ($chunks as $chunk) {
                $this->api->zonePurgeFiles($zoneTag, $chunk);
            }
        }
    }
}

