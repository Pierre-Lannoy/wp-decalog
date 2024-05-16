<?php
/**
 * WP Migrate listener for DecaLog.
 *
 * Defines class for WP Migrate listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   4.1.0
 */

namespace Decalog\Listener;

/**
 * WP Migrate listener for DecaLog.
 *
 * Defines methods and properties for WP Migrate listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   4.1.0
 */
class WpmigrateListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    4.1.0
	 */
	protected function init() {
		$this->id    = 'wpmigrate';
		$this->class = 'plugin';
		$this->name  = 'WP Migrate';
		if ( defined( 'WPMDB_PRO' ) && WPMDB_PRO ) {
			$this->product = 'WP Migrate Pro';
		} else {
			$this->product = 'WP Migrate Lite';
		}
		$this->version = $GLOBALS['wpmdb_meta']['wp-migrate-db-pro']['version'] ?? 'x';
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    4.1.0
	 */
	protected function is_available() {
		return function_exists( 'wp_migrate_db_pro' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    4.1.0
	 */
	protected function launch() {
		add_action( 'wpmdb_migration_complete', [ $this, 'wpmdb_migration_complete' ], 10, 2 );
		add_action( 'wpmdb_cli_before_migration', [ $this, 'wpmdb_cli_before_migration' ], 10, 2 );
		add_action( 'wpmdb_initiate_migration', [ $this, 'wpmdb_initiate_migration' ], 10, 1 );
		add_action( 'wpmdb_error_migration', [ $this, 'wpmdb_error_migration' ], 10, 1 );

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
	 * "wpmdb_migration_complete" event.
	 *
	 * @since    4.1.0
	 */
	public function wpmdb_migration_complete( $type, $url ) {
		$url_parts = wp_parse_url( $url );
		$url       = $url_parts['host'];
		$secured   = ( 'https' === $url_parts['scheme'] ) ? 'Secured' : 'Unsecured';
		$message = sprintf( '%s %s for "%s" completed.', $secured, ucfirst( strtolower( $type??'unknown action' ) ), $url );
		$this->logger->notice( $message );
	}

	/**
	 * "wpmdb_cli_before_migration" event.
	 *
	 * @since    4.1.0
	 */
	public function wpmdb_cli_before_migration( $post_data, $profile ) {
		$action = 'unknown';
		if ( is_array( $profile ) ) {
			$action = $profile['action'] ?? 'unknown';
		}
		$this->logger->info( sprintf( 'Initiating unattended %s action.', $action ) );
	}

	/**
	 * "wpmdb_initiate_migration" event.
	 *
	 * @since    4.1.0
	 */
	public function wpmdb_initiate_migration( $profile ) {
		$action = 'unknown';
		if ( is_array( $profile ) ) {
			$action = $profile['intent'] ?? 'unknown';
		}
		$this->logger->info( sprintf( 'Starting %s action…', $action ) );
	}

	/**
	 * "wpmdb_error_migration" event.
	 *
	 * @since    4.1.0
	 */
	public function wpmdb_error_migration( $error_message = '' ) {
		$this->logger->error( $error_message );
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    4.1.0
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
