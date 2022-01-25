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
	 * @var    \Decalog\Plugin\Feature\DLogger    $events_logger    Maintains the internal DLogger instance.
	 */
	private $events_logger = null;

	/**
	 * The DMonitor instance.
	 *
	 * @since  1.0.0
	 * @var    \Decalog\Plugin\Feature\DMonitor    $metrics_logger    Maintains the internal DMonitor instance.
	 */
	private $metrics_logger = null;

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
		if ( ! defined( 'DECALOG_MAX_SHUTDOWN_PRIORITY' ) ) {
			define( 'DECALOG_MAX_SHUTDOWN_PRIORITY', PHP_INT_MAX - 1000 );
		}
		$version = defined( 'RedisCachePro\Version' ) ? constant('\RedisCachePro\Version') : '1.x';
		if ( ! isset( $this->events_logger ) && class_exists( '\Decalog\Plugin\Feature\DLogger' ) ) {
			$this->events_logger = new \Decalog\Plugin\Feature\DLogger( 'plugin', 'Object Cache Pro', $version, null, true );
			foreach ( self::$earlyLogged as $event ) {
				$this->log( $event['level'], '[loader] ' . $event['message'], [ 'code' => $event['code'] ] );
			}
			self::$earlyLogged = [];
			$this->debug( 'Events logger initialized.' );
		}
		if ( ! isset( $this->events_logger ) ) {
			\error_log('objectcache.warning: unable to load DecalogLogger for events.');
		}
		if ( defined( 'WP_REDIS_ANALYTICS' ) && WP_REDIS_ANALYTICS ) {
			if ( ! isset( $this->metrics_logger ) && class_exists( '\Decalog\Plugin\Feature\DMonitor' ) ) {
				$this->metrics_logger = new \Decalog\Plugin\Feature\DMonitor( 'plugin', 'Object Cache Pro', $version );
				$this->metrics_logger->create_prod_gauge( 'cache_hit_ratio', 0, 'Object cache hit ratio per request, 5 min average - [percent]' );
				$this->metrics_logger->create_prod_gauge( 'cache_size', 0, 'Object cache size per request, 5 min average - [byte]' );
				$this->metrics_logger->create_prod_gauge( 'cache_time', 0, 'Object cache time per request, 5 min average - [second]' );
				$this->metrics_logger->create_prod_gauge( 'cache_calls', 0, 'Number of calls per request, 5 min average - [count]' );
			}
			if ( ! isset( $this->metrics_logger ) ) {
				if ( ! isset( $this->events_logger ) ) {
					\error_log('objectcache.warning: unable to load DecalogLogger for metrics.');
				} else {
					$this->warning( 'Unable to load metrics logger.' );
				}
			} else {
				add_action( 'shutdown', [ $this, 'monitoring_close' ], DECALOG_MAX_SHUTDOWN_PRIORITY );
				$this->debug( 'Metrics logger initialized.' );
			}
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->emergency( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->alert( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->critical( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->error( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->warning( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->notice( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->debug( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->debug( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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
		if ( isset( $this->events_logger ) ) {
			$this->events_logger->log( $level, (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
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

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    1.0.0
	 */
	public function monitoring_close() {
		if ( $this->metrics_logger instanceof \Decalog\Plugin\Feature\DMonitor ) {
			global $wp_object_cache;
			if ( $wp_object_cache instanceof \RedisCachePro\ObjectCaches\PhpRedisObjectCache && defined( 'WP_REDIS_ANALYTICS' ) && WP_REDIS_ANALYTICS) {
				$measurements  = $wp_object_cache->measurements( microtime(true) - 300 );
				$this->metrics_logger->set_prod_gauge( 'cache_hit_ratio', $measurements->mean( 'wp->hitRatio' ) / 100 );
				$this->metrics_logger->set_prod_gauge( 'cache_size', $measurements->mean( 'wp->bytes' ) );
				$this->metrics_logger->set_prod_gauge( 'cache_time', $measurements->mean( 'wp->cacheMs' ) / 1000 );
				$this->metrics_logger->set_prod_gauge( 'cache_calls', $measurements->mean( 'wp->storeReads' ) + $measurements->mean( 'wp->storeWrites' ) );
			}
		}
	}
}