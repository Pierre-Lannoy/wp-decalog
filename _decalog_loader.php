<?php
/**
 *
 * Plugin Name:       DecaLog Early Loader
 * Plugin URI:        https://github.com/Pierre-Lannoy/wp-decalog
 * Description:       MU plugin to early initialize DecaLog engine and listeners.
 * Version:           1.0.0
 * Author:            Pierre Lannoy
 * Author URI:        https://pierre.lannoy.fr
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

