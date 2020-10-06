<?php
/**
 * DecaLog logger utilities.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Plugin\Feature\DLogger;
use Monolog\Logger;
use Decalog\Plugin\Feature\EventTypes;


/**
 * Utilities DecaLog class.
 *
 * This class defines all code necessary to log events with DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Log {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a new logger instance.
	 *
	 * @param   string $class The class identifier, see Decalog\API\DLogger::$classes.
	 * @param   string $name Optional. The name of the component.
	 * @param   string $version Optional. The version of the component.
	 * @param   string $test Optional. The handler to bootstrap if specified..
	 * @return  DLogger The DecaLog logger instance.
	 * @since   1.0.0
	 */
	public static function bootstrap( $class, $name = null, $version = null, $test = null ) {
		return new DLogger( $class, $name, $version, $test );
	}

	/**
	 * Get a level name.
	 *
	 * @param   integer $level The level value.
	 * @return  string The level name.
	 * @since   1.0.0
	 */
	public static function level_name( $level ) {
		$result = 'UNKNOWN';
		if ( array_key_exists( $level, EventTypes::$level_names ) ) {
			$result = EventTypes::$level_names[ $level ];
		}
		return $result;
	}

	/**
	 * Get the levels list.
	 *
	 * @param   integer $minimal    Optional. The minimal level to add.
	 * @param   boolean $warn       Optional. Adds a warning to the DEBUG string.
	 * @return  array The level list.
	 * @since   1.0.0
	 */
	public static function get_levels( $minimal = Logger::DEBUG, $warn = false ) {
		$result  = [];
		$warning = ' (' . esc_html__( 'use this only if you know what you do', 'decalog' ) . ')';
		foreach ( EventTypes::$level_names as $key => $name ) {
			if ( $key >= $minimal ) {
				if ( Logger::DEBUG == $key && $warn) {
					$result[] = [ $key, $name . $warning, EventTypes::$level_texts[ strtolower( $name ) ] . $warning ];
				} else {
					$result[] = [ $key, $name, EventTypes::$level_texts[ strtolower( $name ) ] ];
				}
			}
		}
		return array_reverse( $result );
	}

}
