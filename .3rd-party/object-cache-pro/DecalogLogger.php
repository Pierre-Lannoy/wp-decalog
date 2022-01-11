<?php
/**
 * DecaLog integration.
 *
 * Version:           1.0.0
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           MIT
 */

namespace RedisCachePro\Loggers;

/**
 * DecaLog integration class.
 * This class defines all code necessary to log Object Cache Pro's events via DecaLog.
 */
class DecalogLogger implements LoggerInterface {

	/**
	 * The DLogger instance.
	 *
	 * @since  1.0.0
	 * @var    \Decalog\Plugin\Feature\DLogger    $logger    Maintains the internal DLogger instance.
	 */
	private $logger = null;

	/**
	 * Early logged events.
	 *
	 * @since  1.0.0
	 * @var    array    $earlyLogged    The list of early logged events.
	 */
	private static $earlyLogged = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'ready' ], PHP_INT_MIN );
	}

	/**
	 * Effectively log events when DecaLog is ready to do so.
	 *
	 * @since 1.0.0
	 */
	public function ready() {
		if ( ! isset( $this->logger ) && class_exists( '\Decalog\Plugin\Feature\DLogger' ) ) {
			$this->logger = new \Decalog\Plugin\Feature\DLogger( 'plugin', 'Object Cache Pro', defined( 'RedisCachePro\Version' ) ? constant('\RedisCachePro\Version') : '1.x', null, true );
			foreach ( self::$earlyLogged as $event ) {
				$this->log( $event['level'], $event['message'], [ 'code' => $event['code'] ] );
			}
			self::$earlyLogged = [];
		}
		if ( ! isset( $this->logger ) ) {
			\error_log('objectcache.warning: unable to load DecalogLogger.');
		}
	}

	/**
	 * System is unusable.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function emergency( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->emergency( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}  else {
			$this->earlyLog( 'emergency', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Action must be taken immediately.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function alert( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->alert( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}  else {
			$this->earlyLog( 'alert', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Critical conditions.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function critical( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->critical( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}  else {
			$this->earlyLog( 'critical', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function error( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->error( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}  else {
			$this->earlyLog( 'error', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function warning( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->warning( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}  else {
			$this->earlyLog( 'warning', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Normal but significant events.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function notice( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}  else {
			$this->earlyLog( 'notice', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function info( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->debug( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}  else {
			$this->earlyLog( 'debug', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Detailed debug information.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function debug( $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->debug( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		} else {
			$this->earlyLog( 'debug', (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param  string $level   emergency|alert|critical|error|warning|notice|info|debug.
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.0.0
	 */
	public function log( $level, $message, array $context = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->log( $level, (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		} else {
			$this->earlyLog( $level, (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
		}
	}

	/**
	 * Logs early events (before DecaLog is ready to log).
	 *
	 * @param string    $level      The log level.
	 * @param string    $message    The log message.
	 * @param integer   $code       Optional. The log code.
	 * @since 1.0.0
	 */
	private function earlyLog( string $level, string $message, int $code = 0 ) {
		self::$earlyLogged[] = [
			'level'   => $level,
			'message' => $message,
			'code'    => $code,
		];
	}
}