<?php
/**
 * WPSEO listener for DecaLog.
 *
 * Defines class for WPSEO listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */

namespace Decalog\Listener;

use Decalog\Logger;

/**
 * WPSEO listener for DecaLog.
 *
 * Defines methods and properties for User Switching listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class WpseoListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'wpseo';
		$this->class   = 'plugin';
		$this->product = 'Yoast SEO';
		$this->name    = 'Yoast SEO';
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->version = WPSEO_VERSION;
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
		return function_exists( 'wpseo_auto_load' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.6.0
	 */
	protected function launch() {
		add_filter( 'wpseo_logger', [ $this, 'wpseo_logger' ], 10, 1 );
		return true;
	}

	/**
	 * "wpseo_logger" filter.
	 *
	 * @since    1.6.0
	 */
	public function wpseo_logger( $old_logger ) {
		$a = new Logger( $this->class, $this->name, $this->version );
		error_log('gkejh gkje fkejdfgn kvjdf bkvdjfb vkdjfb vkdjfbgbkv ejdnfvked;jfn vkdjfxcbv kdjfxcbv kdjfxcbv kdfjxcbv kdjfxcbnv kdj fncb vkd;j fncb kdjfncv peso cpzqsedk,f cpzsekdnfvoeldrjfhgn calzqekdnf kedjfnd');
		$a->warning('test');
		return $a;
	}
}
