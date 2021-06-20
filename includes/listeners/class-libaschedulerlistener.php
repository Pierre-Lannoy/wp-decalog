<?php
/**
 * Action Scheduler library listener for DecaLog.
 *
 * Defines class for Action Scheduler library listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Listener;

/**
 * Action Scheduler library listener for DecaLog.
 *
 * Defines methods and properties for Action Scheduler library listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class LibASchedulerListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    2.4.0
	 */
	protected function init() {
		$this->id      = 'ascheduler';
		$this->class   = 'library';
		$this->product = 'Action Scheduler';
		$this->name    = 'Action Scheduler';
		if ( $this->is_available() ) {
			$this->version = \ActionScheduler_Versions::instance()->latest_version();
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
		return ( class_exists( 'ActionScheduler' ) && class_exists( 'ActionScheduler_Versions' ) );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    2.4.0
	 */
	protected function launch() {
		add_action( 'action_scheduler_before_process_queue', [ $this, 'action_scheduler_before_process_queue' ], 10, 0 );
		add_action( 'action_scheduler_after_process_queue', [ $this, 'action_scheduler_after_process_queue' ], 10, 0 );
		add_action( 'action_scheduler_pre_init', [ $this, 'action_scheduler_pre_init' ], 10, 0 );
		add_action( 'action_scheduler_stored_action', [ $this, 'action_scheduler_stored_action' ], 10, 1 );
		add_action( 'action_scheduler_canceled_action', [ $this, 'action_scheduler_canceled_action' ], 10, 1 );
		add_action( 'action_scheduler_deleted_action', [ $this, 'action_scheduler_deleted_action' ], 10, 1 );
		add_action( 'action_scheduler_before_execute', [ $this, 'action_scheduler_before_execute' ], 10, 1 );
		add_action( 'action_scheduler_begin_execute', [ $this, 'action_scheduler_begin_execute' ], 10, 1 );
		add_action( 'action_scheduler_after_execute', [ $this, 'action_scheduler_after_execute' ], 10, 1 );
		add_action( 'action_scheduler_failed_execution', [ $this, 'action_scheduler_failed_execution' ], 10, 2 );
		add_action( 'action_scheduler_failed_validation', [ $this, 'action_scheduler_failed_validation' ], 10, 2 );
		add_action( 'action_scheduler_failed_to_schedule_next_instance', [ $this, 'action_scheduler_failed_to_schedule_next_instance' ], 10, 2 );
		add_action( 'action_scheduler_failed_old_action_deletion', [ $this, 'action_scheduler_failed_old_action_deletion' ], 10, 2 );
		add_action( 'action_scheduler_failed_action', [ $this, 'action_scheduler_failed_action' ], 10, 2 );
		add_action( 'action_scheduler_unexpected_shutdown', [ $this, 'action_scheduler_unexpected_shutdown' ], 10, 2 );
		add_action( 'action_scheduler_reset_action', [ $this, 'action_scheduler_reset_action' ], 10, 1 );
		add_action( 'action_scheduler_execution_ignored', [ $this, 'action_scheduler_execution_ignored' ], 10, 1 );
		add_action( 'action_scheduler_failed_fetch_action', [ $this, 'action_scheduler_failed_fetch_action' ], 10, 1 );
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
	 * "action_scheduler_before_process_queue" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_before_process_queue() {
		$this->logger->debug( 'Starting process queue.' );
	}

	/**
	 * "action_scheduler_after_process_queue" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_after_process_queue() {
		$this->logger->debug( 'Ending process queue.' );
	}

	/**
	 * "action_scheduler_pre_init" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_pre_init() {
		$this->logger->debug( 'Action Scheduler initialization.' );
	}

	/**
	 * "action_scheduler_stored_action" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_stored_action( $action_id ) {
		$this->logger->info( sprintf( 'Action "%s" (action ID %s) created.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_canceled_action" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_canceled_action( $action_id ) {
		$this->logger->notice( sprintf( 'Action "%s" (action ID %s) canceled.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_deleted_action" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_deleted_action( $action_id ) {
		$this->logger->notice( sprintf( 'Action "%s" (action ID %s) deleted.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_before_execute" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_before_execute( $action_id ) {
		$this->logger->info( sprintf( 'Action "%s" (action ID %s) started.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_begin_execute" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_begin_execute( $action_id ) {
		$this->logger->debug( sprintf( 'Action "%s" (action ID %s) running.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_after_execute" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_after_execute( $action_id ) {
		$this->logger->info( sprintf( 'Action "%s" (action ID %s) completed.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_failed_execution" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_failed_execution( $action_id, $exception ) {
		$this->logger->error( sprintf( 'Action "%s" (action ID %s) failed to execute: %s.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id, ( $exception instanceof \Throwable ? $exception->getMessage() : 'unknown error' ) ) );
	}

	/**
	 * "action_scheduler_failed_to_schedule_next_instance" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_failed_to_schedule_next_instance( $action_id, $exception ) {
		$this->logger->error( sprintf( 'Unable to schedule next instance of action "%s" (action ID %s): %s.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id, ( $exception instanceof \Throwable ? $exception->getMessage() : 'unknown error' ) ) );
	}

	/**
	 * "action_scheduler_failed_old_action_deletion" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_failed_old_action_deletion( $action_id, $exception ) {
		$this->logger->error( sprintf( 'Unable to delete lod action "%s" (action ID %s): %s.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id, ( $exception instanceof \Throwable ? $exception->getMessage() : 'unknown error' ) ) );
	}

	/**
	 * "action_scheduler_failed_validation" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_failed_validation( $action_id, $exception ) {
		$this->logger->error( sprintf( 'Action "%s" (action ID %s) failed to validate: %s.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id, ( $exception instanceof \Throwable ? $exception->getMessage() : 'unknown error' ) ) );
	}

	/**
	 * "action_scheduler_failed_action" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_failed_action( $action_id, $timeout ) {
		$this->logger->warning( sprintf( 'Action "%s" (action ID %s) timed out after %s seconds.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id, $timeout ) );
	}

	/**
	 * "action_scheduler_unexpected_shutdown" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_unexpected_shutdown( $action_id, $error ) {
		if ( ! empty( $error ) ) {
			$this->logger->alert( sprintf( 'Unexpected shutdown: PHP Fatal error %s in %s on line %s.', $error['message'], $error['file'], $error['line'] ) );
		}
	}

	/**
	 * "action_scheduler_reset_action" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_reset_action( $action_id ) {
		$this->logger->debug( sprintf( 'Action "%s" (action ID %s) reset.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_execution_ignored" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_execution_ignored( $action_id ) {
		$this->logger->debug( sprintf( 'Action "%s" (action ID %s) ignored.', \ActionScheduler::store()->fetch_action( $action_id )->get_hook(), $action_id ) );
	}

	/**
	 * "action_scheduler_failed_fetch_action" event.
	 *
	 * @since    2.4.0
	 */
	public function action_scheduler_failed_fetch_action( $action_id ) {
		$this->logger->critical( sprintf( 'Unable to fetch action ID %s.', $action_id ) );
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
