<?php
/**
 * Class types handling
 *
 * Handles all available class types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

/**
 * Define the class types functionality.
 *
 * Handles all available class types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ClassTypes {

	/**
	 * The list of available classes.
	 *
	 * @since  1.0.0
	 * @var    array    $classes    Maintains the classes list.
	 */
	public static $classes = [ 'core', 'plugin', 'theme', 'db', 'php', 'library', 'unknown' ];

	/**
	 * The list of classes names.
	 *
	 * @since  1.0.0
	 * @var    array    $classe_names    Maintains the classes list.
	 */
	public static $classe_names = [];

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$classe_names['core']    = decalog_esc_html__( 'Core', 'decalog' );
		self::$classe_names['plugin']  = decalog_esc_html__( 'Plugin', 'decalog' );
		self::$classe_names['theme']   = decalog_esc_html__( 'Theme', 'decalog' );
		self::$classe_names['db']      = decalog_esc_html__( 'Database', 'decalog' );
		self::$classe_names['php']     = decalog_esc_html__( 'PHP', 'decalog' );
		self::$classe_names['library'] = decalog_esc_html__( 'Library', 'decalog' );
		self::$classe_names['unknown'] = decalog_esc_html__( 'Unknown', 'decalog' );

	}

}

ClassTypes::init();
