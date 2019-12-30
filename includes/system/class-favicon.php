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
		if ( filter_var( $name, FILTER_VALIDATE_IP ) ) {
			if ( ! filter_var( $name, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE ) ) {
				return self::get_private();
			}
		}
		if ( ! Option::network_get( 'download_favicons' ) ) {
			return self::get_default();
		}
		$logger = new Logger( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$dir    = WP_CONTENT_DIR . '/cache/site-favicons/';
		$name   = strtolower( $name );
		if ( 0 === strpos( $name, '192.0.' ) ) {   // Automattic has IPs from 192.0.64.0 to 192.0.127.254.
			$c = substr( $name, 6 );
			if ( false !== strpos( $c, '.' ) ) {
				$c = (int) substr( $c, 0, strpos( $c, '.' ) );
				if ( $c >= 64 && $c <= 127 ) {
					$name = 'automattic.com';
				}
			}
		}
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
	 * Returns default (unknown) favicon.
	 *
	 * @return string The default favicon.
	 * @since 1.0.0
	 */
	private static function get_private() {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAEt2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICB4bWxuczpleGlmPSJodHRwOi8vbnMuYWRvYmUuY29tL2V4aWYvMS4wLyIKICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgdGlmZjpJbWFnZUxlbmd0aD0iMTYiCiAgIHRpZmY6SW1hZ2VXaWR0aD0iMTYiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249IjcyLjAiCiAgIHRpZmY6WVJlc29sdXRpb249IjcyLjAiCiAgIGV4aWY6UGl4ZWxYRGltZW5zaW9uPSIxNiIKICAgZXhpZjpQaXhlbFlEaW1lbnNpb249IjE2IgogICBleGlmOkNvbG9yU3BhY2U9IjEiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgeG1wOk1vZGlmeURhdGU9IjIwMTktMTItMzBUMTI6NDc6MjErMDE6MDAiCiAgIHhtcDpNZXRhZGF0YURhdGU9IjIwMTktMTItMzBUMTI6NDc6MjErMDE6MDAiPgogICA8eG1wTU06SGlzdG9yeT4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGkKICAgICAgc3RFdnQ6YWN0aW9uPSJwcm9kdWNlZCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWZmaW5pdHkgUGhvdG8gKFNlcCAyNiAyMDE5KSIKICAgICAgc3RFdnQ6d2hlbj0iMjAxOS0xMi0zMFQxMjo0NzoyMSswMTowMCIvPgogICAgPC9yZGY6U2VxPgogICA8L3htcE1NOkhpc3Rvcnk+CiAgPC9yZGY6RGVzY3JpcHRpb24+CiA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgo8P3hwYWNrZXQgZW5kPSJyIj8+84g2pQAAAYFpQ0NQc1JHQiBJRUM2MTk2Ni0yLjEAACiRdZHPK0RRFMc/ZkbEiGJhIb00rIwYNbFRRkJNmsYog83MMz/U/Hi99ybJVtlOUWLj14K/gK2yVopIycbGmtig5zyjZpI5t3PP537vPad7zwVHJKNmDVc/ZHOmHp4IKHPReaXuCTedNNKLK6Ya2mgoFKSqvd9SY8drr12r+rl/rXEpYahQUy88omq6KTwpHFwxNZu3hNvUdGxJ+ES4V5cLCt/YerzEzzanSvxpsx4Jj4GjRVhJVXC8gtW0nhWWl+PJZgrq733sl7gTudkZiV3iHRiEmSCAwhTjjOFngGGZ/Xjx0ScrquT3/+RPk5dcVWaNVXSWSZHGlN4qFKR6QmJS9ISMDKt2///21UgO+krV3QGofbSs126o24SvomV9HFjW1yE4H+A8V87P78PQm+jFsubZg+Z1OL0oa/FtONuA9nstpsd+JKe4I5mEl2NoikLrFTQslHr2u8/RHUTW5KsuYWcXeuR88+I3T+Jn2/OsiQMAAAAJcEhZcwAACxMAAAsTAQCanBgAAAEUSURBVDiNldKxSgNREAXQkxhFEZWAgtiJIKSLYGHlN1iqxC61iqkWGyu3kPxBGgsRVPAf/AJLBUFsbETENEYbLfKiy7JJ1lvN3Jl737z3ptCIW2M4Rg3j8qGDM0QlnGA3p7CHGRxgtIjtf4qTqJUw26f4jJcQz2Eho6dcHOC+2Yzq1WZUr2KnX1Mpld/gMcRPCf4BpyFexHqvUGjEre9EY6UZ1e8GTKURt5Zx38vTV+gMEgd8JZNBb5AL6TfQiFsF3Z9597dYn5jC61AD3SU5xDVWAneLDRwNM5gMp0Ehw3xaat3TBpdohyusBoHAzWMtwf0avKEc8kqitpQxQZprF3GV0ZgXFyXsYwRbmMgp/MA59n4AgEMtjb8i5Y4AAAAASUVORK5CYII=';
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
