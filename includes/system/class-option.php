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

use Decalog\System\Environment;

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
	 * @var    array    $defaults    The defaults list.
	 */
	private static $defaults = [];

	/**
	 * The list of network-wide options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $network    The network-wide list.
	 */
	private static $network = [];

	/**
	 * The list of site options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $site    The site list.
	 */
	private static $site = [];

	/**
	 * The list of private options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $private    The private options list.
	 */
	private static $private = [];

	/**
	 * Set the defaults options.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$defaults['use_apcu']         = true;
		self::$defaults['use_cdn']           = false;
		self::$defaults['download_favicons'] = false;
		self::$defaults['script_in_footer']  = false;
		self::$defaults['display_nag']       = false;  // In plugin settings.
		self::$defaults['privileges']        = 0;
		self::$defaults['nags']              = [];
		self::$defaults['version']           = '0.0.0';
		self::$defaults['loggers']           = [];
		self::$defaults['livelog']           = true;
		self::$defaults['respect_wp_debug']  = false; // In plugin settings.
		self::$defaults['logger_autostart']  = true;  // In plugin settings.
		self::$defaults['autolisteners']     = true;  // In plugin settings.
		self::$defaults['listeners']         = [];    // In plugin settings.
		self::$defaults['pseudonymization']  = false; // In plugin settings.
		self::$defaults['earlyloading']      = true;  // In plugin settings.
		self::$defaults['metrics_authent']   = false;  // In plugin settings.
		self::$defaults['adminbar']          = true;
		self::$network                       = [ 'version', 'earlyloading', 'use_cdn', 'download_favicons', 'script_in_footer', 'display_nag', 'respect_wp_debug', 'livelog', 'logger_autostart', 'autolisteners', 'pseudonymization', 'privileges', 'metrics_authent', 'adminbar' ];
	}

	/**
	 * Get the options infos for Site Health "info" tab.
	 *
	 * @since 1.0.0
	 */
	public static function debug_info() {
		$result = [];
		$si     = '[Site Option] ';
		$nt     = $si;
		if ( Environment::is_wordpress_multisite() ) {
			$nt = '[Network Option] ';
		}
		foreach ( self::$network as $opt ) {
			$val            = self::network_get( $opt );
			$result[ $opt ] = [
				'label'   => $nt . $opt,
				'value'   => is_bool( $val ) ? $val ? 1 : 0 : $val,
				'private' => in_array( $opt, self::$private, true ),
			];
		}
		if ( ! (bool) self::site_get( 'autolisteners' ) ) {
			$result['listeners'] = [
				'label' => $nt . 'listeners',
				'value' => implode( ', ', self::network_get( 'listeners' ) ),
			];
		}
		foreach ( self::$site as $opt ) {
			$val            = self::site_get( $opt );
			$result[ $opt ] = [
				'label'   => $si . $opt,
				'value'   => is_bool( $val ) ? $val ? 1 : 0 : $val,
				'private' => in_array( $opt, self::$private, true ),
			];
		}
		if ( (bool) self::site_get( 'earlyloading' ) ) {
			if ( defined( 'DECALOG_EARLY_INIT' ) ) {
				$result['muplugin'] = [
					'label' => '[MU-Plugin diagnostic]',
					'value' => 'OK: loaded and operational',
				];
			} else {
				$result['muplugin'] = [
					'label' => '[MU-Plugin diagnostic]',
					'value' => 'Error: not loaded',
				];
				$a                  = [];
				foreach ( [ 'DECALOG_EARLY_INIT_WPMUDIR_ERROR', 'DECALOG_EARLY_INIT_COPY_ERROR', 'DECALOG_EARLY_INIT_ERROR' ] as $err ) {
					if ( defined( $err ) ) {
						$a[] = $err;
					}
				}
				if ( 0 < count( $a ) ) {
					$result['muplugin']['value'] .= ' ' . implode( ' ', $a );
				}
			}
			if ( defined( 'DECALOG_BOOTSTRAPPED' ) ) {
				$result['dropin'] = [
					'label' => '[Drop-In diagnostic]',
					'value' => 'OK: loaded and operational',
				];
				if ( defined( 'DECALOG_TRACEID' ) ) {
					$result['traceid'] = [
						'label' => '[traceID diagnostic]',
						'value' => 'OK: ' . DECALOG_TRACEID,
					];
				} else {
					$result['traceid'] = [
						'label' => '[traceID diagnostic]',
						'value' => 'Error: no value',
					];
				}
			} else {
				$result['dropin'] = [
					'label' => '[Drop-In diagnostic]',
					'value' => 'Error: not loaded',
				];
				$a                = [];
				foreach ( [ 'DECALOG_BOOTSTRAP_COPY_ERROR', 'DECALOG_BOOTSTRAP_ALREADY_EXISTS_ERROR' ] as $err ) {
					if ( defined( $err ) ) {
						$a[] = $err;
					}
				}
				if ( 0 < count( $a ) ) {
					$result['dropin']['value'] .= ' ' . implode( ' ', $a );
				}
			}
		}


		return $result;
	}

	/**
	 * Get an option value for a site.
	 *
	 * @param   string  $option     Option name. Expected to not be SQL-escaped.
	 * @param   boolean $default    Optional. The default value if option doesn't exists.
	 * @return  mixed   The value of the option.
	 * @since 1.0.0
	 */
	public static function site_get( $option, $default = null ) {
		if ( array_key_exists( $option, self::$defaults ) && ! isset( $default ) ) {
			$default = self::$defaults[ $option ];
		}
		$val = get_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, $default );
		if ( is_bool( $default ) ) {
			return (bool) $val;
		}
		return $val;
	}

	/**
	 * Get an option value for a network.
	 *
	 * @param   string  $option     Option name. Expected to not be SQL-escaped.
	 * @param   boolean $default    Optional. The default value if option doesn't exists.
	 * @return  mixed   The value of the option.
	 * @since 1.0.0
	 */
	public static function network_get( $option, $default = null ) {
		if ( array_key_exists( $option, self::$defaults ) && ! isset( $default ) ) {
			$default = self::$defaults[ $option ];
		}
		$val = get_site_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, $default );
		if ( is_bool( $default ) ) {
			return (bool) $val;
		}
		if ( is_bool( $default ) ) {
			return (bool) $val;
		}
		return $val;
	}

	/**
	 * Verify if an option exists.
	 *
	 * @param   string $option Option name. Expected to not be SQL-escaped.
	 * @return  boolean   True if the option exists, false otherwise.
	 * @since 1.0.0
	 */
	public static function site_exists( $option ) {
		return 'non_existent_option' !== get_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, 'non_existent_option' );
	}

	/**
	 * Verify if an option exists.
	 *
	 * @param   string $option Option name. Expected to not be SQL-escaped.
	 * @return  boolean   True if the option exists, false otherwise.
	 * @since 1.0.0
	 */
	public static function network_exists( $option ) {
		return 'non_existent_option' !== get_site_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, 'non_existent_option' );
	}

	/**
	 * Set an option value for a site.
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
	public static function site_set( $option, $value, $autoload = null ) {
		if ( false === $value ) {
			$value = 0;
		}
		return update_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, $value, $autoload );
	}

	/**
	 * Set an option value for a network.
	 *
	 * @param string $option   Option name. Expected to not be SQL-escaped.
	 * @param mixed  $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
	 * @return boolean  False if value was not updated and true if value was updated.
	 * @since 1.0.0
	 */
	public static function network_set( $option, $value ) {
		if ( false === $value ) {
			$value = 0;
		}
		if ( false === $value ) {
			$value = 0;
		}
		return update_site_option( DECALOG_PRODUCT_ABBREVIATION . '_' . $option, $value );
	}

	/**
	 * Delete all options for a site.
	 *
	 * @return integer Number of deleted items.
	 * @since 1.0.0
	 */
	public static function site_delete_all() {
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
		foreach ( self::$network as $key ) {
			if ( 'version' !== $key ) {
				self::network_set( $key, self::$defaults[ $key ] );
			}
		}
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
