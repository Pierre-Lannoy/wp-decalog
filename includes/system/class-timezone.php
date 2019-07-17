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
	 * @return static
	 */
	public static function get_wp() {
		$timezone_string = get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			return new static( $timezone_string );
		}
		$offset  = get_option( 'gmt_offset' );
		$sign    = $offset < 0 ? '-' : '+';
		$hours   = (int) $offset;
		$minutes = abs( ( $offset - (int) $offset ) * 60 );
		$offset  = sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes );
		return new static( $offset );
	}

}
