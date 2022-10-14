<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\Plugin\Feature\Log;

/**
 * Fired after 'init' hook.
 *
 * This class defines all code necessary to run during the plugin's initialization.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Initializer {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		\Decalog\System\Cache::init();
		\Decalog\System\Sitehealth::init();
		\Decalog\System\APCu::init();
		unload_textdomain( DECALOG_SLUG );
		load_plugin_textdomain( DECALOG_SLUG );
	}

	/**
	 * Late initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function late_initialize() {
		require_once DECALOG_PLUGIN_DIR . 'perfopsone/init.php';
		$this->wpcli_initialize();
	}

	/**
	 * Initialize the plugin in wp-cli mode.
	 *
	 * @since 3.6.0
	 */
	public function wpcli_initialize() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			if ( method_exists( '\WP_CLI', 'set_logger') && method_exists( '\WP_CLI', 'get_logger') ) {
				$class = str_replace( 'WP_CLI\Loggers\\', 'Decalog\Listener\WP_CLI\\', get_class( \WP_CLI::get_logger() ) );
				if ( class_exists( $class ) ) {
					try {
						$reflection = new \ReflectionClass( $class );
						$instance   = $reflection->newInstance();
						\WP_CLI::set_logger( $instance );
					} catch ( \Exception $e ) {
						$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
						$logger->critical( sprintf( 'Unable to instanciate `%s` class. DecaLog will not log following WP-CLI events.', $class ) );
					}
				} else {
					$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
					$logger->critical( sprintf( 'Unable to find `%s` class. DecaLog will not log following WP-CLI events.', $class ) );
				}
			} else {
				$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
				$logger->warning( 'WP-CLI is outdated: DecaLog will not be able to report events triggered in interactive command-line session. Please, update WP-CLI!' );
			}
		}
	}

}
