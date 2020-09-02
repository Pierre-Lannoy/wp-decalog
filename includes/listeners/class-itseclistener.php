<?php
/**
 * iThemes Security listener for DecaLog.
 *
 * Defines class for iThemes Security listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */

namespace Decalog\Listener;

use Decalog\System\Plugin;
use Decalog\Plugin\Feature\EventTypes;

/**
 * iThemes Security listener for DecaLog.
 *
 * Defines methods and properties for iThemes Security listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */
class ItsecListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.14.0
	 */
	protected function init() {
		$this->id      = 'itsec';
		$this->class   = 'plugin';
		$this->product = 'iThemes Security';
		$this->name    = 'iThemes Security';
		$plugin        = new Plugin( 'better-wp-security' );
		if ( $plugin->is_detected() ) {
			$this->version = $plugin->get( 'Version' );
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.14.0
	 */
	protected function is_available() {
		return class_exists( '\ITSEC_Core' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.14.0
	 */
	protected function launch() {
		add_action( 'itsec_log_add', [ $this, 'itsec_log_add' ], 10, 3 );
		return true;
	}

	/**
	 * "itsec_log_add" event.
	 *
	 * @since    1.14.0
	 */
	public function itsec_log_add( $data, $id, $log_type ) {
		if ( is_array( $data ) ) {
			$level  = 'error';
			$module = '';
			$code   = '';
			if ( array_key_exists( 'type', $data ) ) {
				$level = (string) $data['type'];
			}
			if ( array_key_exists( 'module', $data ) ) {
				$module = '[' . str_replace( '_', ' ', (string) $data['module'] ) . '] ';
			}
			if ( array_key_exists( 'code', $data ) ) {
				$code = (string) $data['code'];
				if ( false !== strpos( $code, '::' ) ) {
					$codes = explode( '::', $code );
				} else {
					$codes   = [];
					$codes[] = $code;
				}
				$code = ucfirst( str_replace( '-', ' ', $codes[0] ) );
				foreach ( $codes as $i => $c ) {
					if ( 1 === $i ) {
						$code .= ': ' . $c;
					}
					if ( 1 < $i ) {
						$code .= ' - ' . $c;
					}
				}
				$code .= '.';
			}
			$this->logger->log( EventTypes::get_standard_level( $level ), $module . $code );
		} else {
			$this->logger->alert( 'Unknown alert, no data was provided.' );
		}
	}

}
