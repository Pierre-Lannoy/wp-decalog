<?php
/**
 * Plugin Name:       DecaLog Early Loader
 * Plugin URI:        https://perfops.one/decalog
 * Description:       MU plugin to early initialize DecaLog engine and listeners.
 * Version:           4.x
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( (bool) get_site_option( 'decalog_earlyloading', true ) ) {
	$file = WP_PLUGIN_DIR . '/decalog/decalog.php';
	if ( file_exists( $file ) ) {
		define( 'DECALOG_EARLY_INIT', true );
		require $file;
	}
}

add_action(
	'muplugins_loaded',
	function() {
		if ( ! defined( 'POMU_END_TIMESTAMP' ) ) {
			define( 'POMU_END_TIMESTAMP', microtime( true ) );
		}},
	PHP_INT_MIN,
	0
);

add_action(
	'muplugins_loaded',
	function() {
		if ( ! defined( 'POPL_START_TIMESTAMP' ) ) {
			define( 'POPL_START_TIMESTAMP', microtime( true ) );
		}},
	PHP_INT_MAX,
	0
);



