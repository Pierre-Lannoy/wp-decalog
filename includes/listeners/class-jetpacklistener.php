<?php
/**
 * Jetpack listener for DecaLog.
 *
 * Defines class for Jetpack listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.4.0
 */

namespace Decalog\Listener;

/**
 * Jetpack listener for DecaLog.
 *
 * Defines methods and properties for User Switching listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.4.0
 */
class JetpackListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'jetpack';
		$this->class   = 'plugin';
		$this->product = 'Jetpack';
		$this->name    = 'Jetpack';
		if ( defined( 'JETPACK__VERSION' ) ) {
			$this->version = JETPACK__VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.4.0
	 */
	protected function is_available() {
		return class_exists( 'Jetpack' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.4.0
	 */
	protected function launch() {
		add_action( 'jetpack_log_entry', [ $this, 'jetpack_log_entry' ], 10, 1 );
		return true;
	}

	/**
	 * "jetpack_log_entry" event.
	 *
	 * @since    1.4.0
	 */
	public function jetpack_log_entry( $log_entry)  {
		$this->logger->emergency( print_r($log_entry, true) );
	}
}
