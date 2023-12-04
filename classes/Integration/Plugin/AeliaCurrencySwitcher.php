<?php

namespace NitroPack\Integration\Plugin;

class AeliaCurrencySwitcher {
    const STAGE = "very_early";
    const customVariationCookies = ['aelia_cs_selected_currency', 'aelia_customer_country'];

    public static function isActive() {
        return class_exists("\Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher");
    }

    public function init($stage) {
        $siteConfig = get_nitropack()->getSiteConfig();

        if (empty($siteConfig["isAeliaCurrencySwitcherActive"])) {
            return true; // ACS is not active
        }

        if (!self::isAeliaGeolocationEnabled() || self::doesWoocommerceHandleCache() || self::doesCacheHandlerHandleCache()) {
            return true;
        }

        // use CloudFlare geolocation if available
        if (isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
            add_action('set_nitropack_geo_cache_prefix', function() {
                \NitroPack\SDK\NitroPack::addCustomCachePrefix($_SERVER["HTTP_CF_IPCOUNTRY"]);
            });
            return true;
        }

        add_filter("nitropack_passes_cookie_requirements", [$this, "canServeCache"]);
    }

    public static function configureVariationCookies() {
        $siteConfig = get_nitropack()->getSiteConfig();

        if (empty($siteConfig["isAeliaCurrencySwitcherActive"])) {
            removeVariationCookies(self::customVariationCookies);
            return true;
        }

        // Check if ACS is configured to not geolocate or geolocation is already with caching
        if (!self::isAeliaGeolocationEnabled() || self::doesWoocommerceHandleCache() || self::doesCacheHandlerHandleCache()) {
            removeVariationCookies(self::customVariationCookies);
            return true;
        }

        // standard cookie integration
        initVariationCookies(self::customVariationCookies);;
    }

    public function canServeCache($currentState) {
        // some websites only use aelia_cs_selected_currency, but check all cookies just in case.
        if (empty($_COOKIE["aelia_cs_selected_currency"])
            && empty($_COOKIE["aelia_customer_country"])
            && empty($_COOKIE["aelia_customer_state"])
            && empty($_COOKIE["aelia_tax_exempt"])
        ) {
            nitropack_header("X-Nitro-Disabled-Reason: Aelia cookie bypass");
            return false;
        }

        return $currentState;
    }

    public static function isAeliaGeolocationEnabled() {
        $siteConfig = get_nitropack()->getSiteConfig();

        return !empty($siteConfig['options_cache']['wc_aelia_currency_switcher']['ipgeolocation_enabled'])
            && $siteConfig['options_cache']['wc_aelia_currency_switcher']['ipgeolocation_enabled'] == 1;
    }

    public static function doesWoocommerceHandleCache() {
        $siteConfig = get_nitropack()->getSiteConfig();

        return !empty($siteConfig['isWoocommerceActive'])
         && !empty($siteConfig['options_cache']['woocommerce_default_customer_address'])
         && "geolocation_ajax" === $siteConfig['options_cache']['woocommerce_default_customer_address'];
    }

    public static function doesCacheHandlerHandleCache() {
        $siteConfig = get_nitropack()->getSiteConfig();

        return !empty($siteConfig['isWoocommerceCacheHandlerActive'])
        && !empty($siteConfig['options_cache']['cache_handler_cache_handler'])
        && in_array($siteConfig['options_cache']['cache_handler_cache_handler'], ['enable_redirect', 'enable_ajax']);
    }
}
