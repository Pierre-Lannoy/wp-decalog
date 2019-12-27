<?php
/**
 * WP database listener for DecaLog.
 *
 * Defines class for WP database listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;

/**
 * WP database listener for DecaLog.
 *
 * Defines methods and properties for WP database listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class DatabaseListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		global $wpdb;
		$this->id      = 'wpdb';
		$this->name    = esc_html__( 'Database', 'decalog' );
		$this->class   = 'db';
		$this->product = 'MySQL';
		$this->version = $wpdb->db_version();
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.0.0
	 */
	protected function is_available() {
		return true;
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	protected function launch() {
		add_action( 'wp_loaded', [ $this, 'version_check' ] );
		add_action( 'shutdown', [ $this, 'shutdown' ], 10, 0 );
		add_filter( 'wp_die_ajax_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_xmlrpc_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_json_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_jsonp_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_xml_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		return true;
	}

	/**
	 * Check versions modifications.
	 *
	 * @since    1.2.0
	 */
	public function version_check() {
		$db_version  = Environment::mysql_version();
		$old_version = Option::network_get( 'db_version', 'x' );
		if ( 'x' === $old_version ) {
			Option::network_set( 'db_version', $db_version );
			return;
		}
		if ( $db_version === $old_version ) {
			return;
		}
		Option::network_set( 'db_version', $db_version );
		if ( version_compare( $db_version, $old_version, '<' ) ) {
			$this->logger->warning( sprintf( 'MySQL version downgraded from %s to %s.', $old_version, $db_version ) );
			return;
		}
		$this->logger->notice( sprintf( 'MySQL version upgraded from %s to %s.', $old_version, $db_version ) );
	}

	/**
	 * "shutdown" event.
	 *
	 * @since    1.0.0
	 */
	public function shutdown() {
		global $EZSQL_ERROR;
		if ( isset( $this->logger ) && is_array( $EZSQL_ERROR ) && 0 < count( $EZSQL_ERROR ) ) {
			foreach ( $EZSQL_ERROR as $error ) {
				$this->logger->critical( sprintf( 'A database error was detected during the page rendering: "%s" in the query "%s".', $error['error_str'], $error['query'] ) );
			}
		}
	}

	/**
	 * "wp_die_*" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_die_handler( $handler ) {
		$dberror = array_filter(
			// phpcs:ignore
			debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ),
			function ( $item ) {
				return isset( $item['function'] )
				&& isset( $item['class'] )
				&& ( 'bail' === $item['function'] || 'print_error' === $item['function'] )
				&& 'wpdb' === $item['class'];
			}
		);
		if ( ! $handler || ! is_callable( $handler ) || ! $dberror ) {
			return $handler;
		}
		return function ( $message, $title = '', $args = [] ) use ( $handler ) {
			$msg  = '';
			$code = 0;
			if ( is_string( $title ) && '' !== $title ) {
				$title = '"' . $title . '", ';
			}
			if ( is_numeric( $title ) ) {
				$code  = $title;
				$title = '';
			}
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
				$msg  = $title . $message->get_error_message();
				$code = $message->get_error_code();
			} elseif ( is_string( $message ) ) {
				$msg = $title . $message;
			}
			if ( '' !== $msg ) {
				$this->logger->critical( sprintf( 'Database error: %s.', wp_kses( $msg, [] ) ), $code );
			}
			return $handler( $message, $title, $args );
		};
	}

}
