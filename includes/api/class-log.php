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
use Monolog\Logger;


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
	 * List of the available level names.
	 *
	 * @var string[] $level_names Logging levels names
	 */
	private static $level_names = [
		Logger::DEBUG     => 'DEBUG',
		Logger::INFO      => 'INFO',
		Logger::NOTICE    => 'NOTICE',
		Logger::WARNING   => 'WARNING',
		Logger::ERROR     => 'ERROR',
		Logger::CRITICAL  => 'CRITICAL',
		Logger::ALERT     => 'ALERT',
		Logger::EMERGENCY => 'EMERGENCY',
	];

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

	/**
	 * Get a level name.
	 *
	 * @param   integer $level The level value.
	 * @return  string The level name.
	 * @since   1.0.0
	 */
	public static function level_name( $level) {
		$result = 'UNKNOWN';
		if (array_key_exists($level, self::$level_names)) {
			$result = self::$level_names[$level];
		}
		return $result;
	}

	/**
	 * Get the levels list.
	 *
	 * @return  array The level list.
	 * @since   1.0.0
	 */
	public static function get_levels() {
		$result = [];
		foreach (self::$level_names as $key=>$name) {
			$result[] = [$key, $name];
		}
		return array_reverse($result);
	}

}
