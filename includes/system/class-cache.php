<?php
/**
 * Plugin cache handling.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 * @noinspection PhpCSValidationInspection
 */

namespace Decalog\System;

use Decalog\Logger;
use Decalog\System\Conversion;

/**
 * The class responsible to handle cache management.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Cache {

	/**
	 * The pool's name, specific to the calling plugin.
	 *
	 * @since  1.0.0
	 * @var    string    $pool_name    The pool's name.
	 */
	private static $pool_name = DECALOG_SLUG;

	/**
	 * Available TTLs.
	 *
	 * @since  1.0.0
	 * @var    array    $ttls    The TTLs array.
	 */
	private static $ttls = [];

	/**
	 * Default TTL.
	 *
	 * @since  1.0.0
	 * @var    integer    $default_ttl    The default TTL in seconds.
	 */
	private static $default_ttl = 3600;

	/**
	 * Is APCu available.
	 *
	 * @since  1.0.0
	 * @var    boolean    $apcu_available    Is APCu available.
	 */
	private static $apcu_available = false;

	/**
	 * Hits values.
	 *
	 * @since  1.0.0
	 * @var    array    $hit    Hits values.
	 */
	private static $hit = [];

	/**
	 * Miss values.
	 *
	 * @since  1.0.0
	 * @var    array    $miss    Miss values.
	 */
	private static $miss = [];

	/**
	 * Current (temporary) values.
	 *
	 * @since  1.0.0
	 * @var    array    $current    Current (temporary) values.
	 */
	private static $current = [];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Verify if cache is in memory.
	 *
	 * @since 1.0.0
	 */
	public static function is_memory() {
		return wp_using_ext_object_cache() || self::$apcu_available;
	}

	/**
	 * Initializes properties.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$ttls = [
			'ephemeral'         => 0,
			'infinite'          => 10 * YEAR_IN_SECONDS,
			'diagnosis'         => HOUR_IN_SECONDS,
			'plugin-statistics' => DAY_IN_SECONDS,
		];
		if ( wp_using_ext_object_cache() ) {
			wp_cache_add_global_groups( self::$pool_name );
		}
		self::$apcu_available = function_exists( 'apcu_delete' ) && function_exists( 'apcu_fetch' ) && function_exists( 'apcu_store' );
		add_action( 'shutdown', [ 'Decalog\System\Cache', 'log_debug' ], 10, 0 );
		add_filter( 'perfopsone_icache_introspection', [ 'Decalog\System\Cache', 'introspection' ] );
	}

	/**
	 * Get the introspection endpoint.
	 *
	 * @since 1.0.0
	 */
	public static function introspection( $endpoints ) {
		$endpoints[ DECALOG_SLUG ] = [ 'name' => DECALOG_PRODUCT_NAME, 'version' => DECALOG_VERSION, 'endpoint' => [ 'Decalog\System\Cache', 'get_analytics' ] ];
		return $endpoints;
	}

	/**
	 * Get an ID for caching.
	 *
	 * @since 1.0.0
	 */
	public static function id( $args, $path = 'data/' ) {
		if ( '/' === $path[0] ) {
			$path = substr( $path, 1 );
		}
		if ( '/' !== $path[ strlen( $path ) - 1 ] ) {
			$path = $path . '/';
		}
		return $path . md5( (string) $args );
	}

	/**
	 * Full item name.
	 *
	 * @param  string  $item_name Item name. Expected to not be SQL-escaped.
	 * @param  boolean $blog_aware   Optional. Has the name must take care of blog.
	 * @param  boolean $locale_aware Optional. Has the name must take care of locale.
	 * @param  boolean $user_aware   Optional. Has the name must take care of user.
	 * @return string The full item name.
	 * @since  1.0.0
	 */
	private static function full_item_name( $item_name, $blog_aware = false, $locale_aware = false, $user_aware = false ) {
		$name = '';
		if ( $blog_aware ) {
			$name .= (string) get_current_blog_id() . '/';
		}
		if ( $locale_aware ) {
			$name .= (string) L10n::get_display_locale() . '/';
		}
		if ( $user_aware ) {
			$name .= (string) User::get_current_user_id() . '/';
		}
		$name .= $item_name;
		return substr( trim( $name ), 0, 172 - strlen( self::$pool_name ) );
	}

	/**
	 * Normalized item name.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return string The normalized item name.
	 * @since  1.0.0
	 */
	private static function normalized_item_name( $item_name ) {
		if ( '/' === $item_name[0] ) {
			$item_name = substr( $item_name, 1 );
		}
		while ( 0 !== substr_count( $item_name, '//' ) ) {
			$item_name = str_replace( '//', '/', $item_name );
		}
		$item_name = str_replace( '/', '_', $item_name );
		return strtolower( $item_name );
	}

	/**
	 * Get the value of a fully named cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	private static function get_for_full_name( $item_name ) {
		$chrono    = microtime( true );
		$item_name = self::normalized_item_name( $item_name );
		$found     = false;
		if ( wp_using_ext_object_cache() ) {
			$result = wp_cache_get( $item_name, self::$pool_name, false, $found );
		} elseif ( self::$apcu_available ) {
			$result = apcu_fetch( self::$pool_name . '_' . $item_name, $found );
		} else {
			$result = get_transient( self::$pool_name . '_' . $item_name );
			$found  = false !== $result;
		}
		if ( $found ) {
			self::$hit[] = [
				'time' => microtime( true ) - $chrono,
				'size' => strlen( serialize( $result ) ),
			];
			return $result;
		} else {
			self::$current[ $item_name ] = $chrono;
			return null;
		}
	}

	/**
	 * Get the value of a shared cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	public static function get_shared( $item_name ) {
		$save            = self::$pool_name;
		self::$pool_name = 'perfopsone';
		$result = self::get_for_full_name( self::full_item_name( $item_name ) );
		self::$pool_name = $save;
		return $result;
	}

	/**
	 * Get the value of a global cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	public static function get_global( $item_name ) {
		return self::get_for_full_name( self::full_item_name( $item_name ) );
	}

	/**
	 * Get the value of a standard cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string  $item_name Item name. Expected to not be SQL-escaped.
	 * @param  boolean $blog_aware   Optional. Has the name must take care of blog.
	 * @param  boolean $locale_aware Optional. Has the name must take care of locale.
	 * @param  boolean $user_aware   Optional. Has the name must take care of user.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	public static function get( $item_name, $blog_aware = false, $locale_aware = false, $user_aware = false ) {
		return self::get_for_full_name( self::full_item_name( $item_name, $blog_aware, $locale_aware, $user_aware ) );
	}

	/**
	 * Set the value of a fully named cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  int|string $ttl       Optional. The previously defined ttl @see self::init() if it's a string.
	 *                               The ttl value in seconds if it's and integer.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	private static function set_for_full_name( $item_name, $value, $ttl = 'default' ) {
		$item_name  = self::normalized_item_name( $item_name );
		$expiration = self::$default_ttl;
		if ( is_string( $ttl ) && array_key_exists( $ttl, self::$ttls ) ) {
			$expiration = self::$ttls[ $ttl ];
		}
		if ( is_integer( $ttl ) && 0 < (int) $ttl ) {
			$expiration = (int) $ttl;
		}
		if ( $expiration > 0 ) {
			if ( wp_using_ext_object_cache() ) {
				$result = wp_cache_set( $item_name, $value, self::$pool_name, (int) $expiration );
			} elseif ( self::$apcu_available ) {
				$result = apcu_store( self::$pool_name . '_' . $item_name, $value, $expiration );
			} else {
				$result = set_transient( self::$pool_name . '_' . $item_name, $value, $expiration );
			}
			if ( array_key_exists( $item_name, self::$current ) ) {
				self::$miss[] = [
					'time' => microtime( true ) - self::$current[ $item_name ],
					'size' => strlen( serialize( $result ) ),
				];
			}
		} else {
			$result = false;
		}
		return $result;
	}

	/**
	 * Set the value of a shared cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  int|string $ttl       Optional. The previously defined ttl @see self::init() if it's a string.
	 *                               The ttl value in seconds if it's and integer.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set_shared( $item_name, $value, $ttl = 'default' ) {
		$save            = self::$pool_name;
		self::$pool_name = 'perfopsone';
		$result = self::set_for_full_name( self::full_item_name( $item_name ), $value, $ttl );
		self::$pool_name = $save;
		return $result;
	}

	/**
	 * Set the value of a global cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  int|string $ttl       Optional. The previously defined ttl @see self::init() if it's a string.
	 *                               The ttl value in seconds if it's and integer.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set_global( $item_name, $value, $ttl = 'default' ) {
		return self::set_for_full_name( self::full_item_name( $item_name ), $value, $ttl );
	}

	/**
	 * Set the value of a standard cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string  $item_name    Item name. Expected to not be SQL-escaped.
	 * @param  mixed   $value        Item value. Must be serializable if non-scalar.
	 *                               Expected to not be SQL-escaped.
	 * @param  string  $ttl          Optional. The previously defined ttl @see self::init().
	 * @param  boolean $blog_aware   Optional. Has the name must take care of blog.
	 * @param  boolean $locale_aware Optional. Has the name must take care of locale.
	 * @param  boolean $user_aware   Optional. Has the name must take care of user.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set( $item_name, $value, $ttl = 'default', $blog_aware = false, $locale_aware = false, $user_aware = false ) {
		return self::set_for_full_name( self::full_item_name( $item_name, $blog_aware, $locale_aware, $user_aware ), $value, $ttl );
	}

	/**
	 * Delete the value of a fully named cache item.
	 *
	 * This function accepts generic car "*" for transients.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	private static function delete_for_ful_name( $item_name ) {
		$item_name = self::normalized_item_name( $item_name );
		$result    = 0;
		if ( wp_using_ext_object_cache() ) {
			if ( strlen( $item_name ) - 1 === strpos( $item_name, '_*' ) ) {
				return false;
			} else {
				return wp_cache_delete( $item_name, self::$pool_name );
			}
		}
		if ( self::$apcu_available ) {
			if ( strlen( $item_name ) - 1 === strpos( $item_name, '_*' ) ) {
				return false;
			} else {
				return apcu_delete( self::$pool_name . '_' . $item_name );
			}
		}
		global $wpdb;
		$item_name = self::$pool_name . '_' . $item_name;
		if ( strlen( $item_name ) - 1 === strpos( $item_name, '_*' ) ) {
			// phpcs:ignore
			$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name = '_transient_timeout_" . str_replace( '_*', '', $item_name ) . "' OR option_name LIKE '_transient_timeout_" . str_replace( '_*', '_%', $item_name ) . "';" );
		} else {
			// phpcs:ignore
			$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name = '_transient_timeout_" . $item_name . "';" );
		}
		foreach ( $delete as $transient ) {
			$key = str_replace( '_transient_timeout_', '', $transient );
			if ( delete_transient( $key ) ) {
				++$result;
			}
		}
		return $result;
	}

	/**
	 * Delete the value of a shared cache item.
	 *
	 * This function accepts generic car "*" for transients.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	public static function delete_shared( $item_name ) {
		$save            = self::$pool_name;
		self::$pool_name = 'perfopsone';
		$result = self::delete_for_ful_name( self::full_item_name( $item_name ) );
		self::$pool_name = $save;
		return $result;
	}

	/**
	 * Delete the value of a global cache item.
	 *
	 * This function accepts generic car "*" for transients.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	public static function delete_global( $item_name ) {
		return self::delete_for_ful_name( self::full_item_name( $item_name ) );
	}

	/**
	 * Delete the value of a standard cache item.
	 *
	 * This function accepts generic car "*" for transients.
	 *
	 * @param  string  $item_name Item name. Expected to not be SQL-escaped.
	 * @param  boolean $blog_aware   Optional. Has the name must take care of blog.
	 * @param  boolean $locale_aware Optional. Has the name must take care of locale.
	 * @param  boolean $user_aware   Optional. Has the name must take care of user.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	public static function delete( $item_name, $blog_aware = false, $locale_aware = false, $user_aware = false ) {
		return self::delete_for_ful_name( self::full_item_name( $item_name, $blog_aware, $locale_aware, $user_aware ) );
	}

	/**
	 * Get the minimum value of a ttl time range.
	 *
	 * This function accepts generic car "*" for transients.
	 *
	 * @param  string  $ttl_range   The time range in seconds. May be something like '5-600' or '200'.
	 * @return integer The ttl in seconds.
	 * @since  1.0.0
	 */
	public static function get_min( $ttl_range ) {
		if ( ! is_string( $ttl_range) ) {
			return 0;
		}
		$ttls = explode( '-', $ttl_range );
		if ( 1 === count( $ttls ) ) {
			return (int) $ttls[0];
		} else {
			return (int) min( (int) $ttls[0], (int) $ttls[1] );
		}
	}

	/**
	 * Get the maximum value of a ttl time range.
	 *
	 * This function accepts generic car "*" for transients.
	 *
	 * @param  string  $ttl_range   The time range in seconds. May be something like '5-600' or '200'.
	 * @return integer The ttl in seconds.
	 * @since  1.0.0
	 */
	public static function get_max( $ttl_range ) {
		if ( ! is_string( $ttl_range) ) {
			return 0;
		}
		$ttls = explode( '-', $ttl_range );
		if ( 1 === count( $ttls ) ) {
			return (int) $ttls[0];
		} else {
			return (int) max( (int) $ttls[0], (int) $ttls[1] );
		}
	}

	/**
	 * Get the medium value of a ttl time range.
	 *
	 * This function accepts generic car "*" for transients.
	 *
	 * @param  string  $ttl_range   The time range in seconds. May be something like '5-600' or '200'.
	 * @return integer The ttl in seconds.
	 * @since  1.0.0
	 */
	public static function get_med( $ttl_range ) {
		$min = self::get_min( $ttl_range );
		$max = self::get_max( $ttl_range );
		$med = $max - ( ( $max - $min ) / 2 );
		return (int) round( $med );
	}

	/**
	 * Get cache analytics.
	 *
	 * @return array The cache analytics.
	 * @since  1.0.0
	 */
	public static function get_analytics() {
		$result    = [];
		$hit_time  = 0;
		$hit_count = count( self::$hit );
		$hit_size  = 0;
		if ( 0 < $hit_count ) {
			foreach ( self::$hit as $h ) {
				$hit_time = $hit_time + $h['time'];
				$hit_size = $hit_size + $h['size'];
			}
			$hit_time = $hit_time / $hit_count;
			$hit_size = $hit_size / $hit_count;
		}
		$result['hit']['count'] = $hit_count;
		$result['hit']['time'] = $hit_time;
		$result['hit']['size'] = $hit_size;
		$miss_time  = 0;
		$miss_count = count( self::$miss );
		$miss_size  = 0;
		if ( 0 < $miss_count ) {
			foreach ( self::$miss as $h ) {
				$miss_time = $miss_time + $h['time'];
				$miss_size = $miss_size + $h['size'];
			}
			$miss_time = $miss_time / $miss_count;
			$miss_size = $miss_size / $miss_count;
		}
		$result['miss']['count'] = $miss_count;
		$result['miss']['time'] = $miss_time;
		$result['miss']['size'] = $miss_size;
		if ( wp_using_ext_object_cache() ) {
			$result['type'] = 'object_cache';
		} elseif ( self::$apcu_available ) {
			$result['type'] = 'apcu';
		} else {
			$result['type'] = 'db_transient';
		}
		return $result;
	}

	/**
	 * Logs the cache analytics.
	 *
	 * @since  1.0.0
	 */
	public static function log_debug() {
		$analytics = self::get_analytics();
		$log       = '[' . $analytics['type'] . ']';
		$log      .= '   Hit count: ' . $analytics['hit']['count'] . '   Hit time: ' . round($analytics['hit']['time'] * 1000, 3) . 'ms   Hit size: ' . Conversion::data_shorten( (int) $analytics['hit']['size'] );
		$log      .= '   Miss count: ' . $analytics['miss']['count'] . '   Miss time: ' . round($analytics['miss']['time'] * 1000, 3) . 'ms   Miss size: ' . Conversion::data_shorten( (int) $analytics['miss']['size'] );
		if ( 0 !== (int) $analytics['hit']['count'] || 0 !== (int) $analytics['miss']['count'] ) {
			$logger = new Logger( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
			$logger->debug( $log );
		}
	}

	/**
	 * Get the options infos for Site Health "info" tab.
	 *
	 * @since 1.0.0
	 */
	public static function debug_info() {
		if ( wp_using_ext_object_cache() ) {
			$result['product'] = [
				'label' => 'Product',
				'value' => 'External manager',
			];
		} elseif ( self::$apcu_available ) {
			$result['product'] = [
				'label' => 'Product',
				'value' => 'APCu',
			];
			foreach ( [ 'enabled', 'shm_segments', 'shm_size', 'entries_hint', 'ttl', 'gc_ttl', 'mmap_file_mask', 'slam_defense', 'enable_cli', 'use_request_time', 'serializer', 'coredump_unmap', 'preload_path' ] as $key ) {
				$result[ 'directive_' . $key ] = [
					'label' => '[Directive] ' . $key,
					'value' => ini_get( 'apc.' . $key ),
				];
			}
			if ( function_exists( 'apcu_sma_info' ) && function_exists( 'apcu_cache_info' ) ) {
				$raw = apcu_sma_info();
				foreach ( $raw as $key => $status ) {
					if ( ! is_array( $status ) ) {
						$result[ 'status_' . $key ] = [
							'label' => '[Status] ' . $key,
							'value' => $status,
						];
					}
				}
				$raw = apcu_cache_info();
				foreach ( $raw as $key => $status ) {
					if ( ! is_array( $status ) ) {
						$result[ 'status_' . $key ] = [
							'label' => '[Status] ' . $key,
							'value' => $status,
						];
					}
				}
			}
		} else {
			$result['product'] = [
				'label' => 'Product',
				'value' => 'Database transients',
			];
		}
		return $result;
	}

}
