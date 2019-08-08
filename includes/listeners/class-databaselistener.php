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
		$this->id = 'wpdb';
		$this->name = esc_html__('Database', 'decalog');
		$this->class = 'db';
		$this->product = 'MySQL';
		$this->version = $wpdb->db_version();
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.0.0
	 */
	protected function is_needed() {
		return true;
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	protected function launch() {
		add_action( 'shutdown', [$this, 'shutdown'], 10, 0 );
		return true;
	}

	/**
	 * "shutdown" event.
	 *
	 * @since    1.0.0
	 */
	public function shutdown() {
		global $EZSQL_ERROR;
		if (isset($this->logger) && is_array($EZSQL_ERROR) && 0 < count($EZSQL_ERROR)) {
			$errors  = $EZSQL_ERROR;
			$last    = end( $errors );
			if (1 === count($EZSQL_ERROR)) {
				$this->logger->error( sprintf( 'A database error was detected during the page rendering: "%s" in the query "%s".', $last[ 'error_str' ], $last[ 'query' ] ) );
			} else {
				$this->logger->critical( sprintf( '%s database errors were detected during the page rendering. The last one is "%s" in the query "%s"', count($EZSQL_ERROR), $last[ 'error_str' ], $last[ 'query' ] ) );
			}
		}
	}

}
