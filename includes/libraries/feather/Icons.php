<?php
/**
 * Wrapper for Feather Icons library.
 *
 * Handles all icons operations.
 *
 * @package Feather
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Feather;

/**
 * Wraps the feather icons functionality.
 *
 * Handles all icons operations.
 *
 * @package Feather
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Icons {

	/**
	 * Already loaded raw icons.
	 *
	 * @since  1.0.0
	 * @var    array    $icons    Already loaded raw icons.
	 */
	private static $icons = [];

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
	 * @param   string $name    Optional. The name of the icon.
	 * @return  string  The raw value of the SVG icon.
	 * @since   1.0.0
	 */
	public static function get_raw( $name = 'x' ) {
		$name     = strtolower( $name );
		$filename = __DIR__ . '/icons/' . $name . '.svg';
		if ( array_key_exists( $name, self::$icons ) ) {
			return self::$icons[ $name ];
		}
		if ( ! file_exists( $filename ) ) {
			return ( 'x' === $name ? '' : self::get_raw() );
		}
		self::$icons[ $name ] = file_get_contents( $filename );
		return ( self::get_raw( $name ) );
	}

	/**
	 * Returns a base64 svg resource for the icon.
	 *
	 * @param string $color Optional. Color of the icon.
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64( $name = 'x', $fill = 'none', $stroke = 'currentColor', $stroke_width = '2', $line_join = 'round', $line_cap = 'round' ) {
		$source = self::get_raw( $name );
		$source = str_replace( 'fill="none"', 'fill="' . $fill . '"', $source );
		$source = str_replace( 'stroke="currentColor"', 'stroke="' . $stroke . '"', $source );
		$source = str_replace( 'stroke-width="2"', 'stroke-width="' . $stroke_width . '"', $source );
		$source = str_replace( 'stroke-linejoin="round"', 'stroke-linejoin="' . $line_join . '"', $source );
		$source = str_replace( 'stroke-linecap="round"', 'stroke-linecap="' . $line_cap . '"', $source );
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}
