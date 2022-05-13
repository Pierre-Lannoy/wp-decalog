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
use Decalog\System\Option;

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
	 * The apcu pool's prefix, specific to the WordPress instance.
	 *
	 * @since  1.0.0
	 * @var    string    $apcu_pool_prefix    The pool's name.
	 */
	private static $apcu_pool_prefix = '';

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
	public static $apcu_available = false;

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
		return ( wp_using_ext_object_cache() || self::$apcu_available );
	}

	/**
	 * Get cache analytics.
	 *
	 * @return array The cache analytics.
	 * @since  1.0.0
	 */
	public static function get_analytics() {
		$result    = [];
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
	 * Initializes properties.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$ttls = [
			'ephemeral'         => 1 * MINUTE_IN_SECONDS,
			'infinite'          => 10 * YEAR_IN_SECONDS,
			'diagnosis'         => HOUR_IN_SECONDS,
			'plugin-statistics' => DAY_IN_SECONDS,
			'metrics'           => HOUR_IN_SECONDS,
			'm-query'           => 10 * MINUTE_IN_SECONDS,
			'longquery'         => 6 * HOUR_IN_SECONDS,
		];
		if ( wp_using_ext_object_cache() ) {
			wp_cache_add_global_groups( self::$pool_name );
		}
		if ( ! defined( 'APCU_CACHE_PREFIX' ) ) {
			define( 'APCU_CACHE_PREFIX', '_' . md5( ABSPATH ) . '_' );
		}
		self::$apcu_pool_prefix = APCU_CACHE_PREFIX;
		self::$apcu_available = function_exists( 'apcu_delete' ) && function_exists( 'apcu_fetch' ) && function_exists( 'apcu_store' );
	}

	/**
	 * Get an ID for caching.
	 *
	 * @since 1.0.0
	 */
	public static function id( $args, $path = 'data/' ) {
		if ( is_array( $args ) ) {
			$args = serialize( $args );
		}
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
		return substr( trim( $name ), 0, 172 - strlen( self::$apcu_pool_prefix . self::$pool_name ) );
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
		$item_name = self::normalized_item_name( $item_name );
		$found     = false;
		if ( self::$apcu_available && Option::network_get( 'use_apcu', true ) ) {
			$result = apcu_fetch(  self::$pool_name . self::$apcu_pool_prefix . $item_name, $found );
		} elseif ( wp_using_ext_object_cache() ) {
			$result = wp_cache_get( $item_name, self::$pool_name, false, $found );
		} else {
			$result = get_transient( self::$pool_name . '_' . $item_name );
			$found  = false !== $result;
		}
		if ( $found ) {
			return $result;
		} else {
			return null;
		}
	}

	/**
	 * Get the value of a fully named apcu cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  3.4.0
	 */
	private static function get_apcu_for_full_name( $item_name ) {
		$chrono    = microtime( true );
		$item_name = self::normalized_item_name( $item_name );
		$found     = false;
		if ( self::$apcu_available ) {
			$result = apcu_fetch(  self::$pool_name . self::$apcu_pool_prefix . $item_name, $found );
		}
		if ( $found ) {
			return $result;
		} else {
			return null;
		}
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
	 * Get the value of a global apcu cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  3.4.0
	 */
	public static function get_global_apcu( $item_name ) {
		return self::get_apcu_for_full_name( self::full_item_name( $item_name ) );
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
	 * @param  int|string $ttl   Optional. The previously defined ttl @see self::init() if it's a string.
	 *                           The ttl value in seconds if it's and integer.
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
			if ( self::$apcu_available && Option::network_get( 'use_apcu', true ) ) {
				$result = apcu_store( self::$pool_name . self::$apcu_pool_prefix . $item_name, $value, $expiration );
			}  elseif ( wp_using_ext_object_cache() ) {
				$result = wp_cache_set( $item_name, $value, self::$pool_name, $expiration );
			} else {
				$result = set_transient( self::$pool_name . '_' . $item_name, $value, $expiration );
			}
		} else {
			$result = false;
		}
		return $result;
	}

	/**
	 * Set the value of a fully named apcu cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  int|string $ttl   Optional. The previously defined ttl @see self::init() if it's a string.
	 *                           The ttl value in seconds if it's and integer.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	private static function set_apcu_for_full_name( $item_name, $value, $ttl = 'default' ) {
		$item_name  = self::normalized_item_name( $item_name );
		$expiration = self::$default_ttl;
		if ( is_string( $ttl ) && array_key_exists( $ttl, self::$ttls ) ) {
			$expiration = self::$ttls[ $ttl ];
		}
		if ( is_integer( $ttl ) && 0 < (int) $ttl ) {
			$expiration = (int) $ttl;
		}
		if ( $expiration > 0 ) {
			if ( self::$apcu_available ) {
				$result = apcu_store( self::$pool_name . self::$apcu_pool_prefix . $item_name, $value, $expiration );
			}
		} else {
			$result = false;
		}
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
	 * @param  int|string $ttl   Optional. The previously defined ttl @see self::init() if it's a string.
	 *                           The ttl value in seconds if it's and integer.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set_global( $item_name, $value, $ttl = 'default' ) {
		return self::set_for_full_name( self::full_item_name( $item_name ), $value, $ttl );
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
	 * @param  int|string $ttl   Optional. The previously defined ttl @see self::init() if it's a string.
	 *                           The ttl value in seconds if it's and integer.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set_global_apcu( $item_name, $value, $ttl = 'default' ) {
		return self::set_apcu_for_full_name( self::full_item_name( $item_name ), $value, $ttl );
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
	 * @param  int|string $ttl       Optional. The previously defined ttl @see self::init() if it's a string.
	 *                               The ttl value in seconds if it's and integer.
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
		if ( self::$apcu_available && Option::network_get( 'use_apcu', true ) ) {
			if ( strlen( $item_name ) - 1 === strpos( $item_name, '_*' ) ) {
				return false;
			} else {
				return apcu_delete( self::$pool_name . self::$apcu_pool_prefix . $item_name );
			}
		}
		if ( wp_using_ext_object_cache() ) {
			if ( strlen( $item_name ) - 1 === strpos( $item_name, '_*' ) ) {
				return false;
			} else {
				return wp_cache_delete( $item_name, self::$pool_name );
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
	 * Delete the value of a fully named apcu cache item.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  3.4.0
	 */
	private static function delete_apcu_for_ful_name( $item_name ) {
		$item_name = self::normalized_item_name( $item_name );
		$result    = 0;
		if ( self::$apcu_available ) {
			if ( strlen( $item_name ) - 1 === strpos( $item_name, '_*' ) ) {
				return false;
			} else {
				return apcu_delete( self::$pool_name . self::$apcu_pool_prefix . $item_name );
			}
		}
		return $result;
	}

	/**
	 * Delete the full pool.
	 *
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	public static function delete_pool() {
		$result = 0;
		if ( self::$apcu_available && Option::network_get( 'use_apcu', true ) ) {
			if ( function_exists( 'apcu_cache_info' ) && function_exists( 'apcu_delete' ) ) {
				try {
					$infos = apcu_cache_info( false );
					if ( array_key_exists( 'cache_list', $infos ) && is_array( $infos['cache_list'] ) ) {
						foreach ( $infos['cache_list'] as $script ) {
							if ( 0 === strpos( $script['info'], self::$pool_name . self::$apcu_pool_prefix ) ) {
								apcu_delete( $script['info'] );
								$result++;
							}
						}
					}
				} catch ( \Throwable $e ) {
					$result = 0;
				}
			}
		} elseif ( wp_using_ext_object_cache() ) {
			$result = 0;
		} else {
			$result = self::delete_global( '/*' );
		}
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
	 * Delete the value of a global apcu cache item.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  3.4.0
	 */
	public static function delete_global_apcu( $item_name ) {
		return self::delete_apcu_for_ful_name( self::full_item_name( $item_name ) );
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
	 * @param  string  $ttl_range   The time range in seconds. May be something like '0', '200' or '15-600:15'.
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
		}
		if ( false !== strpos( $ttls[1], ':' ) ) {
			$steps   = explode( ':', $ttls[1] );
			$ttls[1] = $steps[0];
		}
		return (int) min( (int) $ttls[0], (int) $ttls[1] );
	}

	/**
	 * Get the maximum value of a ttl time range.
	 *
	 * @param  string  $ttl_range   The time range in seconds. May be something like '0', '200' or '15-600:15'.
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
		}
		if ( false !== strpos( $ttls[1], ':' ) ) {
			$steps   = explode( ':', $ttls[1] );
			$ttls[1] = $steps[0];
		}
		return (int) max( (int) $ttls[0], (int) $ttls[1] );
	}

	/**
	 * Get the step of a ttl time range.
	 *
	 * @param  string  $ttl_range   The time range in seconds. May be something like '0', '200' or '15-600:15'.
	 * @return integer The ttl in seconds.
	 * @since  1.0.0
	 */
	public static function get_step( $ttl_range ) {
		if ( ! is_string( $ttl_range) ) {
			return 0;
		}
		$ttls = explode( '-', $ttl_range );
		if ( 1 === count( $ttls ) ) {
			return 0;
		}
		if ( false !== strpos( $ttls[1], ':' ) ) {
			$steps = explode( ':', $ttls[1] );
			if ( 2 === count( $ttls ) ) {
				return $steps[1];
			}
		}
		return 1;
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
		$min    = self::get_min( $ttl_range );
		$max    = self::get_max( $ttl_range );
		$step   = self::get_step( $ttl_range );
		$factor = $step * (int) round( ( $max - $min ) / ( 2 * $step ) );
		return $min + (int) round( $factor );
	}

	/**
	 * Get the options infos for Site Health "info" tab.
	 *
	 * @since 1.0.0
	 */
	public static function debug_info() {
		if ( self::$apcu_available ) {
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

\Decalog\System\Cache::init();