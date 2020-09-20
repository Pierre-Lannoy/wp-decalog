<?php
/**
 * Emoji flag handling
 *
 * Handles all emoji flag operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

use Decalog\System\L10n;

/**
 * Define the emoji flag functionality.
 *
 * Handles all emoji flag operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class EmojiFlag {

	/**
	 * The list of unicode cars.
	 *
	 * @since  1.0.0
	 * @var    array    $status    Maintains the unicode cars list.
	 */
	public static $unicode = [
		'A' => '1F1E6',
		'B' => '1F1E7',
		'C' => '1F1E8',
		'D' => '1F1E9',
		'E' => '1F1EA',
		'F' => '1F1EB',
		'G' => '1F1EC',
		'H' => '1F1ED',
		'I' => '1F1EE',
		'J' => '1F1EF',
		'K' => '1F1F0',
		'L' => '1F1F1',
		'M' => '1F1F2',
		'N' => '1F1F3',
		'O' => '1F1F4',
		'P' => '1F1F5',
		'Q' => '1F1F6',
		'R' => '1F1F7',
		'S' => '1F1F8',
		'T' => '1F1F9',
		'U' => '1F1FA',
		'V' => '1F1FB',
		'W' => '1F1FC',
		'X' => '1F1FD',
		'Y' => '1F1FE',
		'Z' => '1F1FF',
	];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Get flag.
	 *
	 * @param   string $cc  The country code (ISO 3166-1 alpha-2).
	 * @return  string      The emoji flag.
	 * @since 1.0.0
	 */
	public static function get( $cc ) {
		$result = \mb_convert_encoding( '&#x1F3C1;', 'UTF-8', 'HTML-ENTITIES' ) . \mb_convert_encoding( '&#xFEFF;', 'UTF-8', 'HTML-ENTITIES' ) . \mb_convert_encoding( '&#xFEFF;', 'UTF-8', 'HTML-ENTITIES' );
		$cc     = strtoupper( $cc );
		$tmp    = '';
		$err    = false;
		if ( array_key_exists( $cc, L10n::$countries ) ) {
			if ( 0 === strpos( '[', L10n::$countries[ $cc ] ) ) {
				return $result;
			}
		}
		foreach ( str_split( $cc ) as $c ) {
			if ( array_key_exists( $c, self::$unicode ) ) {
				$tmp .= \mb_convert_encoding( '&#x' . self::$unicode[ $c ] . ';', 'UTF-8', 'HTML-ENTITIES' );
			} else {
				$err = true;
			}
		}
		if ( ! $err ) {
			$result = $tmp . \mb_convert_encoding( '&#x200B;', 'UTF-8', 'HTML-ENTITIES' );
		}
		return $result;
	}

}
