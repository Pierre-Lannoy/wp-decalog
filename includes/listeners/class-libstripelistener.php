<?php
/**
 * Stripe library listener for DecaLog.
 *
 * Defines class for Stripe library listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Listener;

/**
 * Stripe library listener for DecaLog.
 *
 * Defines methods and properties for Stripe library listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class LibStripeListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    2.4.0
	 */
	protected function init() {
		$this->id      = 'stripe';
		$this->class   = 'library';
		$this->product = 'Stripe Gateway';
		$this->name    = 'Standard Stripe Gateway';
		if ( $this->is_available() ) {
			$this->version = \Stripe\Stripe::VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    2.4.0
	 */
	protected function is_available() {
		return class_exists( '\Stripe\Stripe' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    2.4.0
	 */
	protected function launch() {
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		if ( class_exists( '\Decalog\Integration\StripeLogger' ) ) {
			\Stripe\Stripe::setLogger( new \Decalog\Integration\StripeLogger( $this->class, $this->name, $this->version ) );
		}
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
