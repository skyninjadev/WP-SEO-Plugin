<?php

class NitroPack_CF_Helper extends \CF\WordPress\Hooks {
    public function isApoEnabled() {
        return $this->isAutomaticPlatformOptimizationEnabled();
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
