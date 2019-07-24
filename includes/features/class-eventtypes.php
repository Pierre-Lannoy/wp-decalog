<?php
/**
 * Event types handling
 *
 * Handles all available event types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Monolog\Logger;

/**
 * Define the event types functionality.
 *
 * Handles all available event types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class EventTypes {

	/**
	 * List of the available levels.
	 *
	 * @since    1.0.0
	 * @var string[] $levels Logging levels.
	 */
	public static $levels = [
		'debug' => Logger::DEBUG,
		'info' => Logger::INFO,
		'notice' => Logger::NOTICE,
		'warning' => Logger::WARNING,
		'error' => Logger::ERROR,
		'critical' => Logger::CRITICAL,
		'alert' => Logger::ALERT,
		'emergency' => Logger::EMERGENCY,
	];

	/**
	 * List of the available level names.
	 *
	 * @var string[] $level_names Logging levels names
	 */
	public static $level_names = [
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
	 * List of the available levels.
	 *
	 * @since    1.0.0
	 * @var string[] $levels Logging levels.
	 */
	public static $level_values = [
		Logger::DEBUG,
		Logger::INFO,
		Logger::NOTICE,
		Logger::WARNING,
		Logger::ERROR,
		Logger::CRITICAL,
		Logger::ALERT,
		Logger::EMERGENCY,
	];




}
