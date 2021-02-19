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

use Decalog\Plugin\Feature\EventTypes;

/**
 * WP Super Cache listener for DecaLog.
 *
 * Defines methods and properties for WP Super Cache listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class UpdraftplusListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.14.0
	 */
	protected function init() {
		global $updraftplus;
		$this->id    = 'updraftplus';
		$this->class = 'plugin';
		if ( isset( $updraftplus ) ) {
			$this->product = $updraftplus->plugin_title;
			$this->version = $updraftplus->version;
		} else {
			$this->product = 'UpdraftPlus Backup/Restore';
			$this->version = 'x';
		}
		$this->name = $this->product;
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.6.0
	 */
	protected function is_available() {
		global $updraftplus;
		return isset( $updraftplus );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.6.0
	 */
	protected function launch() {
		add_filter( 'updraftplus_logline', [ $this, 'updraftplus_logline' ], 0, 5 );
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
	 * "updraftplus_logline" filter.
	 *
	 * @since    1.6.0
	 */
	public function updraftplus_logline( $line, $nonce, $level, $uniq_id, $destination ) {
		switch ( $level ) {
			case 'notice':
				$severity = 'info';
				break;
			default:
				$severity = $level;
		}
		$this->logger->log( EventTypes::get_standard_level( $severity ), '[' . strtolower( $destination ) . '] ' . $line );
		return $line;
	}
}
