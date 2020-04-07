<?php
/**
 * Plugin updates handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\Plugin\Feature\Log;
use Decalog\Plugin\Feature\LoggerMaintainer;
use Parsedown;
use Decalog\System\Nag;
use Decalog\System\Option;
use Decalog\System\Environment;
use Decalog\System\Role;
use Exception;

/**
 * Plugin updates handling.
 *
 * This class defines all code necessary to handle the plugin's updates.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Updater {

	/**
	 * Initializes the class, set its properties and performs
	 * post-update processes if needed.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$old = Option::network_get( 'version' );
		if ( DECALOG_VERSION !== $old ) {
			if ( '0.0.0' === $old ) {
				$this->install();
				// phpcs:ignore
				$message = sprintf( esc_html__( '%1$s has been correctly installed.', 'decalog' ), DECALOG_PRODUCT_NAME );
			} else {
				$this->update( $old );
				// phpcs:ignore
				$message = sprintf( esc_html__( '%1$s has been correctly updated from version %2$s to version %3$s.', 'decalog' ), DECALOG_PRODUCT_NAME, $old, DECALOG_VERSION );
				$logger  = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
				$logger->notice( $message );
				// phpcs:ignore
				$message .= ' ' . sprintf( __( 'See <a href="%s">what\'s new</a>.', 'decalog' ), admin_url( 'admin.php?page=decalog-settings&tab=about' ) );
			}
			Nag::add( 'update', 'info', $message );
			Option::network_set( 'version', DECALOG_VERSION );
		}
	}

	/**
	 * Performs post-installation processes.
	 *
	 * @since 1.0.0
	 */
	private function install() {

	}

	/**
	 * Performs post-update processes.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since 1.0.0
	 */
	private function update( $from ) {
		// Starting 1.3.x, PushoverHandler is replaced by PshHandler.
		$loggers = Option::network_get( 'loggers', null );
		if ( isset( $loggers ) ) {
			foreach ( $loggers as &$logger ) {
				if ( array_key_exists( 'handler', $logger ) ) {
					if ( 'PushoverHandler' === $logger['handler'] ) {
						$logger['handler'] = 'PshHandler';
					}
				}
			}
			Option::network_set( 'loggers', $loggers );
		}
		// DecaLog handlers auto updating.
		$maintainer = new LoggerMaintainer();
		$maintainer->update( $from );
		// Updates MU-Plugin (even if it's not a new version) to avoid update message.
		$target = WPMU_PLUGIN_DIR . '/_decalog_loader.php';
		$source = DECALOG_PLUGIN_DIR . '/assets/_decalog_loader.php';
		if ( ! file_exists( WPMU_PLUGIN_DIR ) ) {
			// phpcs:ignore
			@mkdir( WPMU_PLUGIN_DIR );
		}
		if ( file_exists( $source ) ) {
			// phpcs:ignore
			@unlink( $target );
			// phpcs:ignore
			@copy( $source, $target );
		}
	}

	/**
	 * Get the changelog.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_changelog( $attributes ) {
		$_attributes = shortcode_atts(
			[
				'style' => 'html',
				'mode'  => 'clean',
			],
			$attributes
		);
		$style       = $_attributes['style'];
		$mode        = $_attributes['mode'];
		$error       = esc_html__( 'Sorry, unable to find or read changelog file.', 'decalog' );
		$result      = esc_html( $error );
		$changelog   = DECALOG_PLUGIN_DIR . 'CHANGELOG.md';
		if ( file_exists( $changelog ) ) {
			try {
				// phpcs:ignore
				$content = wp_kses(file_get_contents( $changelog ), [] );
				if ( $content ) {
					switch ( $style ) {
						case 'html':
							$result = $this->html_changelog( $content, ( 'clean' === $mode ) );
							break;
						default:
							$result = esc_html( $content );
					}
				}
			} catch ( Exception $e ) {
				$result = esc_html( $error );
			}
		}
		return $result;
	}

	/**
	 * Format a changelog in html.
	 *
	 * @param   string  $content  The raw changelog in markdown.
	 * @param   boolean $clean    Optional. Should the output be cleaned?.
	 * @return  string  The converted changelog, ready to print.
	 * @since   1.0.0
	 */
	private function html_changelog( $content, $clean = false ) {
		$markdown = new Parsedown();
		$result   = $markdown->text( $content );
		if ( $clean ) {
			$result = preg_replace( '/<h1>.*<\/h1>/iU', '', $result );
			for ( $i = 8; $i > 1; $i-- ) {
				$result = str_replace( array( '<h' . $i . '>', '</h' . $i . '>' ), array( '<h' . (string) ( $i + 1 ) . '>', '</h' . (string) ( $i + 1 ) . '>' ), $result );
			}
		}
		return wp_kses(
			$result,
			[
				'a'          => [
					'href'  => [],
					'title' => [],
					'rel'   => [],
				],
				'blockquote' => [ 'cite' => [] ],
				'br'         => [],
				'p'          => [],
				'code'       => [],
				'pre'        => [],
				'em'         => [],
				'strong'     => [],
				'ul'         => [],
				'ol'         => [],
				'li'         => [],
				'h3'         => [],
				'h4'         => [],
			]
		);
	}
}
