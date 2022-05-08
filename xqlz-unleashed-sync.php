<?php
/**
 * Plugin Name:     Xqlz Unleashed Sync
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     xqlz-unleashed-sync
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Xqlz_Unleashed_Sync
 */

// Your code starts here.
use XqluzSync\XqluzSync;

defined( 'ABSPATH' ) || exit;

// include autoloader
require_once __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'XQLUZ_SYNC_PLUGIN_FILE' ) ) {
	define( 'XQLUZ_SYNC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'XQLUZ_SYNC_PLUGIN_DIR' ) ) {
	define( 'XQLUZ_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}


/**
 * Init function
 */
function xqluzsync_init() {
	return XqluzSync::instance();
}

$GLOBALS['XqluzSync'] = xqluzsync_init();

// Invokes all functions attached to the 'XqluzSync_free_loaded' hook
do_action( 'xqluzsync_free_loaded' );
