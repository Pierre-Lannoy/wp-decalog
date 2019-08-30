<?php
/**
 * DecaLog inline help
 *
 * Handles all inline help displays.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.2.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\System\L10n;

/**
 * Define the inline help functionality.
 *
 * Handles all inline help operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.2.0
 */
class InlineHelp {

	/**
	 * The current screen.
	 *
	 * @since  1.2.0
	 * @var    WP_Screen    $screen    The current screen.
	 */
	private $screen;

	/**
	 * The current tab.
	 *
	 * @since  1.2.0
	 * @var    null|string    $tab    The current tab.
	 */
	private $tab = null;

	/**
	 * The current log id.
	 *
	 * @since  1.2.0
	 * @var    null|string    $log_id    The log id.
	 */
	private $log_id = null;

	/**
	 * The current event id.
	 *
	 * @since  1.2.0
	 * @var    null|string    $event_id    The event id.
	 */
	private $event_id = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.2.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize the screen and query properties.
	 *
	 * @since    1.2.0
	 */
	private function init() {
		$this->screen = get_current_screen();
		if ( ! ( $this->tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) ) ) {
			$this->tab = filter_input( INPUT_POST, 'tab', FILTER_SANITIZE_STRING );
		}
		if ( ! ( $this->log_id = filter_input( INPUT_GET, 'logid', FILTER_SANITIZE_STRING ) ) ) {
			$this->log_id = filter_input( INPUT_POST, 'logid', FILTER_SANITIZE_STRING );
		}
		if ( ! ( $this->event_id = filter_input( INPUT_GET, 'eventid', FILTER_SANITIZE_STRING ) ) ) {
			$this->event_id = filter_input( INPUT_POST, 'eventid', FILTER_SANITIZE_STRING );
		}
	}

	/**
	 * Displays the sidebar in inline help.
	 *
	 * @since    1.2.0
	 */
	public function set_sidebar() {
		$content  = '<p><strong>' . esc_html__( 'For more help:', 'decalog' ) . '</strong></p>';
		$content .= '<p><a href="https://wordpress.org/support/plugin/decalog/">' . esc_html__( 'User support', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$content .= '<br/><p><strong>' . __( 'See also:', 'decalog' ) . '</strong></p>';
		// $content .= '<p><a href="https://decalog.io/">' . esc_html__( 'Official website', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$content .= '<p><a href="https://github.com/Pierre-Lannoy/wp-decalog">' . esc_html__( 'GitHub repository', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$this->screen->set_help_sidebar( $content );
	}

	/**
	 * Displays inline help for loggers tab.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_settings_loggers() {
		$tabs = [];
		foreach ( $tabs as $tab ) {
			$this->screen->add_help_tab( $tab );
		}
		$this->set_sidebar();
	}

	/**
	 * Displays inline help for listeners tab.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_settings_listeners() {
		$tabs = [];
		foreach ( $tabs as $tab ) {
			$this->screen->add_help_tab( $tab );
		}
		$this->set_sidebar();
	}

	/**
	 * Displays inline help for settings pages.
	 *
	 * @since    1.2.0
	 */
	public function set_contextual_settings() {
		$this->init();
		if ( isset( $this->tab ) ) {
			switch ( strtolower( $this->tab ) ) {
				case 'loggers':
					$this->set_contextual_settings_loggers();
					break;
				case 'listeners':
					$this->set_contextual_settings_listeners();
					break;
			}
		}
	}

	/**
	 * Displays inline help for main viewer page.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_viewer_main() {
		$tabs = [];
		foreach ( $tabs as $tab ) {
			$this->screen->add_help_tab( $tab );
		}
		$this->set_sidebar();
	}

	/**
	 * Displays inline help for event screen.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_viewer_event() {
		$tabs = [];
		foreach ( $tabs as $tab ) {
			$this->screen->add_help_tab( $tab );
		}
		$this->set_sidebar();
	}

	/**
	 * Displays inline help for viewer pages.
	 *
	 * @since    1.2.0
	 */
	public function set_contextual_viewer() {
		$this->init();
		if ( isset( $this->log_id ) ) {
			if ( isset( $this->event_id ) ) {
				$this->set_contextual_viewer_event();
			} else {
				$this->set_contextual_viewer_main();
			}
		}
	}

}
