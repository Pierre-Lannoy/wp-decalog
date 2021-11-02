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
use Decalog\System\Markdown;
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
		Option::network_set( 'version', DECALOG_VERSION );
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
		// Updates MU-Plugin and dropin function.
		decalog_reset_earlyloading();
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
		$md = new Markdown();
		return $md->get_shortcode(  'CHANGELOG.md', $attributes  );
	}
}
