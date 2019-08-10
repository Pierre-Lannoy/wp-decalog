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

use Decalog\System\Option;
use Decalog\System\Http;

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
		// Request.
		add_action( 'plugins_loaded', [$this, 'plugins_loaded'],$max );
		add_action( 'load_textdomain', [$this, 'load_textdomain'],10, 2 );

		add_action( 'wp_loaded', [$this, 'wp_loaded'] );
		add_action( 'auth_cookie_malformed', [$this, 'auth_cookie_malformed'], 10, 2 );
		add_action( 'auth_cookie_valid', [$this, 'auth_cookie_valid'], 10, 2 );

		// Post, Page, Attachment, and Category.


		// Taxonomy and Terms.


		// Comment, Ping, and Trackback.


		// Feed.


		// Template.
		add_action( 'after_setup_theme', [$this, 'after_setup_theme'], $max );
		add_action( 'switch_theme', [$this, 'switch_theme'], 10, 3 );

		// Mail.
		add_action( 'phpmailer_init', [$this, 'phpmailer_init'], 10, 1 );
		add_action( 'wp_mail_failed', [$this, 'wp_mail_failed'], 10, 1 );

		// Administrative.
		add_action( 'added_option', [$this, 'added_option'], 10, 2 );
		add_action( 'updated_option', [$this, 'updated_option'], 10, 3 );
		add_action( 'deleted_option', [$this, 'deleted_option'], 10, 1 );
		add_action( 'delete_user', [$this, 'delete_user'], 10, 2 );
		add_action( 'wpmu_delete_user', [$this, 'wpmu_delete_user'], 10, 1 );
		add_action( 'user_register', [$this, 'user_register'], 10, 1 );
		add_action( 'wpmu_new_user', [$this, 'user_register'], 10, 1 );
		add_action( 'lostpassword_post', [$this, 'lostpassword_post'], 10, 1 );
		add_action( 'password_reset', [$this, 'password_reset'], 10, 2 );
		add_action( 'wp_logout', [$this, 'wp_logout'], 10, 0 );
		add_action( 'wp_login_failed', [$this, 'wp_login_failed'], 10, 1 );
		add_action( 'wp_login', [$this, 'wp_login'], 10, 2 );

		// Advanced.
		add_action( 'activated_plugin', [$this, 'activated_plugin'], 10, 2 );
		add_action( 'deactivated_plugin', [$this, 'deactivated_plugin'], 10, 2 );
		add_action( 'generate_rewrite_rules', [$this, 'generate_rewrite_rules'], 10, 1 );
		add_action( 'upgrader_process_complete', [$this, 'upgrader_process_complete'], 10, 2 );

		// Errors.
		add_filter( 'wp_die_ajax_handler', [$this, 'wp_die_handler'], 10, 1 );
		add_filter( 'wp_die_xmlrpc_handler', [$this, 'wp_die_handler'], 10, 1 );
		add_filter( 'wp_die_handler', [$this, 'wp_die_handler'], 10, 1 );
		add_filter( 'wp_die_json_handler', [$this, 'wp_die_handler'], 10, 1 );
		add_filter( 'wp_die_jsonp_handler', [$this, 'wp_die_handler'], 10, 1 );
		add_filter( 'wp_die_xml_handler', [$this, 'wp_die_handler'], 10, 1 );
		add_filter( 'wp', [$this, 'wp'], 10, 1 );
		add_filter( 'http_api_debug', [$this, 'http_api_debug'], 10, 5 );
		return true;
	}

	/**
	 * "plugins_loaded" event.
	 *
	 * @since    1.0.0
	 */
	public function plugins_loaded() {
		if (isset($this->logger)) {
			$this->logger->debug( 'All plugins are loaded.' );
		}
	}

	/**
	 * "load_textdomain" event.
	 *
	 * @since    1.0.0
	 */
	public function load_textdomain($domain, $mofile) {
		$mofile = './' . str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $mofile ) );
		if (isset($this->logger)) {
			$this->logger->debug( sprintf( 'Text domain "%s" loaded from %s.', $domain, $mofile ) );
		}
	}

	/**
	 * "wp_loaded" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_loaded() {
		if (isset($this->logger)) {
			$this->logger->debug( 'WordPress core, plugins and theme fully loaded and instantiated.' );
		}
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
		if (isset($this->logger)) {
			$this->logger->debug( sprintf( 'Malformed authentication cookie for "%s" scheme.', $domain, $mofile ) );
		}
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


	/**
	 * "added_option" event.
	 *
	 * @since    1.0.0
	 */
	public function added_option($option, $value) {
		$word = 'Option';
		if (0 === strpos($option, '_transient')) {
			$word = 'Transient';
		}
		if (isset($this->logger)) {
			$this->logger->debug( sprintf( '%s "%s" added.', $word, $option ) );
		}
	}

	/**
	 * "updated_option" event.
	 *
	 * @since    1.0.0
	 */
	public function updated_option($option, $old_value, $value) {
		$word = 'Option';
		if (0 === strpos($option, '_transient')) {
			$word = 'Transient';
		}
		if (isset($this->logger)) {
			$this->logger->debug( sprintf( '%s "%s" updated.', $word, $option ) );
		}
	}

	/**
	 * "deleted_option" event.
	 *
	 * @since    1.0.0
	 */
	public function deleted_option($option) {
		$word = 'Option';
		if (0 === strpos($option, '_transient')) {
			$word = 'Transient';
		}
		if (isset($this->logger)) {
			$this->logger->debug( sprintf( '%s "%s" deleted.', $word, $option ) );
		}
	}

	/**
	 * "delete_user" event.
	 *
	 * @since    1.0.0
	 */
	public function delete_user($id, $reassign) {
		if (isset($this->logger)) {
			$this->logger->notice( sprintf( 'User %s deleted.', $this->get_user($id) ) );
		}
	}

	/**
	 * "wpmu_delete_user" event.
	 *
	 * @since    1.0.0
	 */
	public function wpmu_delete_user($id) {
		if (isset($this->logger)) {
			$this->logger->notice( sprintf( 'User %s deleted.', $this->get_user($id) ) );
		}
	}

	/**
	 * "user_register" and "wpmu_new_user" events.
	 *
	 * @since    1.0.0
	 */
	public function user_register($id) {
		if (isset($this->logger)) {
			$this->logger->notice( sprintf( 'User %s created.', $this->get_user($id) ) );
		}
	}

	/**
	 * "lostpassword_post" event.
	 *
	 * @since    1.0.0
	 */
	public function lostpassword_post($errors) {
		if (isset($this->logger)) {
			if (is_wp_error( $errors )) {
				$this->logger->info( sprintf( 'Lost password form submitted with error "%s".', wp_kses($errors->get_error_message(), []) ), $errors->get_error_code() );
			} else {
				$this->logger->info( 'Lost password form submitted.' );
			}
		}
	}

	/**
	 * "password_reset" event.
	 *
	 * @since    1.0.0
	 */
	public function password_reset($user, $new_pass) {
		if ( $user instanceof WP_User ) {
			$id = $user->ID;
		} else {
			$id = 0;
		}
		if (isset($this->logger)) {
			$this->logger->info( sprintf( 'Password reset for %s.', $this->get_user($id) ) );
		}
	}

	/**
	 * "wp_logout" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_logout() {
		if (isset($this->logger)) {
			$this->logger->info( 'User is logged-out.' );
		}
	}

	/**
	 * "wp_login_failed" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_login_failed($username) {
		$name = $username;
		if (Option::get('pseudonymization')) {
			$name = 'somebody';
		}
		if (isset($this->logger)) {
			$this->logger->notice( sprintf( 'Failed login for "%s".', $username ) );
		}
	}

	/**
	 * "wp_login" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_login($user_login, $user) {
		if ( $user instanceof WP_User ) {
			$id = $user->ID;
		} else {
			$id = 0;
		}
		if (isset($this->logger)) {
			$this->logger->info( sprintf( 'User %s is logged-in.', $this->get_user($id) ) );
		}
	}







	/**
	 * "after_setup_theme" event.
	 *
	 * @since    1.0.0
	 */
	public function after_setup_theme() {
		if (isset($this->logger)) {
			$this->logger->debug( 'Theme initialized and set-up.' );
		}
	}

	/**
	 * "switch_theme" event.
	 *
	 * @since    1.0.0
	 */
	public function switch_theme($new_name, $new_theme, $old_theme) {
		if ( $old_theme instanceof \WP_Theme && $new_theme instanceof \WP_Theme ) {
			$message = sprintf('Theme switched from "%s" to "%s".', $old_theme->name, $new_theme->name);
		} else {
			$message = sprintf('Theme "%s" activated.', $new_name);
		}
		if (isset($this->logger)) {
			$this->logger->notice( $message );
		}
	}


	/**
	 * "phpmailer_init" event.
	 *
	 * @since    1.0.0
	 */
	public function phpmailer_init( $phpmailer ) {
		if ( $phpmailer instanceof \PHPMailer ) {
			$phpmailer->SMTPDebug   = 2;
			$self = $this;
			$phpmailer->Debugoutput = function ( $message ) use ( $self ){
				if (isset($self->logger)) {
					$self->logger->debug( $message );
				}
			};
		}
	}

	/**
	 * "wp_mail_failed" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_mail_failed( $error ) {
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $error ) ) {
			if (isset($this->logger)) {
				$this->logger->error( $error->get_error_message(), $error->get_error_code() );
			}
		}
	}

	/**
	 * "activated_plugin" event.
	 *
	 * @since    1.0.0
	 */
	public function activated_plugin( $plugin, $network_activation ) {
		if (isset($this->logger)) {
			if ($network_activation) {
				$this->logger->warning( sprintf( 'Plugin network activation from %s file.', $plugin ) );
			} else {
				$this->logger->warning( sprintf( 'Plugin activation from %s file.', $plugin ) );
			}
		}
	}

	/**
	 * "deactivated_plugin" event.
	 *
	 * @since    1.0.0
	 */
	public function deactivated_plugin( $plugin, $network_activation ) {
		if (isset($this->logger)) {
			if ($network_activation) {
				$this->logger->warning( sprintf( 'Plugin network deactivation for %s file.', $plugin ) );
			} else {
				$this->logger->warning( sprintf( 'Plugin deactivation for %s file.', $plugin ) );
			}
		}
	}

	/**
	 * "generate_rewrite_rules" event.
	 *
	 * @since    1.0.0
	 */
	public function generate_rewrite_rules($wp_rewrite) {
		if (isset($this->logger) && is_array($wp_rewrite)) {
			$this->logger->info( sprintf( '%s rewrite rules generated.', count($wp_rewrite) ) );
		}
	}

	/**
	 * "upgrader_process_complete" event.
	 *
	 * @since    1.0.0
	 */
	public function upgrader_process_complete($upgrader, $data) {
		$action = (array_key_exists('action', $data) ? $data['action'] : '');
		$type = (array_key_exists('type', $data) ? ucfirst($data['type']) : '');
		$plugins = '(unknown package)';
		if (array_key_exists('plugins', $data)) {
			if (is_array($data['plugins'])) {
				$plugins = 'for ' . implode (', ', $data['plugins']);
			} elseif (is_string($data['plugins'])) {
				$plugins = 'for ' . $data['plugins'];
			}
		}
		if (isset($this->logger)) {
			$this->logger->warning( sprintf( '%s %s %s.', $type, $action, $plugins ) );
		}
	}

	/**
	 * "wp_die_*" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_die_handler($handler) {
		$dberror = array_filter( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ), function ( $item ) {
			return
				isset( $item[ 'function' ] )
				&& isset( $item[ 'class' ] )
				&& ( $item[ 'function' ] === 'bail' || $item[ 'function' ] === 'print_error' )
				&& $item[ 'class' ] === 'wpdb';
		} );
		if ( ! $handler || ! is_callable( $handler ) || $dberror ) {
			return $handler;
		}
		return function ( $message, $title = '', $args = [] ) use ( $handler ) {
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
				$errors = $message->get_error_messages();
				if (is_array($errors)) {
					$errors = sprintf('WordPress error(s): %s.', implode( '", "', $errors));
				} else {
					$errors = sprintf('WordPress error: %s.', $errors);
				}
			} else {
				$errors = sprintf('WordPress error: %s.', $message);
			}
			$this->logger->critical( $errors );
			return $handler( $message, $title, $args );
		};
	}

	/**
	 * "wp" event.
	 *
	 * @since    1.0.0
	 */
	public function wp($wp) {
		if ( $wp instanceof \WP ) {
			if (isset( $wp->query_vars[ 'error' ] )) {
				$this->logger->error( $wp->query_vars[ 'error' ] );
			}
			if (is_404()) {
				$this->logger->warning( '404 Page not found', 404 );
			}
		}
	}

	/**
	 * "http_api_debug" event.
	 *
	 * @since    1.0.0
	 */
	public function http_api_debug($response, $context, $class, $request, $url) {
		$error = false;
		$code = 200;
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
			$error = true;
			$message = ucfirst($response->get_error_message()) . ': ';
			$code = $response->get_error_code();
		} elseif ( isset( $response[ 'response' ][ 'code' ] ) ) {
			$code = (int) $response[ 'response' ][ 'code' ];
			$error = ! in_array( $code, Http::$http_success_codes );
			if ( isset($response[ 'message' ]) && is_string( $response[ 'message' ] ) ) {
				$message = ucfirst($response[ 'message' ]) . ': ';
			} elseif ($error) {
				if (array_key_exists($code, Http::$http_status_codes)) {
					$message = Http::$http_status_codes[$code] . ': ';
				} else {
					$message = 'Unknown error: ';
				}

			} else {
				$message = '';
			}
		} elseif ( array_key_exists( 'blocking', $request ) && ! $request[ 'blocking' ] ) {
			$error = false;
			if ( isset($response[ 'message' ]) && is_string( $response[ 'message' ] ) ) {
				$message = ucfirst($response[ 'message' ]) . ': ';
			} else {
				$message = '';
			}
		} elseif ( ! is_numeric( $response[ 'response' ][ 'code' ] ) ) {
			$error = false;
			if ( isset($response[ 'message' ]) && is_string( $response[ 'message' ] ) ) {
				$message = ucfirst($response[ 'message' ]) . ': ';
			} else {
				$message = '';
			}
		}
		if (is_array($request)) {
			$verb = array_key_exists('method', $request) ? $request['method'] : '';
			$message .= $verb . ' ' . $url;
		}
		if ( $error ) {
			$this->logger->error( $message, $code);
		} else {
			$this->logger->debug( $message, $code );
		}
	}

}
