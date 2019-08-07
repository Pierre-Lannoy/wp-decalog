<?php

/**
 * WP core listener for DecaLog.
 *
 * Defines class for WP core listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

/**
 * WP core listener for DecaLog.
 *
 * Defines methods and properties for WP core listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class CoreListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		global $wp_version;
		$this->id = 'wpcore';
		$this->name = esc_html__('WordPress core', 'decalog');
		$this->class = 'core';
		$this->product = 'WordPress';
		$this->version = $wp_version;
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.0.0
	 */
	protected function is_needed() {
		return true;
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	protected function launch() {
		$max = 999999999;
		//add_action( 'plugins_loaded', [$this, 'plugins_loaded'],$max );
		//add_action( 'load_textdomain', [$this, 'load_textdomain'],10, 2 );
		//add_action( 'after_setup_theme', [$this, 'after_setup_theme'], $max );
		//add_action( 'wp_loaded', [$this, 'wp_loaded'] );
		//add_action( 'auth_cookie_malformed', [$this, 'auth_cookie_malformed'], 10, 2 );
		add_action( 'auth_cookie_valid', [$this, 'auth_cookie_valid'], 10, 2 );

		return true;
	}

	/**
	 * "plugins_loaded" event.
	 *
	 * @since    1.0.0
	 */
	public function plugins_loaded() {
		$this->logger->debug( 'All plugins are loaded.' );
	}

	/**
	 * "load_textdomain" event.
	 *
	 * @since    1.0.0
	 */
	public function load_textdomain($domain, $mofile) {
		$mofile = './' . str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $mofile ) );
		$this->logger->debug( sprintf( 'Text domain "%s" loaded from %s.', $domain, $mofile ) );
	}

	/**
	 * "wp_loaded" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_loaded() {
		$this->logger->debug( 'WordPress core, plugins and theme fully loaded and instantiated.');
	}

	/**
	 * "after_setup_theme" event.
	 *
	 * @since    1.0.0
	 */
	public function after_setup_theme() {
		$this->logger->debug( 'Theme initialized and set-up.');
	}

	/**
	 * "auth_cookie_malformed" event.
	 *
	 * @since    1.0.0
	 */
	public function auth_cookie_malformed($cookie, $scheme) {
		if (!$scheme || !is_string($scheme)) {
			$scheme = '<none>';
		}
		$this->logger->debug( sprintf( 'Malformed authentication cookie for "%s" scheme.', $domain, $mofile ) );
	}

	/**
	 * "auth_cookie_valid" event.
	 *
	 * @since    1.0.0
	 */
	public function auth_cookie_valid($cookie, $user) {
		if (isset($this->logger)) {
			$this->logger->debug( sprintf( 'Validated authentication cookie for %s.', $this->get_user($user->ID) ) );
		}
	}



}
