<?php
/**
 * Cavalcade listener for DecaLog.
 *
 * Defines class for Cavalcade listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.3.0
 */

namespace Decalog\Listener;

use Decalog\System\Option;

/**
 * Cavalcade listener for DecaLog.
 *
 * Defines methods and properties for Cavalcade listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.3.0
 */
class LibCavalcadeListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    3.3.0
	 */
	protected function init() {
		$this->id      = 'cavalcade';
		$this->class   = 'library';
		$this->product = 'Cavalcade';
		$this->name    = 'Cavalcade';
		if ( function_exists( 'HM\Cavalcade\Plugin\get_database_version' ) && function_exists( '\HM\Cavalcade\Plugin\is_installed' ) && \HM\Cavalcade\Plugin\is_installed() ) {
			$this->version = 'DBv' . \HM\Cavalcade\Plugin\get_database_version();
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    3.3.0
	 */
	protected function is_available() {
		return function_exists( '\HM\Cavalcade\Plugin\is_installed' ) && \HM\Cavalcade\Plugin\is_installed();
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    3.3.0
	 */
	protected function launch() {
		add_filter( 'schedule_event', [ $this, 'schedule_event' ], PHP_INT_MAX, 1 );
		add_filter( 'pre_clear_scheduled_hook', [ $this, 'pre_clear_scheduled_hook' ], PHP_INT_MAX, 2 );
		add_filter( 'pre_unschedule_hook', [ $this, 'pre_unschedule_hook' ], PHP_INT_MAX, 2 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    3.3.0
	 */
	protected function launched() {
		// No post-launch operations
	}

	/**
	 * "schedule_event" event.
	 *
	 * @since    3.3.0
	 */
	public function schedule_event( $event = null ) {
		if ( $event && is_object( $event ) && property_exists( $event, 'hook' ) && property_exists( $event, 'schedule' ) && property_exists( $event, 'timestamp' ) ) {
			if ( $event->schedule ) {
				$this->logger->debug( sprintf( 'The recurring event "%s" has been scheduled to "%s".', $event->hook, $event->schedule ) );
			} else {
				$this->logger->debug( sprintf( 'The single event "%s" has been (re)scheduled and will be executed %s.', $event->hook, ( 0 === $event->timestamp - time() ? 'immediately' : sprintf( 'in %d seconds', $event->timestamp - time() ) ) ) );
			}
		} else {
			$this->logger->notice( 'A plugin prevented an event to be scheduled or rescheduled.' );
		}
		return $event;
	}

	/**
	 * "pre_clear_scheduled_hook" event.
	 *
	 * @since    3.3.0
	 */
	public function pre_clear_scheduled_hook( $pre, $hook, $args = null, $wp_error = null ) {
		if ( is_int( $pre ) && 0 < $pre ) {
			$this->logger->info( sprintf( 'The "%s" event will be cleared.', $hook ) );
		} elseif ( 0 === $pre ) {
			$this->logger->info( 'No event to clear.' );
		} else {
			$this->logger->notice( sprintf( 'A plugin prevented the "%s" event to be cleared.', $hook ) );
		}
		return $pre;
	}

	/**
	 * "pre_unschedule_hook" event.
	 *
	 * @since    3.3.0
	 */
	public function pre_unschedule_hook( $pre, $hook, $wp_error = null ) {
		if ( is_int( $pre ) && 0 < $pre ) {
			$this->logger->info( sprintf( 'The "%s" event will be unscheduled.', $hook ) );
		} elseif ( 0 === $pre ) {
			$this->logger->info( 'No event to unschedule.' );
		} else {
			$this->logger->notice( sprintf( 'A plugin prevented the "%s" event to be unscheduled.', $hook ) );
		}
		return $pre;
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.3.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		if ( ! \Decalog\Plugin\Feature\DMonitor::$active ) {
			return;
		}
		// No monitors to finalize.
	}
}
