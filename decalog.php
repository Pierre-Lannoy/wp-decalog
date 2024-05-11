<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       DecaLog
 * Plugin URI:        https://perfops.one/decalog
 * Description:       Capture and log events, metrics and traces on your site. Make WordPress observable – finally!
 * Version:           3.10.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       decalog
 * Network:           true
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';
require_once __DIR__ . '/includes/libraries/guzzlehttp/functions_include.php';

/**
 * Copy the file responsible to early initialization in mu-plugins and drop-ins dir.
 *
 * @since 2.4.0
 */
function decalog_check_earlyloading() {
	if ( (bool) get_site_option( 'decalog_earlyloading', true ) ) {
		if ( defined( 'DECALOG_EARLY_INIT' ) && defined( 'DECALOG_BOOTSTRAPPED' ) && DECALOG_BOOTSTRAPPED && DECALOG_EARLY_INIT ) {
			return;
		}
		if ( ! defined( 'DECALOG_EARLY_INIT' ) ) {
			$target = WPMU_PLUGIN_DIR . '/_decalog_loader.php';
			$source = __DIR__ . '/assets/_decalog_loader.php';
			if ( ! file_exists( $target ) ) {
				if ( ! file_exists( WPMU_PLUGIN_DIR ) ) {
					// phpcs:ignore
					@mkdir( WPMU_PLUGIN_DIR );
				}
				if ( ! file_exists( WPMU_PLUGIN_DIR ) ) {
					define( 'DECALOG_EARLY_INIT_WPMUDIR_ERROR', true );
				}
				if ( file_exists( $source ) ) {
					// phpcs:ignore
					@copy( $source, $target );
					// phpcs:ignore
					@chmod( $target, 0644 );
				}
				if ( ! file_exists( $target ) ) {
					define( 'DECALOG_EARLY_INIT_COPY_ERROR', true );
				}
			} else {
				define( 'DECALOG_EARLY_INIT_ERROR', true );
			}
		}
		if ( ! defined( 'DECALOG_BOOTSTRAPPED' ) ) {
			$target = WP_CONTENT_DIR . '/fatal-error-handler.php';
			$source = __DIR__ . '/assets/fatal-error-handler.php';
			if ( ! file_exists( $target ) ) {
				if ( file_exists( $source ) ) {
					// phpcs:ignore
					@copy( $source, $target );
					// phpcs:ignore
					@chmod( $target, 0644 );
				}
				if ( ! file_exists( $target ) ) {
					define( 'DECALOG_BOOTSTRAP_COPY_ERROR', true );
				}
			} else {
				define( 'DECALOG_BOOTSTRAP_ALREADY_EXISTS_ERROR', true );
			}
		}
	} else {
		$file = WPMU_PLUGIN_DIR . '/_decalog_loader.php';
		if ( file_exists( $file ) ) {
			// phpcs:ignore
			@unlink( $file );
		}
		if ( defined( 'DECALOG_BOOTSTRAPPED' ) ) {
			$file = WP_CONTENT_DIR . '/fatal-error-handler.php';
			if ( file_exists( $file ) ) {
				// phpcs:ignore
				@unlink( $file );
			}
		}
	}
}

/**
 * Removes the file responsible to early initialization in mu-plugins and drop-ins dir.
 *
 * @since 3.0.0
 */
function decalog_reset_earlyloading() {
	$target = WPMU_PLUGIN_DIR . '/_decalog_loader.php';
	if ( file_exists( $target ) ) {
		// phpcs:ignore
		@unlink( $target );
	}
	if ( defined( 'DECALOG_BOOTSTRAPPED' ) ) {
		$target = WP_CONTENT_DIR . '/fatal-error-handler.php';
		if ( file_exists( $target ) ) {
			// phpcs:ignore
			@unlink( $target );
		}
	}
}

/**
 * Disable early loading if we update from version lower than 4.0.0.
 *
 * @since 4.0.0
 */
function decalog_update_earlyloading_if_needed() {
	if ( ! str_starts_with( (string) get_site_option( 'decalog_version', '0.0.0' ), '4.' ) ) {
		update_site_option( 'decalog_earlyloading', false );
	}
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function decalog_activate() {
	decalog_reset_earlyloading();
	Decalog\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function decalog_deactivate() {
	decalog_reset_earlyloading();
	Decalog\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function decalog_uninstall() {
	decalog_reset_earlyloading();
	Decalog\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function decalog_run() {
	//decalog_update_earlyloading_if_needed();
	require_once __DIR__ . '/includes/features/class-wpcli.php';
	decalog_check_earlyloading();
	$plugin = new Decalog\Plugin\Core();
	$plugin->run();
}
if ( ! defined( 'DECALOG_MAX_SHUTDOWN_PRIORITY' ) ) {
	define( 'DECALOG_MAX_SHUTDOWN_PRIORITY', PHP_INT_MAX - 1000 );
}
register_activation_hook( __FILE__, 'decalog_activate' );
register_deactivation_hook( __FILE__, 'decalog_deactivate' );
register_uninstall_hook( __FILE__, 'decalog_uninstall' );
decalog_run();
