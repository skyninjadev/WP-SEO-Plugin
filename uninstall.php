<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$nitropackOptions = array(
    'nitropack-siteId',
    'nitropack-siteSecret',
    'nitropack-enableCompression',
    'nitropack-webhookToken',
    'nitropack-checkedCompression',
    'nitropack-cacheablePostTypes',
    'nitropack-wcNotice',
    'nitropack-safeModeStatus',
    'nitropack-bbCacheSyncPurge',
    'nitropack-legacyPurge',
    'nitropack-distribution',
);
if (defined('MULTISITE') && MULTISITE) {
    $blogs = array_map(function($blog) { return $blog->blog_id; }, get_sites());

    foreach ($nitropackOptions as $optionName) {
        foreach ($blogs as $blogId) {
            delete_blog_option($blogId, $optionName);
        }
    }
} else {
    foreach ($nitropackOptions as $optionName) {
        delete_option($optionName);
    }
}

require_once 'nitropack-sdk/autoload.php';
require_once 'constants.php';
NitroPack\SDK\Filesystem::deleteDir(NITROPACK_DATA_DIR);
