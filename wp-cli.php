<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
* Connects a website to NitroPack
*
* ## OPTIONS
*
* <siteID>
* : The site ID obtained from https://nitropack.io/user/connect
*
* <siteSecret>
* : The site secret obtained from https://nitropack.io/user/connect
*
* ## EXAMPLES
*
*     wp nitropack connect siteID siteSecret
*/

function nitropack_cli_connect($args, $assocArgs) {
    $siteId = !empty($args[0]) ? $args[0] : "";
    $siteSecret = !empty($args[1]) ? $args[1] : "";
    nitropack_verify_connect($siteId, $siteSecret);
}

/**
* Disconnects a website from NitroPack
*
* ## EXAMPLES
*
*     wp nitropack disconnect
*/

function nitropack_cli_disconnect($args, $assocArgs) {
    nitropack_disconnect();
}

/**
* Purges a website's cache
*
* ## EXAMPLES
*
*     wp nitropack purge
*/

function nitropack_cli_purge($args, $assocArgs) {
	$host = !empty($assocArgs["purge-host"]) ? $assocArgs["purge-host"] : NULL;
    $url = !empty($assocArgs["purge-url"]) ? $assocArgs["purge-url"] : NULL;
    $tag = !empty($assocArgs["purge-tag"]) ? $assocArgs["purge-tag"] : NULL;
    $reason = !empty($assocArgs["purge-reason"]) ? $assocArgs["purge-reason"] : NULL;

	if (!empty($host)) {
		/**
		 * Override the site url by the purge-host parameter
		 *
		 * @param string $host
		 * @return string
		 */
		add_filter( 'nitropack_current_host', function() use ( $host ) {
				if (!preg_match('#^http(s)?://#', $host)) {
					$host = 'https://' . $host;
				}
				return $host;
			}
		);
	}

    if ($url || $tag || $reason) {
        try {
            if (nitropack_sdk_purge($url, $tag, $reason)) {
                nitropack_json_and_exit(array(
                    "type" => "success",
                    "message" => __( 'Success! Cache has been purged successfully!', 'nitropack' )
                ));
            }
        } catch (\Exception $e) {}

        nitropack_json_and_exit(array(
            "type" => "error",
            "message" => __( 'Error! There was an error and the cache was not purged!', 'nitropack' )
        ));
    } else {
        nitropack_purge_cache();
    }
}

/**
* Invalidate a website's cache
*
* ## EXAMPLES
*
*     wp nitropack invalidate
*/

function nitropack_cli_invalidate($args, $assocArgs) {
    $url = !empty($assocArgs["purge-url"]) ? $assocArgs["purge-url"] : NULL;
    $tag = !empty($assocArgs["purge-tag"]) ? $assocArgs["purge-tag"] : NULL;
    $reason = !empty($assocArgs["purge-reason"]) ? $assocArgs["purge-reason"] : NULL;
    if ($url || $tag || $reason) {
        try {
            if (nitropack_sdk_invalidate($url, $tag, $reason)) {
                nitropack_json_and_exit(array(
                    "type" => "success",
                    "message" => __( 'Success! Cache has been invalidated successfully!', 'nitropack' )
                ));
            }
        } catch (\Exception $e) {}

        nitropack_json_and_exit(array(
            "type" => "error",
            "message" => __( 'Error! There was an error and the cache was not invalidated!', 'nitropack' )
        ));
        nitropack_sdk_invalidate($url, $tag, $reason);
    } else {
        nitropack_invalidate_cache();
    }
}

/**
* Start a warmup process for a website
*
* ## EXAMPLES
*
*     wp nitropack warmup
*/

function nitropack_cli_warmup($args, $assocArgs) {
    nitropack_run_warmup();
}

WP_CLI::add_command("nitropack connect", "nitropack_cli_connect");
WP_CLI::add_command("nitropack disconnect", "nitropack_cli_disconnect");
WP_CLI::add_command("nitropack purge", "nitropack_cli_purge");
WP_CLI::add_command("nitropack invalidate", "nitropack_cli_invalidate");
WP_CLI::add_command("nitropack warmup", "nitropack_cli_warmup");
