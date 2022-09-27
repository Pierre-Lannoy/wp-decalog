<?php
/**
 * Global functions.
 *
 * @package Functions
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

/**
 * Multibyte String Pad
 *
 * Functionally, the equivalent of the standard str_pad function, but is capable of successfully padding multibyte strings.
 *
 * @param string $input The string to be padded.
 * @param int $length The length of the resultant padded string.
 * @param string $padding The string to use as padding. Defaults to space.
 * @param int $padType The type of padding. Defaults to STR_PAD_RIGHT.
 * @param string $encoding The encoding to use, defaults to UTF-8.
 *
 * @return string A padded multibyte string.
 * @since   2.0.0
 */
function decalog_mb_str_pad( $input, $length, $padding = ' ', $padType = STR_PAD_RIGHT, $encoding = 'UTF-8' ) {
	$result = $input;
	if ( ( $padding_required = $length - mb_strlen( $input, $encoding ) ) > 0 ) {
		switch ( $padType ) {
			case STR_PAD_LEFT:
				$result =
					mb_substr( str_repeat( $padding, $padding_required ), 0, $padding_required, $encoding ) .
					$input;
				break;
			case STR_PAD_RIGHT:
				$result =
					$input .
					mb_substr( str_repeat( $padding, $padding_required ), 0, $padding_required, $encoding );
				break;
			case STR_PAD_BOTH:
				$left_padding_length  = floor( $padding_required / 2 );
				$right_padding_length = $padding_required - $left_padding_length;
				$result               =
					mb_substr( str_repeat( $padding, $left_padding_length ), 0, $left_padding_length, $encoding ) .
					$input .
					mb_substr( str_repeat( $padding, $right_padding_length ), 0, $right_padding_length, $encoding );
				break;
		}
	}
	return $result;
}

/**
 * Multibyte full trim
 *
 * Functionally, the equivalent of the standard str_pad function, but is capable of successfully padding multibyte strings.
 *
 * @param string $input         The string to be fully trimed.
 * @param string $replacement   Optional. The string replacement.
 *
 * @return string A fully trimed multibyte string.
 * @since   3.6.0
 */
function decalog_mb_full_trim( $input, $replacement = '' ) {
	return preg_replace(
		"/(\t|\n|\v|\f|\r| |\xC2\x85|\xc2\xa0|\xe1\xa0\x8e|\xe2\x80[\x80-\x8D]|\xe2\x80\xa8|\xe2\x80\xa9|\xe2\x80\xaF|\xe2\x81\x9f|\xe2\x81\xa0|\xe3\x80\x80|\xef\xbb\xbf)+/",
		$replacement,
		$input
	);
}

/**
 * Performs an HTTP request using the PUT method and returns its response.
 * Mimics wp_remote_get or wp_remote_post, but for PUT method.
 *
 * @since 3.0.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url  URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return array|WP_Error The response or WP_Error on failure.
 */
function decalog_remote_put( $url, $args = [] ) {
	$http = _wp_http_get_object();
	if ( isset( $http ) ) {
		$defaults    = [ 'method' => 'PUT' ];
		$parsed_args = wp_parse_args( $args, $defaults );
		return $http->request( $url, $parsed_args );
	}
	return new WP_Error( 500 );
}


/**
 * Provide PHP 7.3 compatibility for array_key_last() function.
 */
if ( ! function_exists( 'array_key_last' ) ) {
	// phpcs:ignore
	function array_key_last( array $array ) {
		if ( ! empty( $array ) ) {
			return key( array_slice( $array, -1, 1, true ) );
		}
	}
}

/**
 * Provide PHP 7.3 compatibility for array_key_first() function.
 */
if ( ! function_exists( 'array_key_first' ) ) {
	// phpcs:ignore
	function array_key_first( array $arr ) {
		foreach ( $arr as $key => $unused ) {
			return $key;
		}
	}
}

/**
 * Provide a replacement for filter_var() used with FILTER_SANITIZE_STRING flag (was legit with PHP prior to 8.1).
 */
function decalog_filter_string( string $string ) {
	return preg_replace( '/\x00|<[^>]*>?/', '', $string );
}
