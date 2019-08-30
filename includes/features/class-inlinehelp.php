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

use Decalog\System\Environment;
use Decalog\System\L10n;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\System\Role;

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
	private function set_sidebar() {
		$content  = '<p><strong>' . esc_html__( 'For more help:', 'decalog' ) . '</strong></p>';
		$content .= '<p><a href="https://wordpress.org/support/plugin/decalog/">' . esc_html__( 'User support', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$content .= '<br/><p><strong>' . __( 'See also:', 'decalog' ) . '</strong></p>';
		// $content .= '<p><a href="https://decalog.io/">' . esc_html__( 'Official website', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$content .= '<p><a href="https://github.com/Pierre-Lannoy/wp-decalog">' . esc_html__( 'GitHub repository', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$this->screen->set_help_sidebar( $content );
	}

	/**
	 * Get the level content.
	 *
	 * @return  string  The content to display about levels and severity.
	 * @since    1.2.0
	 */
	private function get_levels_content() {
		$content = '<p>' . sprintf( esc_html__( 'The severity of an event is indicated by a "level". %s uses the following levels classification:', 'decalog' ), DECALOG_PRODUCT_NAME ) . '</p>';
		foreach ( array_reverse( EventTypes::$level_names ) as $name ) {
			$icon     = '<img style="width:18px;float:left;padding-right:6px;" src="' . EventTypes::$icons[ strtolower( $name ) ] . '" />';
			$content .= '<p>' . $icon . '<strong>' . ucwords( strtolower( $name ) ) . '</strong> &mdash; ' . EventTypes::$level_texts[ strtolower( $name ) ] . '</p>';
		}
		return $content;
	}

	/**
	 * Get the admin rights content.
	 *
	 * @return  string  The content to display about admin rights.
	 * @since    1.2.0
	 */
	private function get_admin_rights_content() {
		$content = '';
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
		$content  = '<p>' . esc_html__( 'Because your site takes part in a sites network, admin ability to view and configure events logs differ as follows:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html_x( 'Network Admin', 'WordPress multisite', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'Can set loggers, can view all events in all WordPress events log.', 'decalog' ) . ( Role::SUPER_ADMIN === Role::admin_type() ? ' <strong><em>' . esc_html__( 'That\'s your current role.', 'decalog' ) . '</em></strong>' : '' ) . '</p>';
		$content .= '<p><strong>' . esc_html_x( 'Sites Admin', 'WordPress multisite', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'Can\'t set loggers, can only view events regarding their own sites in all WordPress events log.', 'decalog' ) . ( Role::LOCAL_ADMIN === Role::admin_type() ? ' <strong><em>' . esc_html__( 'That\'s your current role.', 'decalog' ) . '</em></strong>' : '' ) . '</p>';
		}
		return $content;
	}

	/**
	 * Displays inline help for loggers tab.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_settings_loggers() {
		$tabs = [];

		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => esc_html__( 'Admin rights', 'decalog' ),
				'id'      => 'decalog-contextual-settings-loggers-rights',
				'content' => $this->get_admin_rights_content(),
			];
		}

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

		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => esc_html__( 'Admin rights', 'decalog' ),
				'id'      => 'decalog-contextual-settings-listeners-rights',
				'content' => $this->get_admin_rights_content(),
			];
		}

		foreach ( $tabs as $tab ) {
			$this->screen->add_help_tab( $tab );
		}
		$this->set_sidebar();
	}

	/**
	 * Displays inline help for options tab.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_settings_options() {
		$tabs = [];
		// Overview.
		$content  = '<p>' . sprintf ( esc_html__( 'This screen allows you to set misc options of %s.', 'decalog' ), DECALOG_PRODUCT_NAME ) . '</p>';
		if ( Environment::is_wordpress_multisite() ) {
			$content .= '<p><em>' . esc_html__( 'Note these options are global. They are set for all loggers, for all sites in your network.', 'decalog' ) . '</em></p>';
		} else {
			$content .= '<p><em>' . esc_html__( 'Note these options are global. They are set for all loggers.', 'decalog' ) . '</em></p>';
		}
		$tabs[] = [
			'title'   => esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-main-overview',
			'content' => $content,
		];
		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => esc_html__( 'Admin rights', 'decalog' ),
				'id'      => 'decalog-contextual-settings-options-rights',
				'content' => $this->get_admin_rights_content(),
			];
		}

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
				case 'misc':
					$this->set_contextual_settings_options();
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
		// Overview.
		$content  = '<p>' . esc_html__( 'This screen displays the list of events belonging to a specific WordPress logger. This list is sorted with the most recent event at the top.', 'decalog' ) . '</p>';
		$content .= '<p>' . esc_html__( 'To move forward or backward in time, use the navigation buttons at the top or bottom right of this list.', 'decalog' ) . '</p>';
		$content .= '<p>' . esc_html__( 'You can restrict the display of events according to their severity levels. To do so, use the three links at the top left of the list.', 'decalog' ) . '</p>';
		$content .= '<p>' . esc_html__( 'You can change the events log being viewed (if you have set more than one WordPress logger) with the selector at the top left of the list (don\'t forget to click on the "apply" button).', 'decalog' ) . '</p>';
		$content .= '<p>' . esc_html__( 'To filter the displayed events, use the small blue funnel next to the filterable items. These filters are cumulative, you can activate simultaneously several filters.', 'decalog' ) . '<br/>';
		$content .= '<em>' . esc_html__( 'Note these filters are effective even on pseudonymized or obfuscated fields.', 'decalog' ) . '</em></p>';
		$tabs[]   = [
			'title'   => esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-main-overview',
			'content' => $content,
		];
		// Layout.
		$content  = '<p>' . esc_html__( 'You can use the following controls to arrange the screen to suit your usage preferences:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Screen Options', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'Use the Screen Options tab to choose which extra columns to show.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => esc_html__( 'Layout', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-main-layout',
			'content' => $content,
		];
		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => esc_html__( 'Admin rights', 'decalog' ),
				'id'      => 'decalog-contextual-viewer-main-rights',
				'content' => $this->get_admin_rights_content(),
			];
		}
		// Levels.
		$tabs[] = [
			'title'   => esc_html__( 'Events levels', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-main-levels',
			'content' => $this->get_levels_content(),
		];
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
		// Overview.
		$content  = '<p>' . esc_html__( 'This screen displays the details of a specific event.', 'decalog' ) . ' ' . esc_html__( 'It consists of four to six boxes, depending on your settings, which give specific details of the event:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Event', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'General information about the event.', 'decalog' ) . '</p>';
		$content .= '<p><strong>WordPress</strong> &mdash; ' . esc_html__( 'User and site where the event occurs.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'HTTP request', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'The detail of the request that led to this event.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'PHP introspection', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'The code location where this event was generated.', 'decalog' ) . '</p>';
		/* translators: like in the sentence "PHP backtrace" or "WordPress backtrace" */
		$content .= '<p><strong>' . sprintf( esc_html__( '%s backtrace', 'decalog' ), 'PHP' ) . ' / ' . sprintf( esc_html__( '%s backtrace', 'decalog' ), 'WordPress' ) . '</strong> &mdash; ' . esc_html__( 'Two different views of the same informations: the backtrace of the call that led to this event.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-event-overview',
			'content' => $content,
		];
		// Layout.
		$content  = '<p>' . esc_html__( 'You can use the following controls to arrange the screen to suit your usage preferences:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Screen Options', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'Use the Screen Options tab to choose which boxes to show.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Drag and Drop', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'To rearrange the boxes, drag and drop by clicking on the title bar of the selected box and releasing when you see a gray dotted-line rectangle appear in the location you want to place the box.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Box Controls', 'decalog' ) . '</strong> &mdash; ' . esc_html__( 'Click the title bar of the box to expand or collapse it.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => esc_html__( 'Layout', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-event-layout',
			'content' => $content,
		];
		// Levels.
		$tabs[] = [
			'title'   => esc_html__( 'Events levels', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-event-levels',
			'content' => $this->get_levels_content(),
		];
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
		if ( isset( $this->event_id ) ) {
			$this->set_contextual_viewer_event();
		} else {
			$this->set_contextual_viewer_main();
		}
	}

}
