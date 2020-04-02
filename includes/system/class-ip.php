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
	 * Verify if the current client IP is private.
	 *
	 * @return  boolean True if the IP is private, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_current_private() {
		return ! filter_var( self::get_current(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Verify if the current client IP is public.
	 *
	 * @return  boolean True if the IP is public, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_current_public() {
		return ! self::is_current_private();
	}

	/**
	 * Get the current client IP.
	 *
	 * @return  string The current client IP.
	 * @since 1.0.0
	 */
	public static function get_current() {
		$ip = '';
		if ( array_key_exists( 'REMOTE_ADDR', $_SERVER ) ) {
			$iplist = explode( ',', filter_input( INPUT_SERVER, 'REMOTE_ADDR' ) );
			$ip     = trim( end( $iplist ) );
		}
		if ( array_key_exists( 'HTTP_X_REAL_IP', $_SERVER ) ) {
			$iplist = explode( ',', filter_input( INPUT_SERVER, 'HTTP_X_REAL_IP' ) );
			$ip     = trim( end( $iplist ) );
		}
		if ( '' === $ip && array_key_exists( 'HTTP_X_FORWARDED_FOR', $_SERVER ) ) {
			$iplist = array_reverse( explode( ',', filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR' ) ) );
			$ip     = trim( end( $iplist ) );
		}
		if ( '' === $ip ) {
			$ip = '127.0.0.1';
		}
		return self::expand( $ip );
	}

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