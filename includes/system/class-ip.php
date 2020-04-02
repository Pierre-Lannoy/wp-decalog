<?php
/**
 * IP handling
 *
 * Handles all IP operations and transformation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the IP functionality.
 *
 * Handles all IP operations and transformation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class IP {

	/**
	 * The list of char to clean.
	 *
	 * @since  1.0.0
	 * @var    array    $clean    Maintains the char list.
	 */
	private static $clean = [ '"', '%' ];

	/**
	 * Expands an IPv4 or IPv6 address.
	 *
	 * @param string    $ip     The IP to expand.
	 * @return  string  The expanded IP.
	 * @since   1.0.0
	 */
	public static function expand( $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return self::expand_v6( $ip );
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return self::expand_v4( $ip );
		}
		return '';
	}

	/**
	 * Expands an IPv4 address.
	 *
	 * @param string    $ip     The IP to expand.
	 * @return  string  The expanded IP.
	 * @since   1.0.0
	 */
	public static function expand_v4( $ip ) {
		return long2ip( ip2long( str_replace( self::$clean, '', $ip ) ) );
	}

	/**
	 * Normalizes an IPv4 address.
	 *
	 * @param string    $ip     The IP to normalize.
	 * @return  string  The normalized IP.
	 * @since   1.0.0
	 */
	public static function normalize_v4( $ip ) {
		return long2ip( (int) str_replace( self::$clean, '', $ip ) );
	}

	/**
	 * Expands an IPv6 address.
	 *
	 * @param string    $ip     The IP to expand.
	 * @return  string  The expanded IP.
	 * @since   1.0.0
	 */
	public static function expand_v6( $ip ) {
		return implode( ':', str_split( bin2hex( inet_pton( str_replace( self::$clean, '', $ip ) ) ), 4 ) );
	}

	/**
	 * Normalizes an IPv6 address.
	 *
	 * @param string    $ip     The IP to normalize.
	 * @return  string  The normalized IP.
	 * @since   1.0.0
	 */
	public static function normalize_v6( $ip ) {
		return inet_ntop( inet_pton( str_replace( self::$clean, '', $ip ) ) );
	}

}