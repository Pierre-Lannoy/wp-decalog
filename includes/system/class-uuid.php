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
	 * Generates a (pseudo) unique ID.
	 * This function does not generate cryptographically secure values, and should not be used for cryptographic purposes.
	 *
	 * @param   integer $length     The length of the ID.
	 * @return  string  The unique ID.
	 * @since  1.0.0
	 */
	public static function generate_unique_id( $length = 10 ) {
		$result = '';
		do {
			$s       = self::generate_v4();
			$s       = str_replace( '-', date( 'his' ), $s );
			$result .= $s;
			$l       = strlen( $result );
		} while ( $l < $length );
		return substr( str_shuffle( $result ), 0, $length );
	}

}
