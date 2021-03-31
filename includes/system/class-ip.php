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
	 * Try to extract real ip if any exists.
	 *
	 * @param   array   $iplist             A list of IPs.
	 * @param   boolean $include_private    optional. Include private IPs too.
	 * @return  string  The real ip if found, '' otherwise.
	 * @since 1.0.0
	 */
	public static function maybe_extract_ip( $iplist, $include_private = false ) {
		if ( $include_private ) {
			foreach ( $iplist as $ip ) {
				if ( false !== filter_var( trim( $ip ), FILTER_VALIDATE_IP ) ) {
					return self::expand( $ip );
				}
			}
		}
		foreach ( $iplist as $ip ) {
			if ( false !== filter_var( trim( $ip ), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return self::expand( $ip );
			}
		}
		return '';
	}

	/**
	 * Get the current client IP.
	 *
	 * @return  string The current client IP.
	 * @since 1.0.0
	 */
	public static function get_current() {
		for ( $i = 0; $i < 2; $i++ ) {
			foreach (
				[
					'HTTP_FORWARDED',
					'HTTP_FORWARDED_FOR',
					'HTTP_X_FORWARDED',
					'HTTP_CLIENT_IP',
					'HTTP_X_FORWARDED_FOR',
					'HTTP_X_CLUSTER_CLIENT_IP',
					'HTTP_CF_CONNECTING_IP',
					'HTTP_X_REAL_IP',
					'REMOTE_ADDR',
				] as $field
			) {
				if ( array_key_exists( $field, $_SERVER ) ) {
					$ip = self::maybe_extract_ip( explode( ',', filter_input( INPUT_SERVER, $field ) ), 1 === $i );
					if ( '' !== $ip ) {
						return $ip;
					}
				}
			}
		}
		return '127.0.0.1';
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
