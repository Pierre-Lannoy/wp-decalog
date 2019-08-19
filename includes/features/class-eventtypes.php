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
		self::$icons                    = [];
		self::$icons['unknown']         = Feather\Icons::get_base64( 'circle', '#F0F0F0', '#CCCCCC' );
		self::$icons['debug']           = Feather\Icons::get_base64( 'info', '#F0F0F0', '#CCCCCC' );
		self::$icons['info']            = Feather\Icons::get_base64( 'info', '#EEEEFF', '#9999FF' );
		self::$icons['notice']          = Feather\Icons::get_base64( 'info', '#DDDDFF', '#5555FF' );
		self::$icons['warning']         = Feather\Icons::get_base64( 'alert-circle', '#FFFFC4', '#FFAB10' );
		self::$icons['error']           = Feather\Icons::get_base64( 'alert-circle', '#FFD2A8', '#FB7B00' );
		self::$icons['critical']        = Feather\Icons::get_base64( 'alert-circle', '#FFB7B7', '#FF0000' );
		self::$icons['alert']           = Feather\Icons::get_base64( 'x-circle', '#FFB7B7', '#DD0000' );
		self::$icons['emergency']       = Feather\Icons::get_base64( 'x-circle', '#FFB7B7', '#AA0000' );
		self::$level_texts              = [];
		self::$level_texts['unknown']   = esc_html__( 'Unknown', 'decalog' );
		self::$level_texts['debug']     = esc_html__( 'Debug', 'decalog' );
		self::$level_texts['info']      = esc_html__( 'Information', 'decalog' );
		self::$level_texts['notice']    = esc_html__( 'Notice', 'decalog' );
		self::$level_texts['warning']   = esc_html__( 'Warning', 'decalog' );
		self::$level_texts['error']     = esc_html__( 'Error', 'decalog' );
		self::$level_texts['critical']  = esc_html__( 'Critical error', 'decalog' );
		self::$level_texts['alert']     = esc_html__( 'Alert', 'decalog' );
		self::$level_texts['emergency'] = esc_html__( 'Emergency', 'decalog' );
	}

}

EventTypes::init();
