<?php
/**
 * Files handling
 *
 * Handles all files operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the files functionality.
 *
 * Handles all files operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class File {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Lists files.
	 *
	 * @param   string  $folder         Optional. The starting folder.
	 * @param   integer $levels         Optional. Level of recursion (1 = no recursion).
	 * @param   array   $file_include   Optional. Include list (regex) for file whitelist mode.
	 * @param   array   $file_exclude   Optional. Exclude list (regex) for file blacklist mode.
	 * @param   boolean $hidden         Optional. List hidden files too.
	 * @return  array  The list of files.
	 * @since   1.0.0
	 */
	public static function list_files( $folder = ABSPATH, $levels = 100, $file_include = [], $file_exclude = [], $hidden = false ) {
		if ( empty( $folder ) || ! $levels ) {
			return [];
		}
		$folder = trailingslashit( $folder );
		$files  = [];
		// phpcs:ignore
		$dir = @opendir( $folder );
		if ( $dir ) {
			while ( false !== ( $file = readdir( $dir ) ) ) {
				if ( in_array( $file, [ '.', '..' ], true ) ) {
					continue;
				}
				if ( ! $hidden && '.' === $file[0] ) {
					continue;
				}
				if ( is_dir( $folder . $file ) ) {
					$files = array_merge( $files, self::list_files( $folder . $file, $levels - 1, $file_include, $file_exclude, $hidden ) );
				} else {
					if ( 0 < count( $file_include ) ) {
						$continue = true;
						foreach ( $file_include as $rule ) {
							if ( preg_match( $rule, $folder . $file ) ) {
								$continue = false;
								break;
							}
						}
						if ( $continue ) {
							continue;
						}
					}
					if ( 0 < count( $file_exclude ) ) {
						foreach ( $file_exclude as $rule ) {
							if ( preg_match( $rule, $folder . $file ) ) {
								continue;
							}
						}
					}
					$files[] = $folder . $file;
				}
			}
		}
		// phpcs:ignore
		@closedir( $dir );
		return $files;
	}

}
