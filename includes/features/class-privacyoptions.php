<?php
/**
 * Privacy options handling
 *
 * Handles all available privacy options.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.5.0
 */

namespace Decalog\Plugin\Feature;

/**
 * Define the privacy options functionality.
 *
 * Handles all available privacy options.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.5.0
 */
class PrivacyOptions {

	/**
	 * The list of available options.
	 *
	 * @since  3.5.0
	 * @var    array    $options    Maintains the options list.
	 */
	public static $options = [ 'obfuscation', 'pseudonymization' ];

	/**
	 * The list of options names.
	 *
	 * @since  3.5.0
	 * @var    array    $options_names    Maintains the options names list.
	 */
	public static $options_names = [];

	/**
	 * The list of options icons.
	 *
	 * @since  3.5.0
	 * @var    array    $options_icons    Maintains the options icons list.
	 */
	public static $options_icons = [];

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    3.5.0
	 */
	public static function init() {
		self::$options_names['obfuscation']       = esc_html__( 'Obfuscation', 'decalog' );
		self::$options_names['pseudonymization']  = esc_html__( 'Pseudonymization', 'decalog' );
		self::$options_icons['obfuscation']       = 'eye';
		self::$options_icons['pseudonymization']  = 'user';
	}

}

PrivacyOptions::init();
