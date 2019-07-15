<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\Log;
use Decalog\System\Assets;
use Decalog\System\UUID;
use Decalog\System\Option;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Decalog_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * The internal logger.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    DLogger    $logger    The plugin admin logger.
	 */
	protected $logger;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
		$this->logger = Log::bootstrap('plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( DECALOG_ASSETS_ID, DECALOG_ADMIN_URL, 'css/decalog.min.css' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( DECALOG_ASSETS_ID, DECALOG_ADMIN_URL, 'js/decalog.min.js', [ 'jquery' ] );
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_submenu_page( 'options-general.php', sprintf( __( '%s Settings', 'decalog' ), DECALOG_PRODUCT_NAME ), DECALOG_PRODUCT_NAME, apply_filters( 'adr_manage_options_capability', 'manage_options' ), 'decalog-settings', array( $this, 'get_settings_page' ) );
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if (!($action = filter_input(INPUT_GET, 'action'))) {
			$action = filter_input(INPUT_POST, 'action');
		}
		if (!($tab = filter_input(INPUT_GET, 'tab'))) {
			$tab = filter_input(INPUT_POST, 'tab');
		}
		if (!($uuid = filter_input(INPUT_GET, 'uuid'))) {
			if (!($uuid = filter_input(INPUT_POST, 'uuid'))) {
				$uuid = UUID::generate_v4();
			}
		}
		$view = 'decalog-admin-settings-main';
		if ($action && $tab) {
			switch ( $tab ) {
				case 'loggers':
					switch ( $action ) {
						case 'form-edit':
							break;
						case 'form-delete':
							break;
						case 'do-edit':
							break;
						case 'do-delete':
							break;
						case 'start':
							$loggers = Option::get('loggers');
							if (array_key_exists($uuid, $loggers)) {
								$loggers[$uuid]['running'] = true;
								Option::set('loggers', $loggers);
								$message = sprintf(__('Logger %s has started.', 'decalog'), '<em>' . $loggers[$uuid]['name'] . '</em>');
								$code = 0;
								add_settings_error('decalog_no_error', $code, $message, 'updated');
								$this->logger->info(sprintf('Logger "%s" has started.', $loggers[$uuid]['name']), $code);
							}
							break;
						case 'pause':
							$loggers = Option::get('loggers');
							if (array_key_exists($uuid, $loggers)) {
								$message = sprintf(__('Logger %s has been paused.', 'decalog'), '<em>' . $loggers[$uuid]['name'] . '</em>');
								$code = 0;
								$this->logger->info(sprintf('Logger "%s" has been paused.', $loggers[$uuid]['name']), $code);
								$loggers[$uuid]['running'] = false;
								Option::set('loggers', $loggers);
								add_settings_error('decalog_no_error', $code, $message, 'updated');
							}
					}
					break;
				case 'misc':
					break;
			}
		}
		include DECALOG_ADMIN_DIR . 'partials/' . $view . '.php';
	}

}
