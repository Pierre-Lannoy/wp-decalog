<?php
/**
 * Plugin deactivation handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\System\Option;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Deactivator {


	/**
	 * Deactivate the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		Option::network_set( 'earlyloading', false );
		$file = WPMU_PLUGIN_DIR . '/_decalog_loader.php';
		if ( file_exists( $file ) ) {
			// phpcs:ignore
			@unlink( $file );
		}
		$file = WP_CONTENT_DIR . '/fatal-error-handler.php';
		if ( file_exists( $file ) && defined( 'DECALOG_BOOTSTRAPPED' ) && DECALOG_BOOTSTRAPPED ) {
			// phpcs:ignore
			@unlink( $file );
		}
	}

}
