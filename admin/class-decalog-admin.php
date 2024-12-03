<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\Plugin\Feature\Autolog;
use Decalog\Plugin\Feature\BootstrapManager;
use Decalog\System\APCu;
use Decalog\System\Blog;
use Decalog\System\SharedMemory;
use Decalog\Plugin\Feature\Log;
use Decalog\Plugin\Feature\EventViewer;
use Decalog\Plugin\Feature\HandlerTypes;
use Decalog\Plugin\Feature\ProcessorTypes;
use Decalog\Plugin\Feature\LoggerFactory;
use Decalog\Plugin\Feature\Events;
use Decalog\Plugin\Feature\Traces;
use Decalog\Plugin\Feature\InlineHelp;
use Decalog\Listener\ListenerFactory;
use Decalog\System\Assets;
use Decalog\System\UUID;
use Decalog\System\Option;
use Decalog\System\Form;
use Decalog\System\Role;
use Decalog\System\GeoIP;
use Decalog\System\Environment;
use DLMonolog\Logger;
use PerfOpsOne\Menus;
use PerfOpsOne\AdminBar;
use Decalog\Plugin\Feature\DLogger;
use Decalog\Plugin\Feature\TraceViewer;
use Decalog\System\Cache;

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
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * The internal logger.
	 *
	 * @since  1.0.0
	 * @var    DLogger    $logger    The plugin admin logger.
	 */
	protected $logger;

	/**
	 * The current logger.
	 *
	 * @since  1.0.0
	 * @var    array    $current_logger    The current logger.
	 */
	protected $current_logger;

	/**
	 * The current handler.
	 *
	 * @since  1.0.0
	 * @var    array    $current_handler    The current handler.
	 */
	protected $current_handler;

	/**
	 * The current view.
	 *
	 * @since  1.0.0
	 * @var    object    $current_view    The current view.
	 */
	protected $current_view = null;

	/**
	 * The logger will be created.
	 *
	 * @since  3.0.0
	 * @var    boolean    $creation_mode    The form mode (creation / editing).
	 */
	protected $creation_mode = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
		$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function register_styles() {
		$this->assets->register_style( DECALOG_ASSETS_ID, DECALOG_ADMIN_URL, 'css/decalog.min.css' );
		$this->assets->register_style( DECALOG_LIVELOG_ID, DECALOG_ADMIN_URL, 'css/livelog.min.css' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		$this->assets->register_script( DECALOG_ASSETS_ID, DECALOG_ADMIN_URL, 'js/decalog.min.js', [ 'jquery' ] );
		$this->assets->register_script( DECALOG_LIVELOG_ID, DECALOG_ADMIN_URL, 'js/livelog.min.js', [ 'jquery' ] );
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  2.0.0
	 */
	public function disable_wp_emojis() {
		if ( 'decalog-console' === filter_input( INPUT_GET, 'page' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}
	}

	/**
	 * Sets the help action for the settings page.
	 *
	 * @param string $hook_suffix    The hook suffix.
	 * @since 1.0.0
	 */
	public function set_settings_help( $hook_suffix ) {
		add_action( 'load-' . $hook_suffix, [ new InlineHelp(), 'set_contextual_settings' ] );
	}

	/**
	 * Sets the help action (and boxes settings) for the viewer.
	 *
	 * @param string $hook_suffix    The hook suffix.
	 * @since 1.0.0
	 */
	public function set_viewer_help( $hook_suffix ) {
		$this->current_view = null;
		add_action( 'load-' . $hook_suffix, [ new InlineHelp(), 'set_contextual_viewer' ] );
		$logid   = filter_input( INPUT_GET, 'logid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$eventid = filter_input( INPUT_GET, 'eventid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$traceid = filter_input( INPUT_GET, 'traceid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( 'decalog-viewer' === filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
			if ( isset( $logid ) && isset( $eventid ) && 0 !== $eventid ) {
				$this->current_view = new EventViewer( $logid, $eventid, $this->logger );
				add_action( 'load-' . $hook_suffix, [ $this->current_view, 'add_metaboxes_options' ] );
				add_action( 'admin_footer-' . $hook_suffix, [ $this->current_view, 'add_footer' ] );
				add_filter( 'screen_settings', [ $this->current_view, 'display_screen_settings' ], 10, 2 );
			}
		}
		if ( 'decalog-tviewer' === filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
			if ( isset( $logid ) && isset( $traceid ) && 0 !== $traceid ) {
				$this->current_view = new TraceViewer( $logid, $traceid, $this->logger );
				add_action( 'load-' . $hook_suffix, [ $this->current_view, 'add_metaboxes_options' ] );
				add_action( 'admin_footer-' . $hook_suffix, [ $this->current_view, 'add_footer' ] );
				add_filter( 'screen_settings', [ $this->current_view, 'display_screen_settings' ], 10, 2 );
			}
		}
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfopsone_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['settings'][] = [
				'name'          => DECALOG_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \Decalog\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'decalog-settings',
				/* translators: as in the sentence "DecaLog Settings" or "WordPress Settings" */
				'page_title'    => sprintf( decalog_esc_html__( '%s Settings', 'decalog' ), DECALOG_PRODUCT_NAME ),
				'menu_title'    => DECALOG_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'plugin'        => DECALOG_SLUG,
				'version'       => DECALOG_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\Decalog\System\Statistics', 'sc_get_raw' ],
				'post_callback' => [ $this, 'set_settings_help' ],
			];
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() || Role::override_privileges() ) {
			if ( Events::loggers_count() > 0 ) {
				$perfops['records'][] = [
					'name'          => decalog_esc_html__( 'Events Log', 'decalog' ),
					/* translators: as in the sentence "Check the events that occurred on your network." or "Check the events that occurred on your website." */
					'description'   => sprintf( decalog_esc_html__( 'Check the events that occurred on your %s.', 'decalog' ), Environment::is_wordpress_multisite() ? decalog_esc_html__( 'network', 'decalog' ) : decalog_esc_html__( 'website', 'decalog' ) ),
					'icon_callback' => [ \Decalog\Plugin\Core::class, 'get_base64_logo' ],
					'slug'          => 'decalog-viewer',
					/* translators: as in the sentence "DecaLog Events Viewer" */
					'page_title'    => sprintf( decalog_esc_html__( '%s Events Viewer', 'decalog' ), DECALOG_PRODUCT_NAME ),
					'menu_title'    => decalog_esc_html__( 'Events Log', 'decalog' ),
					'capability'    => 'read_private_pages',
					'callback'      => [ $this, 'get_events_page' ],
					'plugin'        => DECALOG_SLUG,
					'activated'     => true,
					'remedy'        => '',
					'post_callback' => [ $this, 'set_viewer_help' ],
				];
			}
			if ( Traces::loggers_count() > 0 ) {
				$perfops['records'][] = [
					'name'          => decalog_esc_html__( 'Traces', 'decalog' ),
					/* translators: as in the sentence "Check the traces that are recorded on your network." or "Check the traces that are recorded on your website." */
					'description'   => sprintf( decalog_esc_html__( 'Check the traces that are recorded on your %s.', 'decalog' ), Environment::is_wordpress_multisite() ? decalog_esc_html__( 'network', 'decalog' ) : decalog_esc_html__( 'website', 'decalog' ) ),
					'icon_callback' => [ \Decalog\Plugin\Core::class, 'get_base64_logo' ],
					'slug'          => 'decalog-tviewer',
					/* translators: as in the sentence "DecaLog Traces Viewer" */
					'page_title'    => sprintf( decalog_esc_html__( '%s Traces Viewer', 'decalog' ), DECALOG_PRODUCT_NAME ),
					'menu_title'    => decalog_esc_html__( 'Traces', 'decalog' ),
					'capability'    => 'read_private_pages',
					'callback'      => [ $this, 'get_traces_page' ],
					'plugin'        => DECALOG_SLUG,
					'activated'     => true,
					'remedy'        => '',
					'post_callback' => [ $this, 'set_viewer_help' ],
				];
			}
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::override_privileges() ) {
			$perfops['consoles'][] = [
				'name'          => decalog_esc_html__( 'Live Events', 'decalog' ),
				/* translators: as in the sentence "Check the events that occurred on your network." or "Check the events that occurred on your website." */
				'description'   => sprintf( decalog_esc_html__( 'Displays events as soon as they occur on your %s.', 'decalog' ), Environment::is_wordpress_multisite() ? decalog_esc_html__( 'network', 'decalog' ) : decalog_esc_html__( 'website', 'decalog' ) ),
				'icon_callback' => [ \Decalog\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'decalog-console',
				/* translators: as in the sentence "DecaLog Viewer" */
				'page_title'    => sprintf( decalog_esc_html__( '%s Live Events', 'decalog' ), DECALOG_PRODUCT_NAME ),
				'menu_title'    => decalog_esc_html__( 'Live Events', 'decalog' ),
				'capability'    => 'read_private_pages',
				'callback'      => [ $this, 'get_console_page' ],
				'plugin'        => DECALOG_SLUG,
				'activated'     => SharedMemory::$available,
				'remedy'        => esc_url( admin_url( 'admin.php?page=decalog-settings&tab=misc' ) ),
			];
		}
		return $perfops;
	}

	/**
	 * Init PerfOps admin bar.
	 *
	 * @param array $perfops    The already declared items.
	 * @return array    The completed items array.
	 * @since 3.2.0
	 */
	public function init_perfopsone_admin_bar( $perfops ) {
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		$early_signal  = ( 'misc' === $tab && 'do-save' === $action ) && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() );
		$early_signal &= ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) );
		$early_signal &= ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-plugin-options' ) );
		if ( $early_signal ) {
			Option::network_set( 'adminbar', array_key_exists( 'decalog_plugin_options_adminbar', $_POST ) );
		}
		if ( Option::network_get( 'adminbar' ) && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() || Role::override_privileges() ) ) {
			if ( Events::loggers_count() > 0 ) {
				$perfops[] = [
					'id'    => 'decalog-events-viewer',
					'title' => '<strong>' .decalog__( 'Logs', 'decalog' ) . '</strong>&nbsp;&nbsp;➜&nbsp;&nbsp;' .decalog__( 'View Events', 'decalog' ),
					'href'  => esc_url( admin_url( 'admin.php?page=decalog-viewer' . ( Environment::is_wordpress_multisite() ? '&site_id=' . Blog::get_current_blog_id() : '' ) ) ),
					'meta'  => false,
				];
			}
			if ( Traces::loggers_count() > 0 ) {
				$perfops[] = [
					'id'    => 'decalog-traces-viewer',
					'title' => '<strong>' .decalog__( 'Logs', 'decalog' ) . '</strong>&nbsp;&nbsp;➜&nbsp;&nbsp;' .decalog__( 'View Traces', 'decalog' ),
					'href'  => esc_url( admin_url( 'admin.php?page=decalog-tviewer' . ( Environment::is_wordpress_multisite() ? '&site_id=' . Blog::get_current_blog_id() : '' ) ) ),
					'meta'  => false,
				];
			}
		}
		return $perfops;
	}

	/**
	 * Dispatch the items in the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function finalize_admin_menus() {
		Menus::finalize();
	}

	/**
	 * Removes unneeded items from the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function normalize_admin_menus() {
		Menus::normalize();
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		if ( 'decalog-settings' === filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
		}
		add_filter( 'init_perfopsone_admin_menus', [ $this, 'init_perfopsone_admin_menus' ] );
		add_filter( 'init_perfopsone_admin_bar', [ $this, 'init_perfopsone_admin_bar' ] );
		Menus::initialize();
		AdminBar::initialize();
	}

	/**
	 * Get actions links for myblogs_blog_actions hook.
	 *
	 * @param string $actions   The HTML site link markup.
	 * @param object $user_blog An object containing the site data.
	 * @return string   The action string.
	 * @since 1.2.0
	 */
	public function blog_action( $actions, $user_blog ) {
		if ( Role::override_privileges() || Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			if ( Events::loggers_count() > 0 ) {
				$actions .= " | <a href='" . esc_url( admin_url( 'admin.php?page=decalog-viewer&site_id=' . $user_blog->userblog_id ) ) . "'>" .decalog__( 'Events', 'decalog' ) . '</a>';
			}
			if ( Traces::loggers_count() > 0 ) {
				$actions .= " | <a href='" . esc_url( admin_url( 'admin.php?page=decalog-tviewer&site_id=' . $user_blog->userblog_id ) ) . "'>" .decalog__( 'Traces', 'decalog' ) . '</a>';
			}
		}
		return $actions;
	}

	/**
	 * Get actions for manage_sites_action_links hook.
	 *
	 * @param string[] $actions  An array of action links to be displayed.
	 * @param int      $blog_id  The site ID.
	 * @param string   $blogname Site path, formatted depending on whether it is a sub-domain
	 *                           or subdirectory multisite installation.
	 * @return array   The actions.
	 * @since 1.2.0
	 */
	public function site_action( $actions, $blog_id, $blogname ) {
		if ( Role::override_privileges() || Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			if ( Events::loggers_count() > 0 ) {
				$actions['events_log'] = "<a href='" . esc_url( admin_url( 'admin.php?page=decalog-viewer&site_id=' . $blog_id ) ) . "' rel='bookmark'>" .decalog__( 'Events', 'decalog' ) . '</a>';
			}
			if ( Traces::loggers_count() > 0 ) {
				$actions['traces_log'] = "<a href='" . esc_url( admin_url( 'admin.php?page=decalog-tviewer&site_id=' . $blog_id ) ) . "' rel='bookmark'>" .decalog__( 'traces', 'decalog' ) . '</a>';
			}
		}
		return $actions;
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'decalog_loggers_options_section', decalog_esc_html__( 'Loggers options', 'decalog' ), [ $this, 'loggers_options_section_callback' ], 'decalog_loggers_options_section' );
		add_settings_section( 'decalog_plugin_features_section', decalog_esc_html__( 'Plugin features', 'decalog' ), [ $this, 'plugin_features_section_callback' ], 'decalog_plugin_features_section' );
		add_settings_section( 'decalog_plugin_options_section', decalog_esc_html__( 'Plugin options', 'decalog' ), [ $this, 'plugin_options_section_callback' ], 'decalog_plugin_options_section' );
		add_settings_section( 'decalog_listeners_options_section', null, [ $this, 'listeners_options_section_callback' ], 'decalog_listeners_options_section' );
		add_settings_section( 'decalog_listeners_settings_section', null, [ $this, 'listeners_settings_section_callback' ], 'decalog_listeners_settings_section' );
		add_settings_section( 'decalog_logger_misc_section', null, [ $this, 'logger_misc_section_callback' ], 'decalog_logger_misc_section' );
		add_settings_section( 'decalog_logger_delete_section', null, [ $this, 'logger_delete_section_callback' ], 'decalog_logger_delete_section' );
		add_settings_section( 'decalog_logger_specific_section', null, [ $this, 'logger_specific_section_callback' ], 'decalog_logger_specific_section' );
		add_settings_section( 'decalog_logger_privacy_section', decalog_esc_html__( 'Privacy options', 'decalog' ), [ $this, 'logger_privacy_section_callback' ], 'decalog_logger_privacy_section' );
		add_settings_section( 'decalog_logger_details_section', decalog_esc_html__( 'Reported details', 'decalog' ), [ $this, 'logger_details_section_callback' ], 'decalog_logger_details_section' );
		if ( apply_filters( 'perfopsone_show_advanced', false ) ) {
			add_settings_section( 'decalog_plugin_advanced_section', decalog_esc_html__( 'Plugin advanced options', 'decalog' ), [ $this, 'plugin_advanced_section_callback' ], 'decalog_plugin_advanced_section' );
		}
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=decalog-settings' ), decalog_esc_html__( 'Settings', 'decalog' ) );
		if ( Events::loggers_count() > 0 ) {
			$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=decalog-viewer' ), decalog_esc_html__( 'Events', 'decalog' ) );
		}
		if ( Traces::loggers_count() > 0 ) {
			$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=decalog-tviewer' ), decalog_esc_html__( 'Traces', 'decalog' ) );
		}
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the Plugins page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.3.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, DECALOG_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . DECALOG_SLUG . '/">' .decalog__( 'Support', 'decalog' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the events page.
	 *
	 * @since 1.0.0
	 */
	public function get_events_page() {
		if ( isset( $this->current_view ) ) {
			$this->current_view->get();
		} else {
			include DECALOG_ADMIN_DIR . 'partials/decalog-admin-view-events.php';
		}
	}

	/**
	 * Get the content of the traces page.
	 *
	 * @since 1.0.0
	 */
	public function get_traces_page() {
		if ( isset( $this->current_view ) ) {
			wp_enqueue_style( DECALOG_ASSETS_ID );
			$this->current_view->get();
		} else {
			include DECALOG_ADMIN_DIR . 'partials/decalog-admin-view-traces.php';
		}
	}

	/**
	 * Get the content of the console page.
	 *
	 * @since 1.0.0
	 */
	public function get_console_page() {
		if ( isset( $this->current_view ) ) {
			$this->current_view->get();
		} else {
			include DECALOG_ADMIN_DIR . 'partials/decalog-admin-view-console.php';
		}
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		$this->current_handler = null;
		$this->current_logger  = null;
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $handler = filter_input( INPUT_GET, 'handler' ) ) ) {
			$handler = filter_input( INPUT_POST, 'handler' );
		}
		if ( ! ( $uuid = filter_input( INPUT_GET, 'uuid' ) ) ) {
			$uuid = filter_input( INPUT_POST, 'uuid' );
		}
		$nonce = filter_input( INPUT_GET, 'nonce' );
		if ( $uuid ) {
			$loggers = Option::network_get( 'loggers' );
			if ( array_key_exists( $uuid, $loggers ) ) {
				$this->current_logger         = $loggers[ $uuid ];
				$this->current_logger['uuid'] = $uuid;
			}
		}
		if ( $handler ) {
			$handlers              = new HandlerTypes();
			$this->current_handler = $handlers->get( $handler );
		} elseif ( $this->current_logger ) {
			$handlers              = new HandlerTypes();
			$this->current_handler = $handlers->get( $this->current_logger['handler'] );
		}
		if ( $this->current_handler && ! $this->current_logger ) {
			$this->creation_mode  = true;
			$this->current_logger = [
				'uuid'    => $uuid = UUID::generate_v4(),
				'name'    => decalog_esc_html__( 'New logger', 'decalog' ),
				'handler' => $this->current_handler['id'],
				'running' => Option::network_get( 'logger_autostart' ),
				'level'   => Logger::INFO,
			];
		}
		if ( $this->current_logger ) {
			$factory              = new LoggerFactory();
			$this->current_logger = $factory->check( $this->current_logger );
		}
		$view = 'decalog-admin-settings-main';
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'loggers':
					switch ( $action ) {
						case 'form-edit':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$current_logger  = $this->current_logger;
								$current_handler = $this->current_handler;
								$args            = compact( 'current_logger', 'current_handler' );
								$view            = 'decalog-admin-settings-logger-edit';
							}
							if ( 'system' === $current_handler['class'] ) {
								$view    = 'decalog-admin-settings-main';
								$message = decalog_esc_html__( 'You can not modify or remove a system logger.', 'decalog' );
								$code    = 403;
								$this->logger->error( $message, $code );
								add_settings_error( 'decalog_error', $code, $message, 'error' );
							}
							break;
						case 'form-delete':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$current_logger  = $this->current_logger;
								$current_handler = $this->current_handler;
								$args            = compact( 'current_logger', 'current_handler' );
								$view            = 'decalog-admin-settings-logger-delete';
							}
							if ( 'system' === $current_handler['class'] ) {
								$view    = 'decalog-admin-settings-main';
								$message = decalog_esc_html__( 'You can not modify or remove a system logger.', 'decalog' );
								$code    = 403;
								$this->logger->error( $message, $code );
								add_settings_error( 'decalog_error', $code, $message, 'error' );
							}
							break;
						case 'do-edit':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$this->save_current();
							}
							break;
						case 'do-delete':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$this->delete_current();
							}
							break;
						case 'start':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( $nonce && $uuid && wp_verify_nonce( $nonce, 'decalog-logger-start-' . $uuid ) ) {
									$loggers = Option::network_get( 'loggers' );
									if ( array_key_exists( $uuid, $loggers ) && 'system' !== $this->current_handler['class'] ) {
										$loggers[ $uuid ]['running'] = true;
										Option::network_set( 'loggers', $loggers );
										$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
										$message      = sprintf( decalog_esc_html__( 'Logger %s has started.', 'decalog' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
										$code         = 0;
										add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
										$this->logger->info( sprintf( 'Logger "%s" has started.', $loggers[ $uuid ]['name'] ), $code );
									} else {
										$message = decalog_esc_html__( 'You can not start or pause a system logger.', 'decalog' );
										$code    = 403;
										$this->logger->error( $message, $code );
										add_settings_error( 'decalog_error', $code, $message, 'error' );
									}
								}
							}
							break;
						case 'pause':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( $nonce && $uuid && wp_verify_nonce( $nonce, 'decalog-logger-pause-' . $uuid ) ) {
									$loggers = Option::network_get( 'loggers' );
									if ( array_key_exists( $uuid, $loggers ) && 'system' !== $this->current_handler['class'] ) {
										$message = sprintf( decalog_esc_html__( 'Logger %s has been paused.', 'decalog' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
										$code    = 0;
										$this->logger->notice( sprintf( 'Logger "%s" has been paused.', $loggers[ $uuid ]['name'] ), $code );
										$loggers[ $uuid ]['running'] = false;
										Option::network_set( 'loggers', $loggers );
										$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
										add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
									}
									else {
										$message = decalog_esc_html__( 'You can not start or pause a system logger.', 'decalog' );
										$code    = 403;
										$this->logger->error( $message, $code );
										add_settings_error( 'decalog_error', $code, $message, 'error' );
									}
								}
							}
						case 'test':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( $nonce && $uuid && wp_verify_nonce( $nonce, 'decalog-logger-test-' . $uuid ) ) {
									$loggers = Option::network_get( 'loggers' );
									if ( array_key_exists( $uuid, $loggers ) ) {
										$test = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION, $uuid );
										$done = true;
										foreach ( [ 'DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' ] as $level ) {
											$done &= $test->log( $level, ucfirst( strtolower( $level ) ) . ' test message.', 210871 );
										}
										if ( $done ) {
											$message = sprintf( decalog_esc_html__( 'Test messages have been sent to logger %s.', 'decalog' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
											$code    = 0;
											$this->logger->info( sprintf( 'Logger "%s" has been tested.', $loggers[ $uuid ]['name'] ), $code );
											add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
										} else {
											$message = sprintf( decalog_esc_html__( 'Test messages have not been sent to logger %s. Please check the logger\'s settings.', 'decalog' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
											$code    = 1;
											$this->logger->warning( sprintf( 'Logger "%s" has been unsuccessfully tested.', $loggers[ $uuid ]['name'] ), $code );
											add_settings_error( 'decalog_error', $code, $message, 'error' );
										}
									}
								}
							}
					}
					break;
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
						case 'install-podd':
							if ( class_exists( 'PerfOpsOne\Installer' ) && $nonce && wp_verify_nonce( $nonce, $action ) ) {
								$result = \PerfOpsOne\Installer::do( 'device-detector', true );
								if ( '' === $result ) {
									add_settings_error( 'decalog_no_error', '', decalog_esc_html__( 'Plugin successfully installed and activated with default settings.', 'decalog' ), 'info' );
								} else {
									add_settings_error( 'decalog_install_error', '', sprintf( decalog_esc_html__( 'Unable to install or activate the plugin. Error message: %s.', 'decalog' ), $result ), 'error' );
								}
							}
							break;
						case 'install-iplocator':
							if ( class_exists( 'PerfOpsOne\Installer' ) && $nonce && wp_verify_nonce( $nonce, $action ) ) {
								$result = \PerfOpsOne\Installer::do( 'ip-locator', true );
								if ( '' === $result ) {
									add_settings_error( 'decalog_no_error', '', decalog_esc_html__( 'Plugin successfully installed and activated with default settings.', 'decalog' ), 'info' );
								} else {
									add_settings_error( 'decalog_install_error', '', sprintf( decalog_esc_html__( 'Unable to install or activate the plugin. Error message: %s.', 'decalog' ), $result ), 'error' );
								}
							}
							break;
					}
					break;
				case 'listeners':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_listeners();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_listeners();
								}
							}
							break;
					}
					break;
			}
		}
		include DECALOG_ADMIN_DIR . 'partials/' . $view . '.php';
	}

	/**
	 * Save the listeners options.
	 *
	 * @since 1.0.0
	 */
	private function save_listeners() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-listeners-options' ) ) {
				Option::network_set( 'autolisteners', 'auto' === filter_input( INPUT_POST, 'decalog_listeners_options_auto',FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
				$list      = [];
				$listeners = ListenerFactory::$infos;
				foreach ( $listeners as $listener ) {
					if ( array_key_exists( 'decalog_listeners_settings_' . $listener['id'], $_POST ) ) {
						$list[] = $listener['id'];
					}
				}
				Option::network_set( 'listeners', $list );
				$message = decalog_esc_html__( 'Listeners settings have been saved.', 'decalog' );
				$code    = 0;
				add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
				$this->logger->info( 'Listeners settings updated.', $code );
			} else {
				$message = decalog_esc_html__( 'Listeners settings have not been saved. Please try again.', 'decalog' );
				$code    = 2;
				add_settings_error( 'decalog_nonce_error', $code, $message, 'error' );
				$this->logger->warning( 'Listeners settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the listeners options.
	 *
	 * @since 1.0.0
	 */
	private function reset_listeners() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-listeners-options' ) ) {
				Option::network_set( 'autolisteners', true );
				$message = decalog_esc_html__( 'Listeners settings have been reset to defaults.', 'decalog' );
				$code    = 0;
				add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
				$this->logger->info( 'Listeners settings reset to defaults.', $code );
			} else {
				$message = decalog_esc_html__( 'Listeners settings have not been reset to defaults. Please try again.', 'decalog' );
				$code    = 2;
				add_settings_error( 'decalog_nonce_error', $code, $message, 'error' );
				$this->logger->warning( 'Listeners settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'decalog_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_options_usecdn' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'decalog_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_options_nag' ) : false );
				Option::network_set( 'adminbar', array_key_exists( 'decalog_plugin_options_adminbar', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_options_adminbar' ) : false );
				Option::network_set( 'download_favicons', array_key_exists( 'decalog_plugin_options_favicons', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_options_favicons' ) : false );
				Option::network_set( 'earlyloading', array_key_exists( 'decalog_plugin_features_earlyloading', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_features_earlyloading' ) : false );
				Option::network_set( 'logger_autostart', array_key_exists( 'decalog_loggers_options_autostart', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_loggers_options_autostart' ) : false );
				Option::network_set( 'pseudonymization', array_key_exists( 'decalog_loggers_options_pseudonymization', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_loggers_options_pseudonymization' ) : false );
				Option::network_set( 'respect_wp_debug', array_key_exists( 'decalog_loggers_options_wpdebug', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_loggers_options_wpdebug' ) : false );
				Option::network_set( 'env_substitution', array_key_exists( 'decalog_loggers_options_env_substitution', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_loggers_options_env_substitution' ) : false );
				Option::network_set( 'privileges', array_key_exists( 'decalog_plugin_options_privileges', $_POST ) ? (string) filter_input( INPUT_POST, 'decalog_plugin_options_privileges', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'privileges' ) );
				Option::network_set( 'metrics_authent', array_key_exists( 'decalog_plugin_features_metrics_authent', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_features_metrics_authent' ) : false );
				Option::network_set( 'slow_query_warn', array_key_exists( 'decalog_plugin_features_slowqueries', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_features_slowqueries' ) : false );
				Option::network_set( 'unknown_metrics_warn', array_key_exists( 'decalog_plugin_features_unknownmetrics', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_features_unknownmetrics' ) : false );
				Option::network_set( 'trace_query', array_key_exists( 'decalog_plugin_features_tracequeries', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_features_tracequeries' ) : false );

				Option::network_set( 'unbuffered_cli', array_key_exists( 'decalog_plugin_advanced_unbuffered_cli', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_advanced_unbuffered_cli' ) : false );
				Option::network_set( 'buffer_size', array_key_exists( 'decalog_plugin_advanced_buffer_size', $_POST ) ? (string) filter_input( INPUT_POST, 'decalog_plugin_advanced_buffer_size', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'buffer_size' ) );

				$autolog = array_key_exists( 'decalog_plugin_features_livelog', $_POST ) ? (bool) filter_input( INPUT_POST, 'decalog_plugin_features_livelog' ) : false;
				if ( $autolog ) {
					Autolog::activate();
				} else {
					Autolog::deactivate();
				}
				$message = decalog_esc_html__( 'Plugin settings have been saved.', 'decalog' );
				$code    = 0;
				add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
				$this->logger->info( 'Plugin settings updated.', $code );
			} else {
				$message = decalog_esc_html__( 'Plugin settings have not been saved. Please try again.', 'decalog' );
				$code    = 2;
				add_settings_error( 'decalog_nonce_error', $code, $message, 'error' );
				$this->logger->warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = decalog_esc_html__( 'Plugin settings have been reset to defaults.', 'decalog' );
				$code    = 0;
				add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
				$this->logger->info( 'Plugin settings reset to defaults.', $code );
			} else {
				$message = decalog_esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'decalog' );
				$code    = 2;
				add_settings_error( 'decalog_nonce_error', $code, $message, 'error' );
				$this->logger->warning( 'Plugin settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Save the current logger as new or modified logger.
	 *
	 * @since 1.0.0
	 */
	private function save_current() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-logger-edit' ) ) {
				if ( array_key_exists( 'submit', $_POST ) ) {
					$this->current_logger['name']                        = ( array_key_exists( 'decalog_logger_misc_name', $_POST ) ? filter_input( INPUT_POST, 'decalog_logger_misc_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : $this->current_logger['name'] );
					$this->current_logger['level']                       = ( array_key_exists( 'decalog_logger_misc_level', $_POST ) ? filter_input( INPUT_POST, 'decalog_logger_misc_level', FILTER_SANITIZE_NUMBER_INT ) : $this->current_logger['level'] );
					$this->current_logger['privacy']['obfuscation']      = ( array_key_exists( 'decalog_logger_privacy_ip', $_POST ) ? true : false );
					$this->current_logger['privacy']['pseudonymization'] = ( array_key_exists( 'decalog_logger_privacy_name', $_POST ) ? true : false );
					$this->current_logger['processors']                  = [];
					$proc = new ProcessorTypes();
					foreach ( array_reverse( $proc->get_all() ) as $processor ) {
						if ( array_key_exists( 'decalog_logger_details_' . strtolower( $processor['id'] ), $_POST ) ) {
							$this->current_logger['processors'][] = $processor['id'];
						}
					}
					foreach ( $this->current_handler['configuration'] as $key => $configuration ) {
						$id = 'decalog_logger_details_' . strtolower( $key );
						if ( 'boolean' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? true : false );
						}
						if ( 'integer' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? filter_input( INPUT_POST, $id, FILTER_SANITIZE_NUMBER_INT ) : $this->current_logger['configuration'][ $key ] );
						}
						if ( 'string' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? filter_input( INPUT_POST, $id, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : $this->current_logger['configuration'][ $key ] );
						}
						if ( 'password' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? filter_input( INPUT_POST, $id, FILTER_UNSAFE_RAW ) : $this->current_logger['configuration'][ $key ] );
						}
					}
					$uuid             = $this->current_logger['uuid'];
					$loggers          = Option::network_get( 'loggers' );
					$factory          = new LoggerFactory();
					$loggers[ $uuid ] = $factory->check( $this->current_logger, true );
					if ( array_key_exists( 'uuid', $loggers[ $uuid ] ) ) {
						unset( $loggers[ $uuid ]['uuid'] );
					}
					Option::network_set( 'loggers', $loggers );
					$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
					$message      = sprintf( decalog_esc_html__( 'Logger %s has been saved.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
					$code         = 0;
					add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
					$this->logger->info( sprintf( 'Logger "%s" has been saved.', $this->current_logger['name'] ), $code );
				}
			} else {
				$message = sprintf( decalog_esc_html__( 'Logger %s has not been saved. Please try again.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
				$code    = 2;
				add_settings_error( 'decalog_nonce_error', $code, $message, 'error' );
				$this->logger->warning( sprintf( 'Logger "%s" has not been saved.', $this->current_logger['name'] ), $code );
			}
		}
	}

	/**
	 * Delete the current logger.
	 *
	 * @since 1.0.0
	 */
	private function delete_current() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-logger-delete' ) ) {
				if ( array_key_exists( 'submit', $_POST ) ) {
					$uuid    = $this->current_logger['uuid'];
					$loggers = Option::network_get( 'loggers' );
					$factory = new LoggerFactory();
					$factory->destroy( $this->current_logger );
					unset( $loggers[ $uuid ] );
					Option::network_set( 'loggers', $loggers );
					$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
					$message      = sprintf( decalog_esc_html__( 'Logger %s has been removed.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
					$code         = 0;
					add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
					$this->logger->notice( sprintf( 'Logger "%s" has been removed.', $this->current_logger['name'] ), $code );
				}
			} else {
				$message = sprintf( decalog_esc_html__( 'Logger %s has not been removed. Please try again.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
				$code    = 2;
				add_settings_error( 'decalog_nonce_error', $code, $message, 'error' );
				$this->logger->warning( sprintf( 'Logger "%s" has not been removed.', $this->current_logger['name'] ), $code );
			}
		}
	}

	/**
	 * Callback for listeners options section.
	 *
	 * @since 1.0.0
	 */
	public function listeners_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'decalog_listeners_options_auto',
			__( 'Activate', 'decalog' ),
			[ $form, 'echo_field_select' ],
			'decalog_listeners_options_section',
			'decalog_listeners_options_section',
			[
				'list'        => [
					0 => [ 'manual', decalog_esc_html__( 'Selected listeners', 'decalog' ) ],
					1 => [ 'auto', decalog_esc_html__( 'All available listeners (recommended)', 'decalog' ) ],
				],
				'id'          => 'decalog_listeners_options_auto',
				'value'       => Option::network_get( 'autolisteners' ) ? 'auto' : 'manual',
				'description' => decalog_esc_html__( 'Automatically or selectively choose which sources to listen.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_listeners_options_section', 'decalog_listeners_options_autostart' );
	}

	/**
	 * Callback for listeners settings section.
	 *
	 * @since 1.0.0
	 */
	public function listeners_settings_section_callback() {
		$standard  = [];
		$plugin    = [];
		$theme     = [];
		$library   = [];
		$listeners = ListenerFactory::$infos;
		usort(
			$listeners,
			function( $a, $b ) {
				return strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
			}
		);
		foreach ( $listeners as $listener ) {
			if ( 'plugin' === $listener['class'] && $listener['available'] ) {
				$plugin[] = $listener;
			} elseif ( 'theme' === $listener['class'] && $listener['available'] ) {
				$theme[] = $listener;
			} elseif ( 'library' === $listener['class'] && $listener['available'] ) {
				$library[] = $listener;
			} elseif ( $listener['available'] ) {
				$standard[] = $listener;
			}
		}
		$main = [
			decalog_esc_html__( 'Standard listeners', 'decalog' ) => $standard,
			decalog_esc_html__( 'Plugin listeners', 'decalog' )   => $plugin,
			decalog_esc_html__( 'Library listeners', 'decalog' )  => $library,
			decalog_esc_html__( 'Theme listeners', 'decalog' )    => $theme,
		];
		$form = new Form();
		foreach ( $main as $name => $items ) {
			$title = true;
			foreach ( $items as $item ) {
				add_settings_field(
					'decalog_listeners_settings_' . $item['id'],
					$title ? $name : null,
					[ $form, 'echo_field_checkbox' ],
					'decalog_listeners_settings_section',
					'decalog_listeners_settings_section',
					[
						'text'        => sprintf( '%s (%s %s)', $item['name'], $item['product'], $item['version'] ),
						'id'          => 'decalog_listeners_settings_' . $item['id'],
						'checked'     => in_array( $item['id'], Option::network_get( 'listeners' ), true ),
						'description' => null,
						'full_width'  => false,
						'enabled'     => true,
					]
				);
				register_setting( 'decalog_listeners_settings_section', 'decalog_listeners_settings_' . $item['id'] );
				$title = false;
			}
		}
	}

	/**
	 * Callback for loggers options section.
	 *
	 * @since 1.0.0
	 */
	public function loggers_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'decalog_loggers_options_autostart',
			__( 'New logger', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_loggers_options_section',
			'decalog_loggers_options_section',
			[
				'text'        => decalog_esc_html__( 'Auto-start', 'decalog' ),
				'id'          => 'decalog_loggers_options_autostart',
				'checked'     => Option::network_get( 'logger_autostart' ),
				'description' => decalog_esc_html__( 'If checked, when a new logger is added it automatically starts.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_loggers_options_section', 'decalog_loggers_options_autostart' );
		add_settings_field(
			'decalog_loggers_options_pseudonymization',
			__( 'Events messages', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_loggers_options_section',
			'decalog_loggers_options_section',
			[
				'text'        => decalog_esc_html__( 'Respect privacy', 'decalog' ),
				'id'          => 'decalog_loggers_options_pseudonymization',
				'checked'     => Option::network_get( 'pseudonymization' ),
				'description' => decalog_esc_html__( 'If checked, DecaLog will try to obfuscate personal information in events messages.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_loggers_options_section', 'decalog_loggers_options_pseudonymization' );
		add_settings_field(
			'decalog_loggers_options_wpdebug',
			__( 'Rules', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_loggers_options_section',
			'decalog_loggers_options_section',
			[
				'text'        => decalog_esc_html__( 'Respect WP_DEBUG', 'decalog' ),
				'id'          => 'decalog_loggers_options_wpdebug',
				'checked'     => Option::network_get( 'respect_wp_debug' ),
				'description' => decalog_esc_html__( 'If checked, the value of WP_DEBUG will override each logger\'s settings for minimal level of logging.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_loggers_options_section', 'decalog_loggers_options_wpdebug' );
		add_settings_field(
			'decalog_loggers_options_env_substitution',
			__( 'Parameters', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_loggers_options_section',
			'decalog_loggers_options_section',
			[
				'text'        => decalog_esc_html__( 'Variables substitution', 'decalog' ),
				'id'          => 'decalog_loggers_options_env_substitution',
				'checked'     => Option::network_get( 'env_substitution' ),
				'description' => decalog_esc_html__( 'If checked, DecaLog will replace strings between curly braces by the corresponding environment variables or PHP-defined constants.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_loggers_options_section', 'decalog_loggers_options_env_substitution' );
	}

	/**
	 * Get the available privileges overriding.
	 *
	 * @return array An array containing the privileges overriding.
	 * @since  2.4.0
	 */
	protected function get_privileges_array() {
		$result   = [];
		$result[] = [ 0, decalog_esc_html__( 'Never override privileges', 'decalog' ) ];
		$result[] = [ 1, decalog_esc_html__( 'Override privileges for development environments', 'decalog' ) ];
		$result[] = [ 2, decalog_esc_html__( 'Override privileges for staging environments', 'decalog' ) ];
		$result[] = [ 3, decalog_esc_html__( 'Override privileges for staging and development environments', 'decalog' ) ];
		return $result;
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		if ( function_exists( 'wp_get_environment_type' ) ) {
			add_settings_field(
				'decalog_plugin_options_privileges',
				decalog_esc_html__( 'Logs accesses', 'decalog' ),
				[ $form, 'echo_field_select' ],
				'decalog_plugin_options_section',
				'decalog_plugin_options_section',
				[
					'list'        => $this->get_privileges_array(),
					'id'          => 'decalog_plugin_options_privileges',
					'value'       => Option::network_get( 'privileges' ),
					'description' => decalog_esc_html__( 'Allows other users than administrators to access live console and local events logs depending of environments.', 'decalog' ) . '<br/>' . decalog_esc_html__( 'Note: choosing something other than "Never override privileges" grants access to all users having "read_private_pages" capability and may have privacy and security implications.', 'decalog' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_privileges' );
		}
		add_settings_field(
			'decalog_plugin_options_adminbar',
			__( 'Quick actions', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text'        => decalog_esc_html__( 'Display in admin bar', 'decalog' ),
				'id'          => 'decalog_plugin_options_adminbar',
				'checked'     => Option::network_get( 'adminbar' ),
				'description' => decalog_esc_html__( 'If checked, DecaLog will display in admin bar the most important actions, if any.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_adminbar' );
		add_settings_field(
			'decalog_plugin_options_favicons',
			__( 'Favicons', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text'        => decalog_esc_html__( 'Download and display', 'decalog' ),
				'id'          => 'decalog_plugin_options_favicons',
				'checked'     => Option::network_get( 'download_favicons' ),
				'description' => decalog_esc_html__( 'If checked, DecaLog will download favicons of websites to display them in reports.', 'decalog' ) . '<br/>' . decalog_esc_html__( 'Note: This feature uses the (free) Google Favicon Service.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_favicons' );
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( decalog_esc_html__('Your site is currently using %s.', 'decalog' ), '<em>Device Detector v' . PODD_VERSION .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( decalog_esc_html__('Your site does not use any device detection mechanism. To handle user-agents and callers reporting in DecaLog, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'decalog' ), '<a href="https://wordpress.org/plugins/device-detector/">Device Detector</a>' );
			if ( class_exists( 'PerfOpsOne\Installer' ) && ! Environment::is_wordpress_multisite() ) {
				$help .= '<br/><a href="' . wp_nonce_url( admin_url( 'admin.php?page=decalog-settings&tab=misc&action=install-podd' ), 'install-podd', 'nonce' ) . '" class="poo-button-install"><img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'download-cloud', 'none', '#FFFFFF', 3 ) . '" />&nbsp;&nbsp;' . decalog_esc_html__('Install It Now', 'decalog' ) . '</a>';
			}
		}
		add_settings_field(
			'decalog_plugin_options_podd',
			__( 'Device Detection', 'decalog' ),
			[ $form, 'echo_field_simple_text' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_podd' );
		$geo_ip = new GeoIP();
		if ( $geo_ip->is_installed() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( decalog_esc_html__('Your site is currently using %s.', 'decalog' ), '<em>' . $geo_ip->get_full_name() .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( decalog_esc_html__('Your site does not use any IP geographic information plugin. To display callers geographical details in DecaLog, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'decalog' ), '<a href="https://wordpress.org/plugins/ip-locator/">IP Locator</a>' );
			if ( class_exists( 'PerfOpsOne\Installer' ) && ! Environment::is_wordpress_multisite() ) {
				$help .= '<br/><a href="' . wp_nonce_url( admin_url( 'admin.php?page=decalog-settings&tab=misc&action=install-iplocator' ), 'install-iplocator', 'nonce' ) . '" class="poo-button-install"><img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'download-cloud', 'none', '#FFFFFF', 3 ) . '" />&nbsp;&nbsp;' . decalog_esc_html__('Install It Now', 'decalog' ) . '</a>';
			}
		}
		add_settings_field(
			'decalog_plugin_options_geoip',
			__( 'IP information', 'decalog' ),
			[ $form, 'echo_field_simple_text' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_geoip' );
		if ( SharedMemory::$available ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= decalog_esc_html__('Shared memory is available on your server: you can use live console.', 'decalog' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( decalog_esc_html__('Shared memory is not available on your server. To use live console you must activate %s PHP module.', 'decalog' ), '<code>shmop</code>' );
		}
		add_settings_field(
			'decalog_plugin_options_shmop',
			__( 'Shared memory', 'decalog' ),
			[ $form, 'echo_field_simple_text' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text' => $help
			]
		);
		if ( Cache::$apcu_available) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= decalog_esc_html__('APCu is available on your server: you can use high peformance storage mechanism.', 'decalog' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( decalog_esc_html__('APCu is not available on your server. To use high peformance storage mechanism you must activate %s PHP module.', 'decalog' ), '<code>apcu</code>' );
		}
		add_settings_field(
			'decalog_plugin_options_apcu',
			__( 'APCu storage', 'decalog' ),
			[ $form, 'echo_field_simple_text' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_shmop' );
		add_settings_field(
			'decalog_plugin_options_usecdn',
			__( 'Resources', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text'        => decalog_esc_html__( 'Use public CDN', 'decalog' ),
				'id'          => 'decalog_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => decalog_esc_html__( 'Use CDN (jsDelivr) to serve DecaLog scripts and stylesheets.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_usecdn' );
		add_settings_field(
			'decalog_plugin_options_nag',
			__( 'Admin notices', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_options_section',
			'decalog_plugin_options_section',
			[
				'text'        => decalog_esc_html__( 'Display', 'decalog' ),
				'id'          => 'decalog_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => decalog_esc_html__( 'Allows DecaLog to display admin notices throughout the admin dashboard.', 'decalog' ) . '<br/>' . decalog_esc_html__( 'Note: DecaLog respects DISABLE_NAG_NOTICES flag.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_options_section', 'decalog_plugin_options_nag' );
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$form = new Form();
		add_settings_field(
			'decalog_plugin_features_earlyloading',
			__( 'Initialization', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_features_section',
			'decalog_plugin_features_section',
			[
				'text'        => decalog_esc_html__( 'Activate early loading', 'decalog' ),
				'id'          => 'decalog_plugin_features_earlyloading',
				'checked'     => ( 3 === \decalog_get_psr_log_version() ) ? Option::network_get( 'earlyloading' ) : false,
				'description' => ( 3 === \decalog_get_psr_log_version() ) ? decalog_esc_html__( 'If checked, DecaLog will be loaded before all other plugins (recommended).', 'decalog' ) : decalog_esc_html__( 'Option not available.', 'decalog' ) . ' ' . sprintf( decalog_esc_html__( '%s is running in PSR-3 v1 compatibility mode due to an obsolete or outdated third-party plugin or theme.', 'decalog' ), DECALOG_PRODUCT_NAME ) . '<br/>' . sprintf(decalog__( 'Please, do not hesitate to <a href="%s">take part in discussion</a>.', 'decalog' ), 'https://github.com/Pierre-Lannoy/wp-decalog/discussions/63' ),
				'full_width'  => false,
				'enabled'     => ( 3 === \decalog_get_psr_log_version() ),
			]
		);
		register_setting( 'decalog_plugin_features_section', 'decalog_plugin_features_earlyloading' );
		if ( SharedMemory::$available ) {
			add_settings_field(
				'decalog_plugin_features_livelog',
				__( 'Live console', 'decalog' ),
				[ $form, 'echo_field_checkbox' ],
				'decalog_plugin_features_section',
				'decalog_plugin_features_section',
				[
					'text'        => decalog_esc_html__( 'Activate auto-logging', 'decalog' ),
					'id'          => 'decalog_plugin_features_livelog',
					'checked'     => Autolog::is_enabled(),
					'description' => decalog_esc_html__( 'If checked, DecaLog will silently start the features needed by live console.', 'decalog' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'decalog_plugin_features_section', 'decalog_plugin_features_livelog' );
		}
		add_settings_field(
			'decalog_plugin_features_slowqueries',
			__( 'Database', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_features_section',
			'decalog_plugin_features_section',
			[
				'text'        => decalog_esc_html__( 'Warn about slow queries', 'decalog' ),
				'id'          => 'decalog_plugin_features_slowqueries',
				'checked'     => Option::network_get( 'slow_query_warn' ),
				'description' => sprintf( decalog_esc_html__( 'If checked, a warning will be triggered for each SQL query that takes %.1F ms or more to execute.', 'decalog' ), Option::network_get( 'slow_query_ms', 50 ) ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_features_section', 'decalog_plugin_features_slowqueries' );
		add_settings_field(
			'decalog_plugin_features_tracequeries',
			'',
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_features_section',
			'decalog_plugin_features_section',
			[
				'text'        => decalog_esc_html__( 'Trace queries', 'decalog' ),
				'id'          => 'decalog_plugin_features_tracequeries',
				'checked'     => Option::network_get( 'trace_query' ),
				'description' => decalog_esc_html__( 'If checked, all SQL queries will be traced and pushed to running trace loggers.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_features_section', 'decalog_plugin_features_tracequeries' );
		add_settings_field(
			'decalog_plugin_features_unknownmetrics',
			__( 'Metrics', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_features_section',
			'decalog_plugin_features_section',
			[
				'text'        => decalog_esc_html__( 'Warn about non-existent metrics', 'decalog' ),
				'id'          => 'decalog_plugin_features_unknownmetrics',
				'checked'     => Option::network_get( 'unknown_metrics_warn' ),
				'description' => decalog_esc_html__( 'If checked, an error will be triggered when a process try to set a value for a metric which is not defined.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_features_section', 'decalog_plugin_features_unknownmetrics' );
		add_settings_field(
			'decalog_plugin_features_metrics_authent',
			'',
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_features_section',
			'decalog_plugin_features_section',
			[
				'text'        => decalog_esc_html__( 'Authenticated endpoint', 'decalog' ),
				'id'          => 'decalog_plugin_features_metrics_authent',
				'checked'     => Option::network_get( 'metrics_authent' ),
				'description' => sprintf( decalog_esc_html__( 'If checked, DecaLog will require authentication to serve %s calls.', 'decalog' ), '<code>' . htmlentities( '/wp-json/' . DECALOG_REST_NAMESPACE . '/metrics' ) . '</code>' ) . '<br/>' . sprintf( decalog_esc_html__( 'Note: if you activate authentication, you must generate an application password for a user having %s capability.', 'decalog' ), '<code>read_private_pages</code>' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_features_section', 'decalog_plugin_features_metrics_authent' );
	}

	/**
	 * Callback for plugin advanced section.
	 *
	 * @since 4.3.0
	 */
	public function plugin_advanced_section_callback() {
		$form = new Form();
		add_settings_field(
			'decalog_plugin_advanced_unbuffered_cli',
			'Force unbuffering',
			[ $form, 'echo_field_checkbox' ],
			'decalog_plugin_advanced_section',
			'decalog_plugin_advanced_section',
			[
				'text'        => 'WP CLI events',
				'id'          => 'decalog_plugin_advanced_unbuffered_cli',
				'checked'     => Option::network_get( 'unbuffered_cli' ),
				'description' => 'If checked, events triggered via WP CLI will not be buffered.',
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_advanced_section', 'decalog_plugin_advanced_unbuffered_cli' );
		add_settings_field(
			'decalog_plugin_advanced_buffer_size',
			'Events buffer size',
			[ $form, 'echo_field_input_integer' ],
			'decalog_plugin_advanced_section',
			'decalog_plugin_advanced_section',
			[
				'id'          => 'decalog_plugin_advanced_buffer_size',
				'value'       => Option::network_get( 'buffer_size' ),
				'min'         => 10,
				'max'         => 2000,
				'step'        => 10,
				'description' => 'Buffer size, in number of events.',
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_plugin_advanced_section', 'decalog_plugin_advanced_buffer_size' );
	}
	

	/**
	 * Callback for logger misc section.
	 *
	 * @since 1.0.0
	 */
	public function logger_misc_section_callback() {
		$icon  = '<img style="vertical-align:middle;width:34px;margin-top: -2px;padding-right:6px;" src="' . $this->current_handler['icon'] . '" />';
		$title = $this->current_handler['name'];
		echo '<h2>' . $icon . '&nbsp;' . $title . '</h2>';
		echo '<p style="margin-top: -10px;margin-left: 6px;">' . $this->current_handler['help'] . '</p>';
		$form = new Form();
		add_settings_field(
			'decalog_logger_misc_name',
			__( 'Name', 'decalog' ),
			[ $form, 'echo_field_input_text' ],
			'decalog_logger_misc_section',
			'decalog_logger_misc_section',
			[
				'id'          => 'decalog_logger_misc_name',
				'value'       => $this->current_logger['name'],
				'description' => decalog_esc_html__( 'Used only in admin dashboard.', 'decalog' ),
				'full_width'  => false,
				'placeholder' => '',
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_logger_misc_section', 'decalog_logger_misc_name' );
		if ( in_array( $this->current_handler['class'], [ 'alerting', 'logging', 'debugging', 'analytics' ], true ) ) {
			add_settings_field(
				'decalog_logger_misc_level',
				__( 'Minimal level', 'decalog' ),
				[ $form, 'echo_field_select' ],
				'decalog_logger_misc_section',
				'decalog_logger_misc_section',
				[
					'list'        => Log::get_levels( $this->current_handler['minimal'], true ),
					'id'          => 'decalog_logger_misc_level',
					'value'       => $this->current_logger['level'],
					'description' => decalog_esc_html__( 'Minimal reported level. May be overridden by the "respect WP_DEBUG directive" option.', 'decalog' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'decalog_logger_misc_section', 'decalog_logger_misc_level' );
		}
	}

	/**
	 * Callback for logger delete section.
	 *
	 * @since 1.0.0
	 */
	public function logger_delete_section_callback() {
		$icon  = '<img style="vertical-align:middle;width:34px;margin-top: -2px;padding-right:6px;" src="' . $this->current_handler['icon'] . '" />';
		$title = $this->current_handler['name'];
		echo '<h2>' . $icon . '&nbsp;' . $title . '</h2>';
		echo '<p style="margin-top: -10px;margin-left: 6px;">' . $this->current_handler['help'] . '</p>';
		$form = new Form();
		add_settings_field(
			'decalog_logger_delete_name',
			__( 'Name', 'decalog' ),
			[ $form, 'echo_field_input_text' ],
			'decalog_logger_delete_section',
			'decalog_logger_delete_section',
			[
				'id'          => 'decalog_logger_delete_name',
				'value'       => $this->current_logger['name'],
				'description' => null,
				'full_width'  => false,
				'placeholder' => '',
				'enabled'     => false,
			]
		);
		register_setting( 'decalog_logger_delete_section', 'decalog_logger_delete_name' );
		if ( in_array( $this->current_handler['class'], [ 'alerting', 'logging', 'debugging', 'analytics' ], true ) ) {
			add_settings_field(
				'decalog_logger_delete_level',
				__( 'Minimal level', 'decalog' ),
				[ $form, 'echo_field_select' ],
				'decalog_logger_delete_section',
				'decalog_logger_delete_section',
				[
					'list'        => Log::get_levels( $this->current_handler['minimal'] ),
					'id'          => 'decalog_logger_delete_level',
					'value'       => $this->current_logger['level'],
					'description' => null,
					'full_width'  => false,
					'enabled'     => false,
				]
			);
			register_setting( 'decalog_logger_delete_section', 'decalog_logger_delete_level' );
		}
	}

	/**
	 * Callback for logger specific section.
	 *
	 * @since 1.0.0
	 */
	public function logger_specific_section_callback() {
		$form = new Form();
		if ( 'ErrorLogHandler' === $this->current_logger['handler'] ) {
			add_settings_field(
				'decalog_logger_specific_dummy',
				__( 'Log file', 'decalog' ),
				[ $form, 'echo_field_input_text' ],
				'decalog_logger_specific_section',
				'decalog_logger_specific_section',
				[
					'id'          => 'decalog_logger_specific_dummy',
					'value'       => ini_get( 'error_log' ),
					'description' => decalog_esc_html__( 'Value set in php.ini file.', 'decalog' ),
					'full_width'  => false,
					'placeholder' => '',
					'enabled'     => false,
				]
			);
			register_setting( 'decalog_logger_specific_section', 'decalog_logger_specific_dummy' );
		}
		foreach ( $this->current_handler['configuration'] as $key => $configuration ) {
			if ( ! $configuration['show'] ) {
				continue;
			}
			$id   = 'decalog_logger_details_' . strtolower( $key );
			$args = [
				'id'          => $id,
				'text'        => decalog_esc_html__( 'Enabled', 'decalog' ),
				'checked'     => (bool) $this->current_logger['configuration'][ $key ],
				'value'       => $this->current_logger['configuration'][ $key ],
				'description' => $configuration['help'],
				'full_width'  => false,
				'enabled'     => $configuration['control']['enabled'],
				'placeholder' => $configuration['default'],
				'list'        => ( array_key_exists( 'list', $configuration['control'] ) ? $configuration['control']['list'] : [] ),
			];
			foreach ( $configuration['control'] as $index => $control ) {
				if ( 'type' !== $index && 'cast' !== $index ) {
					if ( 0 === strpos( $key, 'constant-' ) && ! $this->creation_mode && 'enabled' === $index ) {
						$control = false;
					}
					$args[ $index ] = $control;
				}
			}
			add_settings_field(
				$id,
				$configuration['name'],
				[ $form, 'echo_' . $configuration['control']['type'] ],
				'decalog_logger_specific_section',
				'decalog_logger_specific_section',
				$args
			);
			register_setting( 'decalog_logger_specific_section', $id );
		}
	}

	/**
	 * Callback for logger privacy section.
	 *
	 * @since 1.0.0
	 */
	public function logger_privacy_section_callback() {
		$form = new Form();
		add_settings_field(
			'decalog_logger_privacy_ip',
			__( 'Remote IPs', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_logger_privacy_section',
			'decalog_logger_privacy_section',
			[
				'text'        => decalog_esc_html__( 'Obfuscation', 'decalog' ),
				'id'          => 'decalog_logger_privacy_ip',
				'checked'     => $this->current_logger['privacy']['obfuscation'],
				'description' => decalog_esc_html__( 'If checked, logged details will contain hashes instead of real IPs.', 'decalog' ) . '<br/>' . decalog_esc_html__( 'Note: it concerns everything except events messages.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_logger_privacy_section', 'decalog_logger_privacy_ip' );
		add_settings_field(
			'decalog_logger_privacy_name',
			__( 'Users', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_logger_privacy_section',
			'decalog_logger_privacy_section',
			[
				'text'        => decalog_esc_html__( 'Pseudonymisation', 'decalog' ),
				'id'          => 'decalog_logger_privacy_name',
				'checked'     => $this->current_logger['privacy']['pseudonymization'],
				'description' => decalog_esc_html__( 'If checked, logged details will contain hashes instead of user IDs & names.', 'decalog' ) . '<br/>' . decalog_esc_html__( 'Note: it concerns everything except events messages.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_logger_privacy_section', 'decalog_logger_privacy_name' );
	}

	/**
	 * Callback for logger privacy section.
	 *
	 * @since 1.0.0
	 */
	public function logger_details_section_callback() {
		$form = new Form();
		$id   = 'decalog_logger_details_dummy';
		add_settings_field(
			$id,
			__( 'Standard', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_logger_details_section',
			'decalog_logger_details_section',
			[
				'text'        => decalog_esc_html__( 'Included', 'decalog' ),
				'id'          => $id,
				'checked'     => true,
				'description' => decalog_esc_html__( 'Allows to log standard DecaLog information.', 'decalog' ),
				'full_width'  => false,
				'enabled'     => false,
			]
		);
		register_setting( 'decalog_logger_details_section', $id );
		$proc = new ProcessorTypes( 'tracing' === $this->current_handler['class'] );
		foreach ( array_reverse( $proc->get_all() ) as $processor ) {
			$id = 'decalog_logger_details_' . strtolower( $processor['id'] );
			add_settings_field(
				$id,
				$processor['name'],
				[ $form, 'echo_field_checkbox' ],
				'decalog_logger_details_section',
				'decalog_logger_details_section',
				[
					'text'        => decalog_esc_html__( 'Included', 'decalog' ),
					'id'          => $id,
					'checked'     => in_array( $processor['id'], $this->current_logger['processors'], true ),
					'description' => $processor['help'],
					'full_width'  => false,
					'enabled'     => ! in_array( $processor['id'], array_merge( $this->current_handler['processors']['excluded'] ?? [], $this->current_handler['processors']['included'] ?? [] ), true ),
				]
			);
			register_setting( 'decalog_logger_details_section', $id );
		}
	}

}
