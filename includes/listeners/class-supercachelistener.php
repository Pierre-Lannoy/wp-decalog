<?php
/**
 * WP Super Cache listener for DecaLog.
 *
 * Defines class for WP Super Cache listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */

namespace Decalog\Listener;

use Decalog\Logger;

/**
 * WP Super Cache listener for DecaLog.
 *
 * Defines methods and properties for WP Super Cache listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class SupercacheListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'supercache';
		$this->class   = 'plugin';
		$this->product = 'WP Super Cache';
		$this->name    = 'WP Super Cache';
		$this->version = '1.x.x';
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.6.0
	 */
	protected function is_available() {
		return defined( 'WPCACHEHOME' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.6.0
	 */
	protected function launch() {
		add_action( 'wp_cache_cleared', [ $this, 'wp_cache_cleared' ], 10, 0 );
		add_action( 'wp_cache_gc', [ $this, 'wp_cache_gc' ], PHP_INT_MAX, 0 );
		add_action( 'gc_cache', [ $this, 'gc_cache' ], PHP_INT_MAX, 2 );
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
	 * "wp_cache_cleared" filter.
	 *
	 * @since    1.6.0
	 */
	public function wp_cache_cleared() {
		$this->logger->info( 'WordPress cache cleared.' );
	}

	/**
	 * "wp_cache_gc" filter.
	 *
	 * @since    1.6.0
	 */
	public function wp_cache_gc() {
		$this->logger->info( 'Garbage collector executed.' );
	}

	/**
	 * "gc_cache" filter.
	 *
	 * @since    1.6.0
	 */
	public function gc_cache( $action, $item ) {
		switch ( $action ) {
			case 'prune':
				$message = 'Item pruned: %s.';
				break;
			case 'rebuild':
				$message = 'Item rebuilt: %s.';
				break;
			default:
				$message = 'Unknown action on: %s.';
		}
		$this->logger->info( sprintf( $message, $item ) );
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
