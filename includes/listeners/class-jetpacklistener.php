<?php
/**
 * Jetpack listener for DecaLog.
 *
 * Defines class for Jetpack listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */

namespace Decalog\Listener;

use Decalog\System\Option;

/**
 * Jetpack listener for DecaLog.
 *
 * Defines methods and properties for Jetpack class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
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
	 * @since    1.6.0
	 */
	protected function is_available() {
		return class_exists( 'Jetpack' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.6.0
	 */
	protected function launch() {
		add_action( 'jetpack_log_entry', [ $this, 'jetpack_log_entry' ], 10, 2 );
		add_action( 'jpp_log_failed_attempt', [ $this, 'jpp_log_failed_attempt' ], 10, 1 );
		add_action( 'jpp_kill_login', [ $this, 'jpp_kill_login' ], 10, 1 );
		add_action( 'jetpack_site_registered', [ $this, 'jetpack_site_registered' ], 10, 3 );
		add_action( 'jetpack_unrecognized_action', [ $this, 'jetpack_unrecognized_action' ], 10, 1 );
		add_action( 'jetpack_activate_module', [ $this, 'jetpack_activate_module' ], 10, 2 );
		add_action( 'jetpack_deactivate_module', [ $this, 'jetpack_deactivate_module' ], 10, 2 );
		add_action( 'jetpack_sync_import_end', [ $this, 'jetpack_sync_import_end' ], 10, 2 );
		add_action( 'jetpack_sitemaps_purge_data', [ $this, 'jetpack_sitemaps_purge_data' ], PHP_INT_MAX, 0 );
		return true;
	}

	/**
	 * "jetpack_log_entry" event.
	 *
	 * @since    1.6.0
	 */
	public function jetpack_log_entry( $log_entry ) {
		// phpcs:ignore
		$this->logger->debug( print_r( $log_entry, true ) );
	}

	/**
	 * "jpp_log_failed_attempt" event.
	 *
	 * @since    1.6.0
	 */
	public function jpp_log_failed_attempt( $user ) {
		if ( array_key_exists( 'login', $user ) ) {
			$name = $user['login'];
		} else {
			$name = 'unknown user';
		}
		if ( Option::network_get( 'pseudonymization' ) ) {
			$name = 'somebody';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Failed login for "%s".', $name ) );
		}
	}

	/**
	 * "jpp_kill_login" event.
	 *
	 * @since    1.6.0
	 */
	public function jpp_kill_login( $ip ) {
		$this->logger->info( sprintf( 'Potential security violations from IP %s.', $ip ) );
	}

	/**
	 * "jetpack_site_registered" event.
	 *
	 * @since    1.6.0
	 */
	public function jetpack_site_registered( $jetpack_id, $jetpack_secret, $jetpack_public ) {
		if ( $jetpack_public ) {
			$this->logger->notice( sprintf( 'Site "%s" publicly registered.', $jetpack_id ) );
		} else {
			$this->logger->notice( sprintf( 'Site "%s" privately registered.', $jetpack_id ) );
		}
	}

	/**
	 * "jetpack_unrecognized_action" event.
	 *
	 * @since    1.6.0
	 */
	public function jetpack_unrecognized_action( $action ) {
		$this->logger->error( sprintf( 'Unrecognized action: %s.', $action ) );
	}

	/**
	 * "jetpack_activate_module" event.
	 *
	 * @since    1.6.0
	 */
	public function jetpack_activate_module( $module, $success = null ) {
		if ( null === $success ) {
			return;
		}
		if ( $success ) {
			$this->logger->notice( sprintf( 'Module "%s" successfully activated.', $module ) );
		} else {
			$this->logger->warning( sprintf( 'Unable to activate module "%s".', $module ) );
		}
	}

	/**
	 * "jetpack_deactivate_module" event.
	 *
	 * @since    1.6.0
	 */
	public function jetpack_deactivate_module( $module, $success = null ) {
		if ( null === $success ) {
			return;
		}
		if ( $success ) {
			$this->logger->notice( sprintf( 'Module "%s" successfully deactivated.', $module ) );
		} else {
			$this->logger->warning( sprintf( 'Unable to deactivate module "%s".', $module ) );
		}
	}

	/**
	 * "jetpack_sync_import_end" event.
	 *
	 * @since    1.6.0
	 */
	public function jetpack_sync_import_end( $importer, $importer_name ) {
		$this->logger->info( sprintf( 'Import "%s" is terminated.', $importer_name ) );
	}

	/**
	 * "jetpack_sitemaps_purge_data" event.
	 *
	 * @since    1.6.0
	 */
	public function jetpack_sitemaps_purge_data() {
		$this->logger->info( 'Sitemaps data purged.' );
	}
}
