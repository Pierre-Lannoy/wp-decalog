<?php
/**
 * WP database listener for DecaLog.
 *
 * Defines class for WP database listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
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
 * @since   1.3.0
 */
class HsissListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id    = 'htaccess-server-info-server-status';
		$this->class = 'plugin';
		if ( defined( 'HSISS_PRODUCT_NAME' ) ) {
			$this->product = HSISS_PRODUCT_NAME;
		} else {
			$this->product = 'HSISS';
		}
		$this->name = $this->product;
		if ( defined( 'HSISS_VERSION' ) ) {
			$this->version = HSISS_VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.3.0
	 */
	protected function is_available() {
		return defined( 'HSISS_VERSION' ) && class_exists( 'HtaccessServerInfoStatus' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.3.0
	 */
	protected function launch() {
		add_action( 'hsiss_rewrite_rules_added', [ $this, 'hsiss_rewrite_rules_added' ] );
		return true;
	}

	/**
	 * "hsiss_rewrite_rules_added" event.
	 *
	 * @since    1.3.0
	 */
	public function hsiss_rewrite_rules_added() {
		$this->logger->debug( 'Rewrite rules added.' );
	}

}
