<?php
namespace NitroPack\WordPress;

class Config {
    private $config;

    public function __construct() {
        $this->config = NULL;
    }

    public function get() {
        if ($this->config) {
            return $this->config;
        }

        $config = [];

        if ($this->exists()) {
            $config = json_decode(file_get_contents(NITROPACK_CONFIG_FILE), true); // TODO: Convert this to use the Filesystem abstraction for better Redis support
            if (!empty($config['config_path']) && $config['config_path'] != md5(NITROPACK_DATA_DIR)) {
                $config = [];
            }
        }

        $this->config = $config;
        return $config;
    }

    public function set($config) {
        $np = NitroPack::getInstance();
        if (!$np->dataDirExists() && !$np->initDataDir()) return false;
        $config['config_path'] = md5(NITROPACK_DATA_DIR);
        $this->config = $config;
        return WP_DEBUG ? file_put_contents(NITROPACK_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT)) : @file_put_contents(NITROPACK_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT)); // TODO: Convert this to use the Filesystem abstraction for better Redis support
    }

    // Used when changing the location of the data dir
    public function updateConfigPath() {
		$config = json_decode(file_get_contents(NITROPACK_CONFIG_FILE), true); // TODO: Convert this to use the Filesystem abstraction for better Redis support
        $config['config_path'] = md5(NITROPACK_DATA_DIR);
        return WP_DEBUG ? file_put_contents(NITROPACK_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT)) : @file_put_contents(NITROPACK_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT)); // TODO: Convert this to use the Filesystem abstraction for better Redis support
    }

    public function exists() {
        return defined("NITROPACK_CONFIG_FILE") && file_exists(NITROPACK_CONFIG_FILE); // TODO: Convert this to use the Filesystem abstraction for better Redis support
    }
}
