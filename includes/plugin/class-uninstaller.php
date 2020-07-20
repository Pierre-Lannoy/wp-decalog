<?php
/**
 * Plugin deletion handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\Plugin\Feature\LoggerMaintainer;
use Decalog\System\Option;
use Decalog\System\User;

/**
 * Fired during plugin deletion.
 *
 * This class defines all code necessary to run during the plugin's deletion.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Uninstaller {

	/**
	 * Delete the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		$maintainer = new LoggerMaintainer();
		$maintainer->finalize();
		$file = WP_PLUGIN_DIR . '/decalog/decalog.php';
		if ( file_exists( $file ) ) {
			// phpcs:ignore
			@unlink( $file );
		}
		Option::site_delete_all();
		User::delete_all_meta();
	}

}
