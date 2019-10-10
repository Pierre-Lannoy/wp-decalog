<?php
/**
 * Favicons handling
 *
 * Handles all favicons operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

use Decalog\Logger;

/**
 * Define the favicons functionality.
 *
 * Handles all favicons operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Favicon {

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
	 * Get a raw favicon.
	 *
	 * @param   string $name    Optional. The top domain of the site.
	 * @param   boolean $force_download Optional. Forces download instead of default icon if not present.
	 * @return  string  The raw value of the favicon.
	 * @since   1.0.0
	 */
	public static function get_raw( $name = 'wordpress.org', $force_download = false ) {
		if ( ! Option::network_get( 'download_favicons' ) ) {
			return self::get_default();
		}
		$logger   = new Logger( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$dir      = WP_CONTENT_DIR . '/cache/site-favicons/';
		$name     = strtolower( $name );
		$filename = $dir . sanitize_file_name( $name ) . '.png';
		if ( array_key_exists( $name, self::$icons ) ) {
			return self::$icons[ $name ];
		}
		if ( ! file_exists( $dir ) ) {
			try {
				mkdir( $dir, 0755, true );
				$logger->info( 'Created: "' . $dir . '" favicons cache directory.' );
			} catch ( \Exception $ex ) {
				$logger->error( 'Unable to create "' . $dir . '" favicons cache directory.' );
				return self::get_default();
			}
		}
		if ( ! file_exists( $filename ) ) {
			if ( ! $force_download ) {
				return self::get_default();
			}
			$response = wp_remote_get( 'https://www.google.com/s2/favicons?domain=' . esc_url_raw( $name ) );
			if ( is_wp_error( $response ) ) {
				$logger->error( 'Unable to download "' . $name . '" favicon: ' . $response->get_error_message(), $response->get_error_code() );
				return self::get_default();
			}
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$logger->error( 'Unable to download "' . $name . '" favicon.', wp_remote_retrieve_response_code( $response ) );
				return self::get_default();
			}
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			$wp_filesystem->put_contents(
				$filename,
				$response['body'],
				FS_CHMOD_FILE
			);
			if ( $wp_filesystem->errors->has_errors() ) {
				foreach ( $wp_filesystem->errors->get_error_messages() as $message ) {
					$logger->error( 'Unable to download "' . $name . '" favicon: ' . $message );
				}
				return self::get_default();
			}
			$logger->debug( 'Favicon downloaded for "' . $name . '".' );
		}
		// phpcs:ignore
		self::$icons[ $name ] = file_get_contents( $filename );
		return ( self::get_raw( $name ) );
	}

	/**
	 * Returns default (unknown) favicon.
	 *
	 * @return string The default favicon.
	 * @since 1.0.0
	 */
	private static function get_default() {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABs0lEQVR4AWL4//8/RRjO8Iucx+noO0O2qmlbUEnt5r3Juas+hsQD6KaG7dqCKPgx72Pe9GIY27btZBrbtm3btm0nO12D7tVXe63jqtqqU/iDw9K58sEruKkngH0DBljOE+T/qqx/Ln718RZOFasxyd3XRbWzlFMxRbgOTx9QWFzHtZlD+aqLb108sOAIAai6+NbHW7lUHaZkDFJt+wp1DG7R1d0b7Z88EOL08oXwjokcOvvUxYMjBFCamWP5KjKBjKOpZx2HEPj+Ieod26U+dpg6lK2CIwTQH0oECGT5eHj+IgSueJ5fPaPg6PZrz6DGHiGAISE7QPrIvIKVrSvCe2DNHSsehIDatOBna/+OEOgTQE6WAy1AAFiVcf6PhgCGxEvlA9QngLlAQCkLsNWhBZIDz/zg4ggmjHfYxoPGEMPZECW+zjwmFk6Ih194y7VHYGOPvEYlTAJlQwI4MEhgTOzZGiNalRpGgsOYFw5lEfTKybgfBtmuTNdI3MrOTAQmYf/DNcAwDeycVjROgZFt18gMso6V5Z8JpcEk2LPKpOAH0/4bKMCAYnuqm7cHOGHJTBRhAEJN9d/t5zCxAAAAAElFTkSuQmCC';
	}

	/**
	 * Returns a base64 png resource for the icon.
	 *
	 * @param   string $name    Optional. The top domain of the site.
	 * @return string The resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64( $name = 'wordpress.org' ) {
		$source = self::get_raw( $name );
		if ( 0 === strpos( $source, 'data:image/png;base64,' ) ) {
			return $source;
		} else {
			// phpcs:ignore
			return 'data:image/png;base64,' . base64_encode( $source );
		}
	}

}
