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
use Feather;

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
		'debug'     => Logger::DEBUG,
		'info'      => Logger::INFO,
		'notice'    => Logger::NOTICE,
		'warning'   => Logger::WARNING,
		'error'     => Logger::ERROR,
		'critical'  => Logger::CRITICAL,
		'alert'     => Logger::ALERT,
		'emergency' => Logger::EMERGENCY,
	];

	/**
	 * List of the available WSAL levels.
	 *
	 * @since    1.0.0
	 * @var string[] $levels Logging levels.
	 */
	public static $wsal_levels = [
		7 => Logger::DEBUG,
		6 => Logger::INFO,
		5 => Logger::NOTICE,
		4 => Logger::WARNING,
		3 => Logger::ERROR,
		2 => Logger::CRITICAL,
		1 => Logger::ALERT,
		0 => Logger::EMERGENCY,
	];

	/**
	 * List of the available icons.
	 *
	 * @since    1.0.0
	 * @var string[] $icons Logging levels.
	 */
	public static $icons = [];

	/**
	 * List of the available level texts.
	 *
	 * @var string[] $level_names Logging levels texts.
	 */
	public static $level_texts = [];

	/**
	 * List of the available level names.
	 *
	 * @var string[] $level_names Logging levels names.
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
	 * @var string[] $level_values Logging levels.
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

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$icons              = [];
		self::$icons['unknown']   = Feather\Icons::get_base64( 'circle', '#F0F0F0', '#CCCCCC' );
		self::$icons['debug']     = Feather\Icons::get_base64( 'info', '#F0F0F0', '#CCCCCC' );
		self::$icons['info']      = Feather\Icons::get_base64( 'info', '#EEEEFF', '#9999FF' );
		self::$icons['notice']    = Feather\Icons::get_base64( 'info', '#DDDDFF', '#5555FF' );
		self::$icons['warning']   = Feather\Icons::get_base64( 'alert-circle', '#FFFFC4', '#FFAB10' );
		self::$icons['error']     = Feather\Icons::get_base64( 'alert-circle', '#FFD2A8', '#FB7B00' );
		self::$icons['critical']  = Feather\Icons::get_base64( 'alert-circle', '#FFB7B7', '#FF0000' );
		self::$icons['alert']     = Feather\Icons::get_base64( 'x-circle', '#FFB7B7', '#DD0000' );
		self::$icons['emergency'] = Feather\Icons::get_base64( 'x-circle', '#FFB7B7', '#AA0000' );
		self::$level_texts        = [];
		/* translators: definition of an event typed 'UNKNOWN' */
		self::$level_texts['unknown'] = esc_html__( 'The event is not typed, this can\'t be a good news.', 'decalog' );
		/* translators: definition of an event typed 'DEBUG', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['debug'] = esc_html__( 'An information for developers and testers. Only used for events related to application/system debugging.', 'decalog' );
		/* translators: definition of an event typed 'INFO', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['info'] = esc_html__( 'A standard information, just for you to know… and forget!', 'decalog' );
		/* translators: definition of an event typed 'NOTICE', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['notice'] = esc_html__( 'A normal but significant condition. Now you know!', 'decalog' );
		/* translators: definition of an event typed 'WARNING', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['warning'] = esc_html__( 'A significant condition indicating a situation that may lead to an error if recurring or if no action is taken. Does not usually affect the operations.', 'decalog' );
		/* translators: definition of an event typed 'ERROR', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['error'] = esc_html__( 'A minor operating error that may affects the operations. It requires investigation and preventive treatment.', 'decalog' );
		/* translators: definition of an event typed 'CRITICAL', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['critical'] = esc_html__( 'An operating error that undoubtedly affects the operations. It requires investigation and corrective treatment.', 'decalog' );
		/* translators: definition of an event typed 'ALERT', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['alert'] = esc_html__( 'A major operating error that undoubtedly affects the operations. It requires immediate investigation and corrective treatment.', 'decalog' );
		/* translators: definition of an event typed 'EMERGENCY', see https://github.com/Pierre-Lannoy/wp-decalog/blob/master/DEVELOPER.md for details*/
		self::$level_texts['emergency'] = esc_html__( 'A panic condition. WordPress is unusable.', 'decalog' );
	}

}

EventTypes::init();
