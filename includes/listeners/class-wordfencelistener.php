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
		add_action( 'wordfence_security_event', [ $this, 'wordfence_security_event' ], 10, 2 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		$span = $this->tracer->start_span( 'Metrics collation' );
		if ( class_exists( '\wfBlock' ) ) {
			$this->monitor->create_prod_counter( 'block_duration_permanent', 'Number of permanent blocks - [count]' );
			$this->monitor->create_prod_counter( 'block_duration_temporary', 'Number of current temporary blocks - [count]' );
			$this->monitor->create_prod_counter( 'block_duration_obsolete', 'Number of obsolete temporary blocks - [count]' );
			$this->monitor->create_prod_counter( 'block_type_ip', 'Number of "IP block" blocks - [count]' );
			$this->monitor->create_prod_counter( 'block_type_throttle', 'Number of "IP throttling" blocks - [count]' );
			$this->monitor->create_prod_counter( 'block_type_lockout', 'Number of "lockout" blocks - [count]' );
			$this->monitor->create_prod_counter( 'block_type_country', 'Number of "country" blocks - [count]' );
			$this->monitor->create_prod_counter( 'block_type_advanced', 'Number of "advanced" blocks - [count]' );
		}
		if ( class_exists( '\wfDB' ) ) {
			$this->monitor->create_prod_histogram( 'issue_severity', [ 0.25, 0.5, 0.75 ], 'All issues severities - [percent]' );
			$this->monitor->create_prod_counter( 'issue_new', 'Number of new issues - [count]' );
			$this->monitor->create_prod_counter( 'issue_ignored', 'Number of ignored issues - [count]' );
			$this->monitor->create_prod_counter( 'issue_other', 'Number of other issues - [count]' );
		}
		$this->tracer->end_span( $span );
	}

	/**
	 * "Wordfence_logger" filter.
	 *
	 * @since    1.6.0
	 */
	public function wordfence_security_event( $event, $details = null, $a = null ) {
		$duration = 0;
		$reason   = '';
		$user     = 'an unknown user';
		if ( isset( $details ) && is_array( $details ) ) {
			if ( array_key_exists( 'reason', $details ) ) {
				$reason = $details['reason'];
			}
			if ( array_key_exists( 'duration', $details ) ) {
				$duration = (int) $details['duration'];
			}
			if ( array_key_exists( 'username', $details ) ) {
				$user = '"' . wp_kses( $details['username'], [] ) . '"';
			}
		}
		switch ( $event ) {
			case 'wordfenceDeactivated':
				$this->logger->warning( 'Wordfence is now deactivated.' );
				break;
			case 'lostPasswdForm':
				$this->logger->info( sprintf( 'Attempt to recover the password for %s.', $user ) );
				break;
			case 'loginLockout':
			case 'block':
				$this->logger->info( $reason . ': ' . sprintf( 'this IP is now blocked for %d seconds.', $duration ) );
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
				$this->logger->info( $reason . ': ' . sprintf( 'this IP is now throttled for %d seconds.', $duration ) );
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
		global $wpdb;
		if ( class_exists( '\wfBlock' ) ) {
			$sql = 'SELECT `type`, `expiration` FROM `' . \wfBlock::blocksTable() . '`;';
			//phpcs:ignore
			$lines = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $lines as $line ) {
				if ( array_key_exists( 'type', $line ) && array_key_exists( 'expiration', $line ) ) {
					switch ( (int) $line['type'] ) {
						case \wfBlock::TYPE_IP_MANUAL:
						case \wfBlock::TYPE_IP_AUTOMATIC_TEMPORARY:
						case \wfBlock::TYPE_IP_AUTOMATIC_PERMANENT:
						case \wfBlock::TYPE_WFSN_TEMPORARY:
						case \wfBlock::TYPE_RATE_BLOCK:
							$this->monitor->inc_prod_counter( 'block_type_ip', 1 );
							break;
						case \wfBlock::TYPE_RATE_THROTTLE:
							$this->monitor->inc_prod_counter( 'block_type_throttle', 1 );
							break;
						case \wfBlock::TYPE_LOCKOUT:
							$this->monitor->inc_prod_counter( 'block_type_lockout', 1 );
							break;
						case \wfBlock::TYPE_COUNTRY:
							$this->monitor->inc_prod_counter( 'block_type_country', 1 );
							break;
						case \wfBlock::TYPE_PATTERN:
							$this->monitor->inc_prod_counter( 'block_type_advanced', 1 );
							break;
					}
					if ( 0 === (int) $line['expiration'] ) {
						$this->monitor->inc_prod_counter( 'block_duration_permanent', 1 );
					} elseif ( time() < (int) $line['expiration'] ) {
						$this->monitor->inc_prod_counter( 'block_duration_temporary', 1 );
					} else {
						$this->monitor->inc_prod_counter( 'block_duration_obsolete', 1 );
					}
				}
			}
		}
		if ( class_exists( '\wfDB' ) ) {
			$sql = 'SELECT `status`, `severity` FROM `' . \wfDB::networkTable('wfIssues') . '`;';
			//phpcs:ignore
			$lines = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $lines as $line ) {
				if ( array_key_exists( 'status', $line ) && array_key_exists( 'severity', $line ) ) {
					$this->monitor->observe_prod_histogram( 'issue_severity', (float) $line['severity'] / 100 );
					if ( 'new' === (string) $line['status'] ) {
						$this->monitor->inc_prod_counter( 'issue_new', 1 );
					} elseif ( 0 === strpos( (string) $line['status'], 'ignore' ) ) {
						$this->monitor->inc_prod_counter( 'issue_ignored', 1 );
					} else {
						$this->monitor->inc_prod_counter( 'issue_other', 1 );
					}
				}
			}
		}
	}
}
