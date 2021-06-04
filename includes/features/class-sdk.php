<?php
/**
 * SDK utilities for DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Plugin\Feature\DLogger;
use Decalog\System\Option;
use Decalog\Plugin\Feature\Log;

/**
 * SDK utilities for DecaLog.
 *
 * Defines methods and properties for SDK introspection.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class SDK {

	/**
	 * Verify if SDK is present.
	 *
	 * @retun   boolean True if SDK is present, false otherwise.
	 * @since    3.0.0
	 */
	public static function is_present() {
		return class_exists( '\DecaLog\Engine' );
	}

	/**
	 * Get the self-registered components list.
	 *
	 * @return  array
	 * @since    3.0.0  The self-registered components list.
	 */
	public static function get_selfreg() {
		if ( self::is_present() ) {
			$result = \DecaLog\Engine::getLoggers();
			foreach ( $result as $slug => $loger ) {
				$result[ $slug ]['slug'] = $slug;
			}
			return $result;
		}
		return [];
	}
}
