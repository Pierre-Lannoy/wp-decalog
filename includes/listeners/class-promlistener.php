<?php
/**
 * Pseudo Prometheus listener for DecaLog.
 *
 * Defines class for pseudo Prometheus listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Listener;

/**
 * Pseudo Prometheus listener for DecaLog.
 *
 * Defines methods and properties for pseudo Prometheus listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class PromListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    3.0.0
	 */
	protected function init() {
		$this->id      = 'prom';
		$this->name    = esc_html__( 'Prometheus compliant listeners', 'decalog' );
		$this->class   = 'prom';
		$this->product = DECALOG_PRODUCT_NAME;
		$this->version = DECALOG_VERSION;
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    3.0.0
	 */
	protected function is_available() {
		return true;
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    3.0.0
	 */
	protected function launch() {
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    3.0.0
	 */
	protected function launched() {
		// No post-launch operations
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.0.0
	 */
	public function monitoring_close() {
		// No monitors to finalize.
	}

}
