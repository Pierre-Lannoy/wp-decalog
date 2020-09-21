<?php
/**
 * PHP utilities.
 *
 * Helpers for PHP paths and files handling.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Decalog\System;

use Decalog\System\Option;
use Decalog\System\File;
use Decalog\Logger;

/**
 * Define the PHP functionality.
 *
 * Helpers for PHP paths and files handling.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class PHP {

	/**
	 * Normalizes a path+file.
	 *
	 * @param   string  $file   The raw file name.
	 * @return  string  The normalized file name.
	 * @since   2.0.0
	 */
	public static function normalized_file( $file ) {
		if ( 'unknown' === $file || '' === $file ) {
			return 'PHP kernel';
		}
		if ( false !== strpos( $file, 'phar://' ) ) {
			return str_replace( 'phar://', '', wp_normalize_path( $file ) );
		}
		return './' . str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $file ) );
	}

	/**
	 * Normalizes a path+file.
	 *
	 * @param   string  $file   The raw file name.
	 * @param   string  $line   Optional. The file line.
	 * @return  string  The normalized file name.
	 * @since   2.0.0
	 */
	public static function normalized_file_line( $file, $line = '' ) {
		if ( '' === (string) $line || '0' === (string) $line ) {
			return self::normalized_file( $file );
		}
		return self::normalized_file( $file ) . ':' . (string) $line;
	}

}
