<?php
/**
 * Matomo for WordPress listener for DecaLog.
 *
 * Defines class for Matomo for WordPress listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.5.0
 */

namespace Decalog\Listener;

/**
 * Matomo for WordPress listener for DecaLog.
 *
 * Defines methods and properties for Matomo for WordPress listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.5.0
 */
class MatomoListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    3.5.0
	 */
	protected function init() {
		$this->id      = 'matomo';
		$this->class   = 'plugin';
		$this->product = 'Matomo for WordPress';
		$this->name    = 'Matomo for WordPress';
		/*$version       = DECALOG_ALL_PLUGINS_DIR . 'matomo/app/core/Version.php';
		if ( file_exists( $version ) ) {
			require $version;
		}*/
		if ( class_exists( '\Piwik\Version' ) ) {
			$this->version = \Piwik\Version::VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    3.5.0
	 */
	protected function is_available() {
		return false;//class_exists( '\WpMatomo\Logger' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    3.5.0
	 */
	protected function launch() {
		add_filter( 'matomo_register_psr3_log_handlers', [ $this, 'matomo_register_psr3_log_handlers' ], 0, 1 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    3.5.0
	 */
	protected function launched() {
		// No post-launch operations
	}

	/**
	 * "matomo_register_psr3_log_handlers" filter.
	 *
	 * @since    3.5.0
	 */
	public function matomo_register_psr3_log_handlers( $handlers ) {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new \Decalog\Integration\MatomoLogger( $this->class, $this->name, $this->version );
		}
		array_push( $handlers, self::$instance );
		return $handlers;
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.5.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		// No monitors to finalize.
	}
}
