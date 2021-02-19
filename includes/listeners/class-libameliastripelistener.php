<?php
/**
 * Amelia Stripe library listener for DecaLog.
 *
 * Defines class for Amelia Stripe library listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Listener;

/**
 * Amelia Stripe library listener for DecaLog.
 *
 * Defines methods and properties for Amelia Stripe library listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class LibAmeliaStripeListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    2.4.0
	 */
	protected function init() {
		$this->id      = 'astripe';
		$this->class   = 'library';
		$this->product = 'Stripe Gateway';
		$this->name    = 'Amelia Stripe Gateway';
		if ( $this->is_available() ) {
			$this->version = \AmeliaStripe\Stripe::VERSION;
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
		return class_exists( '\AmeliaStripe\Stripe' );
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
		if ( class_exists( '\Decalog\Integration\AmeliaStripeLogger' ) ) {
			\AmeliaStripe\Stripe::setLogger( new \Decalog\Integration\AmeliaStripeLogger( $this->class, $this->name, $this->version ) );
		}
	}
}
