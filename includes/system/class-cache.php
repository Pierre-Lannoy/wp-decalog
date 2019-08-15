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
	 * Differentiates cache items by blogs.
	 *
	 * @since  1.0.0
	 * @var    boolean    $blog_aware    Is the item id must contain the blog id?
	 */
	private static $blog_aware = true;

	/**
	 * Differentiates cache items by current locale.
	 *
	 * @since  1.0.0
	 * @var    boolean    $blog_aware    Is the item id must contain the locale id?
	 */
	private static $locale_aware = true;

	/**
	 * Differentiates cache items by current user.
	 *
	 * @since  1.0.0
	 * @var    boolean    $blog_aware    Is the item id must contain the user id?
	 */
	private static $user_aware = true;

	/**
	 * Available TTLs.
	 *
	 * @since  1.0.0
	 * @var    array    $ttls    The TTLs array.
	 */
	private static $ttls;

	/**
	 * Default TTL.
	 *
	 * @since  1.0.0
	 * @var    integer    $default_ttl    The default TTL in seconds.
	 */
	private static $default_ttl = 3600;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Initializes properties.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$ttls = [
			'ephemeral' => -1,
			'infinite'  => 0,
			'diagnosis' => 3600,
		];
	}

	/**
	 * Full item name.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return string The full item name.
	 * @since  1.0.0
	 */
	private static function full_item_name( $item_name ) {
		$name = self::$pool_name . '_';
		if ( self::$blog_aware ) {
			$name .= (string) get_current_blog_id() . '_';
		}
		if ( self::$locale_aware ) {
			$name .= (string) L10n::get_display_locale() . '_';
		}
		if ( self::$user_aware ) {
			$name .= (string) User::get_current_user_id() . '_';
		}
		$name .= $item_name;
		return substr( trim( $name ), 0, 172 );
	}

	/**
	 * Get the value of a cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	public static function get( $item_name ) {
		return get_transient( self::full_item_name( $item_name ) );
	}

	/**
	 * Set the value of a cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  string $ttl       Optional. The previously defined ttl @see self::init().
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set( $item_name, $value, $ttl = 'default' ) {
		$expiration = self::$default_ttl;
		if ( array_key_exists( $ttl, self::$ttls ) ) {
			$expiration = self::$ttls[ $ttl ];
		}
		if ( $expiration >= 0 ) {
			return set_transient( self::full_item_name( $item_name ), $value, $expiration );
		} else {
			return false;
		}
	}

	/**
	 * Delete the value of a cache item.
	 *
	 * This function accepts generic car "*".
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	public static function delete( $item_name ) {
		global $wpdb;
		$result = 0;
		if ( strlen( $item_name ) - 1 === strpos( $item_name, '/*' ) && '/' === $item_name[0] ) {
			// phpcs:ignore
			$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name = '_transient_timeout_" . self::full_item_name( str_replace( '/*', '', $item_name ) ) . "' OR option_name LIKE '_transient_timeout_" . self::full_item_name( str_replace( '/*', '/%', $item_name ) ) . "';" );
		} else {
			// phpcs:ignore
			$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name = '_transient_timeout_" . self::full_item_name( $item_name ) . "';" );
		}
		foreach ( $delete as $transient ) {
			$key = str_replace( '_transient_timeout_', '', $transient );
			if ( delete_transient( $key ) ) {
				++$result;
			}
		}
		return $result;
	}

}
