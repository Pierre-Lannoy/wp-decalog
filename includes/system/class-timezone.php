<?php
/**
 * Timezone handling
 *
 * Handles all timezone operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

use Decalog\System\Environment;

/**
 * Define the timezone functionality.
 *
 * Handles all timezone operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Timezone extends \DateTimeZone {

	/**
	 * Determine time zone from WordPress options and return as object.
	 * Inspired by https://github.com/Rarst/wpdatetime repository.
	 *
	 * @param   string $timezone_string    The timezone identifier.
	 * @param   string $offset             Optional. The offset of the timezone.
	 * @return static
	 */
	private static function get( $timezone_string, $offset = '0' ) {
		if ( ! empty( $timezone_string ) ) {
			return new static( $timezone_string );
		}
		$sign    = $offset < 0 ? '-' : '+';
		$hours   = (int) $offset;
		$minutes = abs( ( $offset - (int) $offset ) * 60 );
		$offset  = sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes );
		return new static( $offset );
	}

	/**
	 * Get the timezone for the current site
	 *
	 * @return static
	 */
	public static function site_get() {
		return self::get( get_option( 'timezone_string' ), get_option( 'gmt_offset' ) );
	}

	/**
	 * Get the timezone for a specific site
	 *
	 * @param   int $id     Optional. The blog id.
	 * @return static
	 */
	public static function site_get_for( $id = 1 ) {
		if ( Environment::is_wordpress_multisite() ) {
			return self::get( get_blog_option( $id, 'timezone_string' ), get_blog_option( $id, 'gmt_offset' ) );
		}
		return self::site_get();
	}

	/**
	 * Get the timezone for the network
	 *
	 * @return static
	 */
	public static function network_get() {
		if ( Environment::is_wordpress_multisite() ) {
			return self::site_get_for( 1 );
		}
		return self::site_get();
	}

}
