<?php
/**
 * Bootstrap utilities for DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Plugin\Feature\DLogger;
use Decalog\System\Option;
use Decalog\Plugin\Feature\Log;

/**
 * Bootstrap utilities for DecaLog.
 *
 * Defines methods and properties for bootstrap management.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class BootstrapManager {

	/**
	 * Get the line to add to wp-settings.php file.
	 *
	 * @return  string  The line to add.
	 * @since    2.4.0
	 */
	public static function install_help() {
		$file = str_replace( ABSPATH, '', DECALOG_PLUGIN_DIR . 'assets/_decalog_bootstrap.php' );
		return "require_once ABSPATH . '" . $file . "';";
	}
}
