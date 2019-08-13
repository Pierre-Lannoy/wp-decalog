<?php
/**
 * Options handling
 *
 * Handles all options operations for the plugin.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the options functionality.
 *
 * Handles all options operations for the plugin.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Option {

	/**
	 * The list of defaults options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $defaults    The $defaults list.
	 */
	private static $defaults = [];

	/**
	 * Set the defaults options.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$defaults['use_cdn']              = false;
		self::$defaults['script_in_footer']     = false;
		self::$defaults['auto_update']          = true;  // In plugin settings.
		self::$defaults['display_nag']          = true;  // In plugin settings.
		self::$defaults['nags']                 = [];
		self::$defaults['version']              = '0.0.0';
		self::$defaults['loggers']              = [];
		self::$defaults['respect_wp_debug']     = false; // In plugin settings.
		self::$defaults['logger_autostart']     = true;  // In plugin settings.
		self::$defaults['autolisteners']        = true;  // In plugin settings.
		self::$defaults['listeners']            = [];    // In plugin settings.
		self::$defaults['pseudonymization']     = false; // In plugin settings.
	}

	/**
	 * Get an option value.
	 *
	 * @param   string  $option     Option name. Expected to not be SQL-escaped.
	 * @param   boolean $default    Optional. The default value if option doesn't exists.
	 *                              This default value is used only if $option is not present
	 *                              in the $defaults array.
	 * @return  mixed   The value of the option.
	 * @since 1.0.0
	 */
	public static function get( $option, $default = null ) {
		if ( array_key_exists( $option, self::$defaults ) ) {
			$default = self::$defaults[ $option ];
		}
		return get_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, $default );
	}

	/**
	 * Set an option value.
	 *
	 * @param string      $option   Option name. Expected to not be SQL-escaped.
	 * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
	 * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
	 *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
	 *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
	 *                              the default value is 'yes'. Default null.
	 * @return boolean  False if value was not updated and true if value was updated.
	 * @since 1.0.0
	 */
	public static function set( $option, $value, $autoload = null ) {
		return update_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, $value, $autoload );
	}

	/**
	 * Delete all options.
	 *
	 * @return integer Number of deleted items.
	 * @since 1.0.0
	 */
	public static function delete_all() {
		global $wpdb;
		$result = 0;
		// phpcs:ignore
		$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '" . DECALOG_PRODUCT_ABBREVIATION . '_%' . "';" );
		foreach ( $delete as $option ) {
			if ( delete_option( $option ) ) {
				++$result;
			}
		}
		return $result;
	}

	/**
	 * Reset some options to their defaults.
	 *
	 * @since 1.0.0
	 */
	public static function reset_to_defaults() {
		self::set('use_cdn', self::$defaults['use_cdn'] ); 
		self::set('script_in_footer', self::$defaults['script_in_footer'] ); 
		self::set('auto_update', self::$defaults['auto_update'] ); 
		self::set('display_nag', self::$defaults['display_nag'] ); 
		self::set('respect_wp_debug', self::$defaults['respect_wp_debug'] ); 
		self::set('logger_autostart', self::$defaults['logger_autostart'] ); 
		self::set('autolisteners', self::$defaults['autolisteners'] ); 
		self::set('pseudonymization', self::$defaults['pseudonymization'] ); 
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}
}

Option::init();
