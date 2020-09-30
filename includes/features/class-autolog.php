<?php
/**
 * Auto-logging utilities for DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Plugin\Feature\DLogger;
use Decalog\System\Option;
use Decalog\Plugin\Feature\Log;

/**
 * Auto-logging utilities for DecaLog.
 *
 * Defines methods and properties for auto-logging.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Autolog {

	/**
	 * Verify if auto-logging is enabled.
	 *
	 * @since    2.0.0
	 */
	public static function is_enabled() {
		if ( ! Option::network_get( 'livelog' ) ) {
			return false;
		}
		$loggers = Option::network_get( 'loggers' );
		if ( ! array_key_exists( DECALOG_SHM_ID, $loggers ) ) {
			return false;
		}
		if ( ! $loggers[DECALOG_SHM_ID]['running'] ) {
			return false;
		}
		return true;
	}

	/**
	 * Activate auto-logging.
	 *
	 * @since    2.0.0
	 */
	public static function activate() {
		$old = Option::network_get( 'livelog' );
		Option::network_set( 'livelog', true );
		$loggers = Option::network_get( 'loggers' );
		foreach ( $loggers as $uuid => $logger ) {
			if ( 'SharedMemoryHandler' === $logger['handler'] ) {
				$loggers[$uuid]['running'] = true;
			}
		}
		Option::network_set( 'loggers', $loggers );
		if ( ! $old ) {
			$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$logger->notice( 'Auto-logging is now activated.' );
		}
	}

	/**
	 * Deactivate auto-logging.
	 *
	 * @since    2.0.0
	 */
	public static function deactivate() {
		$old     = Option::network_get( 'livelog' );
		$loggers = Option::network_get( 'loggers' );
		foreach ( $loggers as $uuid => $logger ) {
			if ( 'SharedMemoryHandler' === $logger['handler'] ) {
				$loggers[$uuid]['running'] = false;
			}
		}
		Option::network_set( 'loggers', $loggers );
		Option::network_set( 'livelog', false );
		if ( $old ) {
			$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$logger->notice( 'Auto-logging is now deactivated.' );
		}
	}
}
