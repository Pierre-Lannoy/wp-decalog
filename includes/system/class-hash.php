<?php
/**
 * Hash handling
 *
 * Handles all hash operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the hash functionality.
 *
 * Handles all hash operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Hash {

	/**
	 * Algo availability.
	 *
	 * @since  1.0.0
	 * @var    array    $x_available    Is MD5 available?
	 */
	private static $x_available = [];

	/**
	 * SHA1 availability.
	 *
	 * @since  1.0.0
	 * @var    boolean    $sha1_available    Is SHA1 available?
	 */
	private static $sha1_available = false;

	/**
	 * SHA-256 availability.
	 *
	 * @since  1.0.0
	 * @var    boolean    $sha256_available    Is SHA-256 available?
	 */
	private static $sha256_available = false;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Static initialization.
	 *
	 * @since  1.0.0
	 */
	public static function init() {
		self::$x_available = hash_algos();
		self::$sha1_available = in_array('sha1', self::$x_available);
		self::$sha256_available = in_array('sha256', self::$x_available);
	}

	/**
	 * Simple hashing function.
	 *
	 * @param   string  $secret     String to hash.
	 * @param   boolean $markup     Optional. With {}.
	 * @return  string  The hashed string.
	 * @since  1.0.0
	 */
	public static function simple_hash($secret, $markup=true) {
		$result = '';
		if (self::$sha256_available) {
			$result = hash('sha256', (string)$secret);
		} elseif (self::$sha1_available) {
			$result =  hash('sha1', (string)$secret);
		} else {
			$result =  hash('md5', (string)$secret);
		}
		if ($markup) {
			$result = '{' . $result . '}';
		}
		return $result;
	}

}

Hash::init();