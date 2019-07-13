<?php
/**
 * DecaLog logger utilities.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog;

use Decalog\API\DLogger;


/**
 * Utilities DecaLog class.
 *
 * This class defines all code necessary to log events with DecaLog.
 *
 * @package API
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
	 * @return  DLogger The DecaLog logger instance.
	 * @since   1.0.0
	 */
	public static function bootstrap( $class, $name = null , $version=null) {
		return new DLogger( $class, $name, $version );
	}

}
