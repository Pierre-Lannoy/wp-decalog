<?php
/**
 * Internationalization handling
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class I18n {

	/**
	 * Verification of internationalization extension.
	 *
	 * @return  boolean True if Intl PHP extension is loaded, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_extension_loaded() {
		return ( class_exists( 'Locale' ) && class_exists( 'DateTimeZone' ) );
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			DECALOG_SLUG,
			false,
			DECALOG_LANGUAGES_DIR
		);
	}

}
