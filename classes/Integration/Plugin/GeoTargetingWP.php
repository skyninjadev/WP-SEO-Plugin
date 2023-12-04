<?php

namespace NitroPack\Integration\Plugin;

class GeoTargetingWP {
    const STAGE = "very_early";
    const allGeoWpCookies = ['geot_rocket_country', 'geot_rocket_state', 'geot_rocket_city'];
    const defaultVariationCookies = ['geot_rocket_country'];
    private $printedCookies = [];

    public static function isActive() {
        return defined("GEOWP_VERSION");
    }

    public function init($stage) {
        $siteConfig = get_nitropack()->getSiteConfig();
        $geotSettings = null;

        if (empty($siteConfig["isGeoTargetingWPActive"])) {
            return true;
        }

        // no need for variation cookies with GEOWP if using Ajax mode
        if (function_exists( 'geot_settings' )) {
            $geotSettings = geot_settings();
        } elseif (function_exists('get_option')) {
            $geotSettings = apply_filters( 'geot/settings_page/opts', get_option( 'geot_settings' ) );
        }

        if (!empty($geotSettings) && !empty($geotSettings['ajax_mode'])) {
            return true;
        }

        // enable geot cookies
        add_filter( 'geot/enable_cookies', '__return_true');

        // require geot cookies for serving cache
        add_filter("nitropack_passes_cookie_requirements", [$this, "hasGeoTargetingWpCookies"]);

        // serve cache after geowp has added geot cookies
        add_action('init', function() {
            nitropack_handle_request('geotargetingwp');
        }, 16);

        add_action('np_set_cookie_filter', function() {
            \NitroPack\SDK\NitroPack::addCookieFilter([$this, "filterCookies"]);
        });

        return true;
    }

    public static function getCustomVariationCookies() {
        $enabledCookies = self::defaultVariationCookies;
        // apply_filter() is unavailable at stage 'very_early'
        // $enabledCookies = apply_filter("nitropack_geotargetingwp_enabled_cookies", self::defaultVariationCookies);
        return array_intersect(self::allGeoWpCookies, $enabledCookies);
    }

    public static function configureVariationCookies() {
        $siteConfig = get_nitropack()->getSiteConfig();

        if (empty($siteConfig["isGeoTargetingWPActive"])) {
            removeVariationCookies(self::allGeoWpCookies);
            return true;
        }

        // standard cookie integration
        initVariationCookies(self::getCustomVariationCookies());
    }

    public function hasGeoTargetingWpCookies($currentState) {
        $allCookies = array_merge($_COOKIE, getNewCookies());
        $neededCookies = self::getCustomVariationCookies();

        foreach($neededCookies as $c) {
            if (!empty($allCookies[$c])) {
                // Needed so reverse proxies don't end up caching these pages.
                if (!in_array($c, $this->printedCookies)) {
                    $val = $allCookies[$c];
                    if (is_array($val)) {
                        $val = end($val);
                    }
                    setcookie($c, $val, time() + 86000);
                    $this->printedCookies[] = $c;
                }
                $neededCookies = array_diff($neededCookies, [$c]);
            }
        }

        if (!empty($neededCookies)) {
            return false;
        }
        return $currentState;
    }

    public function filterCookies(&$cookies) {
        foreach (self::getCustomVariationCookies() as $cookieName) {
            $newlySetCookie = getNewCookie($cookieName);
            if (empty($_COOKIE[$cookieName]) && !empty($newlySetCookie)) {
                $cookies[$cookieName] = $newlySetCookie;
            }
        }
    }
}
