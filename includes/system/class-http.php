<?php
/**
 * HTTP handling
 *
 * Handles all HTTP operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the HTTP functionality.
 *
 * Handles all HTTP operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Http {

	/**
	 * The list of available verbs.
	 *
	 * @since  1.0.0
	 * @var    array    $verbs    Maintains the verbs list.
	 */
	public static $verbs = [ 'get', 'head', 'post', 'put', 'delete', 'connect', 'options', 'trace', 'patch', 'unknown' ];

	/**
	 * The list of HTTP codes meaning success.
	 *
	 * @since  1.0.0
	 * @var    array    $http_success_codes    Maintains the success codes list.
	 */
	public static $http_success_codes = [ 200, 201, 202, 203, 204, 205, 206, 207, 300, 301, 302, 303, 304, 305, 306, 307, 308 ];

	/**
	 * The list of HTTP status.
	 *
	 * @since  1.0.0
	 * @var    array    $http_status_codes    Maintains the status list.
	 */
	public static $http_status_codes = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '(Unused)',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		419 => 'Authentication Timeout',
		420 => 'Enhance Your Calm',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Method Failure',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		444 => 'No Response',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		451 => 'Unavailable For Legal Reasons',
		494 => 'Request Header Too Large',
		495 => 'Cert Error',
		496 => 'No Cert',
		497 => 'HTTP to HTTPS',
		499 => 'Client Closed Request',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
		598 => 'Network read timeout error',
		599 => 'Network connect timeout error',
	];

	/**
	 * Set the plugin user agent.
	 *
	 * @param string $user_agent Plugin user agent string.
	 * @param string $url        The request URL.
	 * @return  string  The user agent to use.
	 * @since  1.0.0
	 */
	public static function user_agent( $user_agent, $url ) {
		return DECALOG_PRODUCT_NAME . ' (' . Environment::wordpress_version_id() . '; ' . Environment::plugin_version_id() . '; +' . DECALOG_PRODUCT_URL . ')';
	}

	/**
	 * Get the top domain of a full fqdn host.
	 *
	 * @param string $host The host.
	 * @return  string  The top domain.
	 * @since  1.0.0
	 */
	public static function top_domain( $host ) {
		if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 ) ) {
			return $host;
		}
		$parts       = explode( '.', $host );
		$middle_part = array_slice( $parts, -2, 1 );
		$mid         = $middle_part[0];
		$slice       = ( ( strlen( $mid ) <= 3 ) && ( count( $parts ) > 2 ) ) ? 3 : 2;
		$result      = implode( '.', array_slice( $parts, ( 0 - $slice ), $slice ) );
		return $result;
	}

}
