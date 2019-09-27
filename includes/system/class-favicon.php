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
	 * @return  string  The raw value of the favicon.
	 * @since   1.0.0
	 */
	public static function get_raw( $name = 'wordpress.org' ) {
		$logger   = new Logger( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$dir      = WP_CONTENT_DIR . '/cache/site-favicons/';
		$name     = strtolower( $name );
		$filename = $dir . $name . '.png';
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
			$response = wp_remote_get( 'https://www.google.com/s2/favicons?domain=' . $name );
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
		return '';
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
		// phpcs:ignore
		return 'data:image/png;base64,' . base64_encode( $source );
	}

}
