<?php
require_once dirname(__FILE__) . "/vendor/autoload.php";

spl_autoload_register(function ($class) {
    $file = str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php";
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    if (file_exists($path)) {
        require_once $path;
    }
});
