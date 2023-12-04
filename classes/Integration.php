<?php

namespace NitroPack;

class Integration {
    const CRITICAL_INIT_ACTION = "nitropack_integration_critical_init";
    public static $criticalInitSemaphore = 0;

    private static $instance = NULL;
    private static $purgeAllPending = false;
    private static $purgeUrlPending = [];
    private static $isInitialized = false;
    private static $isInitializedStage = [];
    private static $shutdownCallbacks = [];
    private static $modules = [
        "NitroPack/Integration/Hosting/Cloudways",
        "NitroPack/Integration/Hosting/Flywheel",
        "NitroPack/Integration/Hosting/WPEngine",
        "NitroPack/Integration/Hosting/SiteGround",
        "NitroPack/Integration/Hosting/GoDaddyWPaaS",
        "NitroPack/Integration/Hosting/Kinsta",
        "NitroPack/Integration/Hosting/Pagely",
        "NitroPack/Integration/Hosting/Vimexx",
        "NitroPack/Integration/Hosting/Pressable",
        "NitroPack/Integration/Hosting/RocketNet",
        "NitroPack/Integration/Hosting/Savvii",
        "NitroPack/Integration/Hosting/DreamHost",
        "NitroPack/Integration/Hosting/WPX",
        "NitroPack/Integration/Hosting/WPmudev",
        "NitroPack/Integration/Hosting/Raidboxes",
        "NitroPack/Integration/Hosting/Pantheon",
        "NitroPack/Integration/Server/LiteSpeed",
        "NitroPack/Integration/Server/Fastly",
        "NitroPack/Integration/Server/Cloudflare",
        "NitroPack/Integration/Server/Sucuri",
        "NitroPack/Integration/Plugin/NginxHelper",
        "NitroPack/Integration/Plugin/Cloudflare",
        "NitroPack/Integration/Plugin/ShortPixel",
        "NitroPack/Integration/Plugin/WPCacheHelper",
        "NitroPack/Integration/Plugin/CookieNotice",
        "NitroPack/Integration/Plugin/BeaverBuilder",
        "NitroPack/Integration/Plugin/FusionBuilder",
        "NitroPack/Integration/Plugin/ThriveTheme",
        "NitroPack/Integration/Plugin/AeliaCurrencySwitcher",
        "NitroPack/Integration/Plugin/Woocommerce",
        "NitroPack/Integration/Plugin/WoocommerceCacheHandler",
        "NitroPack/Integration/Plugin/AdvancedMathCaptcha",
        "NitroPack/Integration/Plugin/TheEventsCalendar",
	    "NitroPack/Integration/Plugin/WCML",
        "NitroPack/Integration/Plugin/YoastSEO",
        "NitroPack/Integration/Plugin/JetPackNP",
        "NitroPack/Integration/Plugin/SquirrlySEO",
	    "NitroPack/Integration/Plugin/RankMathNP",
	    "NitroPack/Integration/Plugin/WPBakeryNP",
	    //"NitroPack/Integration/Plugin/GravityForms",
	    "NitroPack/Integration/Plugin/Elementor",
	    "NitroPack/Integration/Plugin/WPForms",
        "NitroPack/Integration/Plugin/GeoTargetingWP",
    ];
    private static $loadedModules = [];
    private static $stage = "very_early";
    private $siteConfig = [];
    private $purgeUrls = [];
    private $fullPurge = false;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Integration();
        }
        return self::$instance;
    }

    public static function onCriticalInit($callback) {
        if (!did_action(self::CRITICAL_INIT_ACTION)) {
            add_action(self::CRITICAL_INIT_ACTION, $callback);
        } else {
            $callback();
        }
    }

    public static function onShutdown($callback) {
        self::$shutdownCallbacks[] = $callback;
    }

    public static function initSemAcquire() {
        self::$criticalInitSemaphore++;
    }

    public static function initSemRelease() {
        if (--self::$criticalInitSemaphore < 0) {
            self::$criticalInitSemaphore = 0;
        }
    }

    public function __construct() {
        $this->siteConfig = nitropack_get_site_config();
    }

    public function getHosting() {
        return $this->siteConfig && !empty($this->siteConfig["hosting"]) ? $this->siteConfig["hosting"] : "unknown";
    }

    public function init() {
        if (self::$isInitialized) return true;

        add_action( 'nitropack_integration_purge_url', [$this, "logUrlPurge"] );
        add_action( 'nitropack_integration_purge_all', [$this, "logFullPurge"] );
        self::onShutdown([$this, 'executeIntegrationPurges']);
        register_shutdown_function(function() {
            foreach (self::$shutdownCallbacks as $callback) {
                $callback();
            }
        });

        if ($this->siteConfig && !empty($this->siteConfig["isLateIntegrationInitRequired"])) {
            self::initSemAcquire();
        }

        $this->initModules(); // very_early init

        $action = $this->getSetupAction(); // can be muplugins_loaded or plugins_loaded
        if (did_action($action)) {
            $this->initModules();
        } else {
            add_action($action, [$this, 'initModules']);
        }

        if (did_action('plugins_loaded')) {
            $this->lateInitModules();
        } else {
            add_action('plugins_loaded', [$this, 'lateInitModules']);
        }

        self::$isInitialized = true;
    }

    public function logUrlPurge($url) {
        $this->purgeUrls[] = $url;
    }

    public function logFullPurge() {
        $this->fullPurge = true;
    }

    public function initModules() {
        if (!empty(self::$isInitializedStage[self::$stage])) return true;

        foreach (self::$modules as $moduleName) {
            $module = $this->loadModule($moduleName);
            if ($module && $module->init(self::$stage)) {
                // Modules which need to be initialized only once return NULL so they don't end up in this array
                // This array holds only modules which need to have their init method called for each stage
                self::$loadedModules[$moduleName] = $module;
            }
        }

        self::$isInitializedStage[self::$stage] = true;

        if (self::$criticalInitSemaphore < 1 && !did_action(self::CRITICAL_INIT_ACTION)) {
            do_action(self::CRITICAL_INIT_ACTION);
        }

        if (self::$stage == "very_early") {
            self::$stage = "early";
        }
    }

    public function lateInitModules() {
        self::$stage = "late";
        if ($this->siteConfig && !empty($this->siteConfig["isLateIntegrationInitRequired"])) {
            self::initSemRelease();
        }
        $this->initModules();
    }

    public function executeIntegrationPurges() {
        if ($this->fullPurge) {
            do_action("nitropack_execute_purge_all");
        } else {
            foreach (array_unique($this->purgeUrls) as $url) {
                do_action("nitropack_execute_purge_url", $url);
            }
        }
    }

    private function loadModule($name) {
        if (isset(self::$loadedModules[$name])) return self::$loadedModules[$name];

        $class = str_replace("/", "\\", $name);
        if ($class::STAGE == self::$stage) {
            $module = new $class();
            return $module;
        } else {
            return NULL;
        }
    }

    private function getSetupAction() {
        if ($this->siteConfig && !empty($this->siteConfig["isLateIntegrationInitRequired"])) {
            return "plugins_loaded";
        }

        return "muplugins_loaded";
    }
}
