<?php

namespace NitroPack;

use NitroPack\Integration\Plugin\AeliaCurrencySwitcher;
use NitroPack\Integration\Plugin\GeoTargetingWP;

class PluginStateHandler {
    const eventHandlersMap = [
        'woocommerce-aelia-currencyswitcher/woocommerce-aelia-currencyswitcher.php' =>[
            'activateCallback' => 'HandleAeliaCurrencyActivation',
            'deactivateCallback' => 'HandleAeliaCurrencyDeactivation',
        ],
    ];
    private static $instance;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new PluginStateHandler();
        }
        return self::$instance;
    }

    public static function init() {
        add_action('activated_plugin', [self::getInstance(), 'handleActivation'], 10, 1);
        add_action('deactivated_plugin', [self::getInstance(), 'handleDeactivation'], 10, 1);
    }

    public function handleActivation($plugin) {
        if (array_key_exists($plugin, self::eventHandlersMap) && !empty(self::eventHandlersMap[$plugin]['activateCallback'])) {
            self::{self::eventHandlersMap[$plugin]['activateCallback']}();
        }
    }

    public function handleDeactivation($plugin) {
        if (array_key_exists($plugin, self::eventHandlersMap) && !empty(self::eventHandlersMap[$plugin]['deactivateCallback'])) {
            self::{self::eventHandlersMap[$plugin]['deactivateCallback']}();
        }
    }

    // maybe have these handlers be part of each plugin compatibility class (maybe even have a class PluginCompatibility that they extend).
    public static function HandleAeliaCurrencyActivation() {
        initVariationCookies(AeliaCurrencySwitcher::customVariationCookies);
    }

    public static function HandleAeliaCurrencyDeactivation() {
        removeVariationCookies(AeliaCurrencySwitcher::customVariationCookies);
    }

    public static function HandleGeowpActivation() {
        initVariationCookies(GeoTargetingWP::getCustomVariationCookies());
    }

    public static function HandleGeowpDeactivation() {
        removeVariationCookies(GeoTargetingWP::allGeoWpCookies);
    }
}
