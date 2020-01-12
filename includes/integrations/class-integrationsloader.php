<?php
/**
 * Integrations loader utilities.
 *
 * @package Integrations
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.9.0
 */

namespace Decalog\Integration;

/**
 * Integrations loader class.
 *
 * This class defines all code necessary to load integrations.
 *
 * @package Integrations
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.9.0
 */
class IntegrationsLoader {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.9.0
	 */
	public function __construct() {
	}

	/**
	 * Loads PSR-3 integrations.
	 *
	 * @since 1.9.0
	 */
	public function load_psr3() {
		add_filter( 'wp_optimize_loggers_classes', [ self::class, 'wp_optimize_loggers_classes' ] );
	}

	/**
	 * Adds DecaLog as source.
	 *
	 * @param  array $classes The already set classes.
	 *
	 * @return array The extended classes.
	 * @since 1.9.0
	 */
	public static function wp_optimize_loggers_classes( $classes ) {
		$classes['Decalog\Integration\OptimizeLogger'] = DECALOG_INCLUDES_DIR . 'integrations/class-optimizelogger.php';
		return $classes;
	}
}