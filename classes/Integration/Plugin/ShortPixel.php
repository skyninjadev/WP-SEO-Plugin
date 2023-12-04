<?php

namespace NitroPack\Integration\Plugin;

class ShortPixel {
    const STAGE = "late";

    public function init($stage) {
        if (defined('SHORTPIXEL_AI_VERSION')) { // ShortPixel
            if (nitropack_is_ajax()) {
                if (version_compare(SHORTPIXEL_AI_VERSION, "2", ">=")) { // ShortPixel AI 2.x
                    remove_action('wp_enqueue_scripts', array(\ShortPixelAI::_(), 'enqueue_script'));
                    remove_action('init', array(\ShortPixelAI::_(), 'init_ob'), 1);
                    remove_filter('script_loader_tag', array(\ShortPixelAI::_(), 'disable_rocket-Loader'), 10);
                } else { // ShortPixel AI 1.x
                    remove_action('wp_enqueue_scripts', array(\ShortPixelAI::instance(SHORTPIXEL_AI_PLUGIN_FILE), 'enqueue_script'), 11);
                    remove_action('init', array(\ShortPixelAI::instance(SHORTPIXEL_AI_PLUGIN_FILE), 'init_ob'), 1);
                    remove_filter('rocket_css_content', array(\ShortPixelAI::instance(SHORTPIXEL_AI_PLUGIN_FILE), 'parse_cached_css'), 10);
                    remove_filter('script_loader_tag', array(\ShortPixelAI::instance(SHORTPIXEL_AI_PLUGIN_FILE), 'disable_rocket-Loader'), 10);
                }
            }
        }
    }
}
