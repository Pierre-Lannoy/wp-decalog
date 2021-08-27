<?php
/**
 * Global functions for Perfops One features.
 *
 * @package @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

global $wp_version;

use Decalog\System\Plugin;

if ( version_compare( $wp_version, '5.5', '<' ) && ! function_exists( 'wp_is_auto_update_enabled_for_type' ) ) {
	/**
	 * Provide WP 5.5 compatibility for wp_is_auto_update_enabled_for_type() function.
	 */
	function wp_is_auto_update_enabled_for_type( $type ) {
		return false;
	}
}

if ( ! function_exists( 'poo_switch_autoupdate_callback' ) ) {
	/**
	 * Ajax callback for autoupdate switching.
	 *
	 * @since    2.0.0
	 */
	function poo_switch_autoupdate_callback() {
		check_ajax_referer( 'poo-auto-update', 'nonce' );
		$plugin = new Plugin( filter_input( INPUT_POST, 'plugin' ) );
		if ( ! current_user_can( 'update_plugins' ) || ! wp_is_auto_update_enabled_for_type( 'plugin' ) || ( is_multisite() && ! is_network_admin() ) ) {
			wp_die( 403 );
		}
		if ( $plugin->switch_auto_update() ) {
			wp_die( 200 );
		} else {
			wp_die( 500 );
		}
	}
	add_action( 'wp_ajax_poo_switch_autoupdate', 'poo_switch_autoupdate_callback' );
}
