<?php
/**
 * Redis Object Cache listener for DecaLog.
 *
 * Defines class for Redis Object Cache listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */

namespace Decalog\Listener;

use Decalog\System\Plugin;
use Decalog\Plugin\Feature\EventTypes;

/**
 * Redis Object Cache listener for DecaLog.
 *
 * Defines methods and properties for Redis Object Cache listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */
class RedisOCListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    3.4.0
	 */
	protected function init() {
		$this->id      = 'redisoc';
		$this->class   = 'plugin';
		$this->product = 'Redis Object Cache';
		$this->name    = 'Redis Object Cache';
		$this->version = '2.x';
		if ( defined( 'WP_REDIS_VERSION' ) ) {
			$this->version = WP_REDIS_VERSION;
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    3.4.0
	 */
	protected function is_available() {
		return defined( 'WP_REDIS_VERSION' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    3.4.0
	 */
	protected function launch() {
		add_action( 'redis_object_cache_delete', [ $this, 'redis_object_cache_delete' ], 10, 3 );
		add_action( 'redis_object_cache_flush', [ $this, 'redis_object_cache_flush' ], 10, 5 );
		add_action( 'redis_object_cache_get', [ $this, 'redis_object_cache_get' ], 10, 6 );
		add_action( 'redis_object_cache_get_multiple', [ $this, 'redis_object_cache_get_multiple' ], 10, 5 );
		add_action( 'redis_object_cache_set', [ $this, 'redis_object_cache_set' ], 10, 5 );
		add_action( 'redis_object_cache_trace', [ $this, 'redis_object_cache_trace' ], 10, 4 );
		add_action( 'redis_object_cache_error', [ $this, 'redis_object_cache_error' ], 10, 1 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    3.4.0
	 */
	protected function launched() {
		if ( class_exists( '\Rhubarb\RedisCache\Metrics' ) && method_exists( \Rhubarb\RedisCache\Metrics::class, 'get' ) ) {
			$this->monitor->create_prod_gauge( 'cache_hit_ratio', 0, 'Object cache hit ratio per request, 5 min average - [percent]' );
			$this->monitor->create_prod_gauge( 'cache_size', 0, 'Object cache size per request, 5 min average - [byte]' );
			$this->monitor->create_prod_gauge( 'cache_time', 0, 'Object cache time per request, 5 min average - [second]' );
			$this->monitor->create_prod_gauge( 'cache_calls', 0, 'Number of calls per request, 5 min average - [count]' );
		}
	}

	/**
	 * "redis_object_cache_delete" event.
	 *
	 * @since    3.4.0
	 */
	public function redis_object_cache_delete( $key = null, $group = null, $execute_time = null ) {
		if ( ! is_string( $key ) ) {
			$key = 'Unknown key';
		} else {
			$key = 'Key "' . $key . '"';
		}
		if ( ! is_string( $group ) ) {
			$group = 'unknown pool';
		}
		if ( ! is_numeric( $execute_time ) ) {
			$execute_time = 0;
		}
		$this->logger->debug( sprintf( '%s (%s) successfully deleted in %.1F ms.', $key, $group, $execute_time  ) );
	}

	/**
	 * "redis_object_cache_flush" event.
	 *
	 * @since    3.4.0
	 */
	public function redis_object_cache_flush( $results = null, $delay = null, $selective = null, $salt = null, $execute_time = null ) {
		if ( ! is_numeric( $execute_time ) ) {
			$execute_time = 0;
		}
		$this->logger->debug( sprintf( 'Object cache successfully flushed in %.1F ms.', $execute_time * 1000 ) );
	}

	/**
	 * "redis_object_cache_get" event.
	 *
	 * @since    3.4.0
	 */
	public function redis_object_cache_get( $key = null, $value = null, $group = null, $force = null, $found = null, $execute_time = null ) {
		if ( ! is_string( $key ) ) {
			$key = 'Unknown key';
		} else {
			$key = 'Key "' . $key . '"';
		}
		if ( ! is_string( $group ) ) {
			$group = 'unknown pool';
		}
		if ( ! is_numeric( $execute_time ) ) {
			$execute_time = 0;
		}
		if ( $found ) {
			$this->logger->debug( sprintf( '%s (%s) successfully retrieved in %.1F ms.', $key, $group, $execute_time * 1000 ) );
		} else {
			$this->logger->debug( sprintf( '%s (%s) unsuccessfully retrieved in %.1F ms.', $key, $group, $execute_time * 1000 ) );
		}
	}

	/**
	 * "redis_object_cache_get_multiple" event.
	 *
	 * @since    3.4.0
	 */
	public function redis_object_cache_get_multiple( $keys = null, $cache = null, $group = null, $force = null, $execute_time = null ) {
		$key = 'Multiple keys';
		if ( ! is_string( $group ) ) {
			$group = 'unknown pool';
		}
		if ( ! is_numeric( $execute_time ) ) {
			$execute_time = 0;
		}
		$this->logger->debug( sprintf( '%s (%s) successfully retrieved in %.1F ms.', $key, $group, $execute_time * 1000 ) );
	}

	/**
	 * "redis_object_cache_set" event.
	 *
	 * @since    3.4.0
	 */
	public function redis_object_cache_set( $key = null, $value = null, $group = null, $expiration = null, $execute_time = null ) {
		if ( ! is_string( $key ) ) {
			$key = 'Unknown key';
		} else {
			$key = 'Key "' . $key . '"';
		}
		if ( ! is_string( $group ) ) {
			$group = 'unknown pool';
		}
		if ( ! is_numeric( $execute_time ) ) {
			$execute_time = 0;
		}
		$this->logger->debug( sprintf( '%s (%s) successfully stored in %.1F ms.', $key, $group, $execute_time * 1000 ) );
	}

	/**
	 * "redis_object_cache_trace" event.
	 *
	 * @since    3.4.0
	 */
	public function redis_object_cache_trace( $command = null, $group = null, $keyValues = null, $duration = null ) {
		// What to do?
	}

	/**
	 * "redis_object_cache_error" event.
	 *
	 * @since    3.4.0
	 */
	public function redis_object_cache_error( $exception = null ) {
		$this->logger->alert( ( $exception instanceof \Throwable ? $exception->getMessage() : 'Unknown error.' ), ( $exception instanceof \Throwable ? $exception->getCode() : 0 ));
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.4.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		if ( ! \Decalog\Plugin\Feature\DMonitor::$active ) {
			return;
		}
		$span = $this->tracer->start_span( 'Metrics collation', DECALOG_SPAN_SHUTDOWN );
		if ( class_exists( '\Rhubarb\RedisCache\Metrics' ) && method_exists( \Rhubarb\RedisCache\Metrics::class, 'get' ) ) {
			$result  = \Rhubarb\RedisCache\Metrics::get( 300 );
			$metrics = [
				'hit'    => [],
				'misses' => [],
				'bytes'  => [],
				'time'   => [],
				'calls'  => [],
			];
			$hit  = 0;
			$miss = 0;
			if ( is_array( $result ) ) {
				foreach ( $result as $record ) {
					if ( $record instanceof \Rhubarb\RedisCache\Metrics ) {
						$metrics['hit'][]    = $record->hits;
						$metrics['misses'][] = $record->misses;
						$metrics['bytes'][]  = $record->bytes;
						$metrics['time'][]   = $record->time;
						$metrics['calls'][]  = $record->calls;
					}
				}
			}
			if ( 0 < count( $metrics['hit'] ) ) {
				$hit = array_sum( $metrics['hit'] ) / count( $metrics['hit'] );
			}
			if ( 0 < count( $metrics['misses'] ) ) {
				$miss = array_sum( $metrics['misses'] ) / count( $metrics['misses'] );
			}
			if ( 0 < ( $hit + $miss ) ) {
				$this->monitor->set_prod_gauge( 'cache_hit_ratio', $hit / ( $hit + $miss ) );
			}
			if ( 0 < count( $metrics['bytes'] ) ) {
				$this->monitor->set_prod_gauge( 'cache_size', array_sum( $metrics['bytes'] ) / count( $metrics['bytes'] ) );
			}
			if ( 0 < count( $metrics['time'] ) ) {
				$this->monitor->set_prod_gauge( 'cache_time', array_sum( $metrics['time'] ) / count( $metrics['time'] ) );
			}
			if ( 0 < count( $metrics['time'] ) ) {
				$this->monitor->set_prod_gauge( 'cache_calls', array_sum( $metrics['calls'] ) / count( $metrics['calls'] ) );
			}
		}
		$this->tracer->end_span( $span );
	}

}
