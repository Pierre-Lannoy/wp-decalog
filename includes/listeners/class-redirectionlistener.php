<?php
/**
 * Redirection listener for DecaLog.
 *
 * Defines class for Redirection listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.9.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;

/**
 * Redirection listener for DecaLog.
 *
 * Defines methods and properties for Redirection listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.9.0
 */
class RedirectionListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.9.0
	 */
	protected function init() {
		$this->id      = 'redirection';
		$this->class   = 'plugin';
		$this->product = 'Redirection';
		$this->name    = 'Redirection';
		if ( defined( 'REDIRECTION_VERSION' ) ) {
			$this->version = REDIRECTION_VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.9.0
	 */
	protected function is_available() {
		return defined( 'REDIRECTION_VERSION' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.9.0
	 */
	protected function launch() {
		add_filter( 'redirection_log_data', [ $this, 'redirection_log_data' ] );
		add_filter( 'redirection_404_data', [ $this, 'redirection_404_data' ] );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		// No post-launch operations
	}

	/**
	 * "redirection_log_data" event.
	 *
	 * @since    1.9.0
	 */
	public function redirection_log_data( $insert ) {
		$this->logger->info( sprintf( 'URL %s redirected to %s.', $insert['url'], $insert['sent_to'] ), 30 );
		return $insert;
	}

	/**
	 * "redirection_404_data" event.
	 *
	 * @since    1.9.0
	 */
	public function redirection_404_data( $insert ) {
		$this->logger->warning( sprintf( 'Page not found: %s.', $insert['url'] ), 404 );
		return $insert;
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.0.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		// No monitors to finalize.
	}

}
