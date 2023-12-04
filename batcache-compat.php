<?php
define("NITROPACK_BATCACHE_COMPAT", true);
$batcache["unique"][] = !empty($_SERVER["HTTP_USER_AGENT"]) && preg_match("/(Android|Mobile|iPod|iPhone|MobileSafari|webOS|BlackBerry|windows phone|symbian|vodafone|opera mini|windows ce|smartphone|palm|midp)/i", $_SERVER["HTTP_USER_AGENT"]) ? "mobile" : "desktop";
$batcache["cache_control"] = false;
$batcache["use_stale"] = false;
$batcache["times"] = 1;
