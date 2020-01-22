<?php
/**
 * Wrapper for Flag-Icon-CSS library.
 *
 * Handles all flags operations.
 *
 * @package Feather
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Flagiconcss;

use Decalog\System\Cache;

/**
 * Wraps the Flag-Icon-CSS functionality.
 *
 * Handles all flags operations.
 *
 * @package FlagiconCSS
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Flags {

	/**
	 * Already loaded raw flags.
	 *
	 * @since  1.0.0
	 * @var    array $flags Already loaded raw flags.
	 */
	private static $flags = [];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a raw (SVG) icon.
	 *
	 * @param string $name Optional. The name of the flag.
	 * @param boolean $squared Optional. The flag must be squared.
	 *
	 * @return  string  The raw value of the SVG flag.
	 * @since   1.0.0
	 */
	public static function get_raw( $name = 'fr', $squared = false ) {
		$fname    = ( $squared ? '1x1/' : '4x3/' ) . strtolower( $name );
		$filename = __DIR__ . '/flags/' . $fname . '.svg';
		// phpcs:ignore
		$id = Cache::id( serialize( [ 'name' => $name, 'squared' => $squared ] ), 'flags/' );
		if ( Cache::is_memory() ) {
			$flag = Cache::get_shared( $id );
			if ( isset( $flag ) ) {
				return $flag;
			}
		} else {
			if ( array_key_exists( $fname, self::$flags ) ) {
				return self::$flags[ $fname ];
			}
		}
		if ( ! file_exists( $filename ) ) {
			return ( 'fr' === $name ? '' : self::get_raw() );
		}
		if ( Cache::is_memory() ) {
			// phpcs:ignore
			Cache::set_shared( $id, file_get_contents( $filename ), 'infinite' );
		} else {
			// phpcs:ignore
			self::$flags[ $fname ] = file_get_contents( $filename );
		}

		return ( self::get_raw( $name ) );
	}

	/**
	 * Returns a base64 svg resource for the icon.
	 *
	 * @param string $name Optional. The name of the flag.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64( $name = 'fr' ) {
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( self::get_raw( $name ) );
	}
}