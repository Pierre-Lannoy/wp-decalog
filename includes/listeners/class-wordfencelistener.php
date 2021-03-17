<?php
/**
 * Wordfence listener for DecaLog.
 *
 * Defines class for Wordfence listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */

namespace Decalog\Listener;

use Decalog\Logger;

/**
 * Wordfence listener for DecaLog.
 *
 * Defines methods and properties for Wordfence listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class WordfenceListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'wordfence';
		$this->class   = 'plugin';
		$this->product = 'Wordfence Security';
		$this->name    = 'Wordfence Security';
		if ( defined( 'WORDFENCE_VERSION' ) ) {
			$this->version = WORDFENCE_VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.6.0
	 */
	protected function is_available() {
		return class_exists( 'wordfence' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.6.0
	 */
	protected function launch() {
		add_action( 'wordfence_security_event', [ $this, 'wordfence_security_event' ], 10, 1 );
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
	 * "Wordfence_logger" filter.
	 *
	 * @since    1.6.0
	 */
	public function wordfence_security_event( $event, $details = null, $a = null ) {
		switch ( $event ) {
			case 'wordfenceDeactivated':
				$this->logger->warning( 'Wordfence is now deactivated.' );
				break;
			case 'lostPasswdForm':
				$this->logger->info( sprintf( 'Attempt to recover the password for "%s".', wp_kses( $details['username'], [] ) ) );
				break;
			case 'loginLockout':
			case 'block':
				$this->logger->info( $details['reason'] . ' ' . sprintf( 'This IP is now blocked for %s seconds.', $details['duration'] ) );
				break;
			case 'breachLogin':
				$this->logger->info( 'User login blocked for insecure password.' );
				break;
			case 'increasedAttackRate':
				$this->logger->notice( 'Increased Attack Rate.' );
				break;
			case 'autoUpdate':
				$this->logger->notice( 'Wordfence is now updated.' );
				break;
			case 'wafDeactivated':
				$this->logger->warning( 'Wordfence firewall is now deactivated.' );
				break;
			case 'throttle':
				$this->logger->info( $details['reason'] . ' ' . sprintf( 'This IP is now throttled for %s seconds.', $details['duration'] ) );
				break;
			default:
				// phpcs:ignore
				$this->logger->emergency( $event . ' / ' . print_r( $details, true ) );
		}
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
