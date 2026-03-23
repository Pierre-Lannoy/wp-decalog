<?php
/**
 * UUIDs handling
 *
 * Handles all UUID operations and generation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the UUID functionality.
 *
 * Handles all UUID operations and generation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class UUID {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Generates a v4 UUID.
	 *
	 * @since  1.0.0
	 * @return string      A v4 UUID.
	 */
	public static function generate_v4() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// phpcs:disable
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
			// phpcs:enabled
		);
	}

	/**
	 * Check if a string is a valid v4 UUID
	 *
	 * @param mixed $uuid The string to check
	 * @return  boolean True if the string is a valid v4 UUID, false otherwise.
	 * @since  2.0.0
	 */
	public static function is_valid_v4( $uuid ) {
		return is_string( $uuid ) && preg_match( '/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid );
	}

	/**
	 * Sanitize a v4 UUID
	 *
	 * @param mixed $uuid The string to sanitize
	 * @return  string The sanitized v4 UUID.
	 * @since  2.0.0
	 */
	public static function sanitize_v4( $uuid ) {
		return self::is_valid_v4( $uuid ) ? (string) $uuid : '00000000-0000-4000-0000-000000000000';
	}

	/**
	 * Generates a (pseudo) unique ID.
	 * This function does not generate cryptographically secure values, and should not be used for cryptographic purposes.
	 *
	 * @param   integer $length     The length of the ID.
	 * @return  string  The unique ID.
	 * @since  1.0.0
	 */
	public static function generate_unique_id( $length = 10 ) {
		$result = '';
		$date   = new \DateTime();
		do {
			$s       = self::generate_v4();
			$s       = str_replace( '-', (string) ( $date->format( 'u' ) ), $s );
			$result .= $s;
			$l       = strlen( $result );
		} while ( $l < $length );
		return substr( str_shuffle( $result ), 0, $length );
	}

}
