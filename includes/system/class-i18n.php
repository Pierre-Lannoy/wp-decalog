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

namespace WPPluginBoilerplate\System;

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
			WPPB_SLUG,
			false,
			WPPB_LANGUAGES_DIR
		);
	}

}
