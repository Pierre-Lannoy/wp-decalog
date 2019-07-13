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
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		$view = 'decalog-admin-settings-main';
		include DECALOG_ADMIN_DIR . 'partials/' . $view . '.php';
	}

}
