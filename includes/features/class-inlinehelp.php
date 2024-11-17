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
use Decalog\System\Markdown;
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
	 * The current trace id.
	 *
	 * @since  3.0.0
	 * @var    null|string    $trace_id    The trace id.
	 */
	private $trace_id = null;

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
		if ( ! ( $this->tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
			$this->tab = filter_input( INPUT_POST, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		if ( ! ( $this->log_id = filter_input( INPUT_GET, 'logid', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
			$this->log_id = filter_input( INPUT_POST, 'logid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		if ( ! ( $this->event_id = filter_input( INPUT_GET, 'eventid', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
			$this->event_id = filter_input( INPUT_POST, 'eventid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		if ( ! ( $this->trace_id = filter_input( INPUT_GET, 'traceid', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
			$this->trace_id = filter_input( INPUT_POST, 'traceid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
	}

	/**
	 * Displays the sidebar in inline help.
	 *
	 * @since    1.2.0
	 */
	private function set_sidebar() {
		$content  = '<p><strong>' . decalog_esc_html__( 'For more help:', 'decalog' ) . '</strong></p>';
		$content .= '<p><a href="https://wordpress.org/support/plugin/decalog/">' . decalog_esc_html__( 'User support', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$content .= '<br/><p><strong>' .decalog__( 'See also:', 'decalog' ) . '</strong></p>';
		$content .= '<p><a href="https://perfops.one/">' . decalog_esc_html__( 'Official website', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$content .= '<p><a href="https://github.com/Pierre-Lannoy/wp-decalog">' . decalog_esc_html__( 'GitHub repository', 'decalog' ) . '</a>' . L10n::get_language_markup( [ 'en' ] ) . '</p>';
		$this->screen->set_help_sidebar( $content );
	}

	/**
	 * Get the level content.
	 *
	 * @return  string  The content to display about levels and severity.
	 * @since    1.2.0
	 */
	private function get_levels_content() {
		$content = '<p>' . sprintf( decalog_esc_html__( 'The severity of an event is indicated by a "level". %s uses the following levels classification:', 'decalog' ), DECALOG_PRODUCT_NAME ) . '</p>';
		foreach ( array_reverse( EventTypes::$level_names ) as $name ) {
			$icon     = '<img style="width:18px;float:left;padding-right:6px;" src="' . EventTypes::$icons[ strtolower( $name ) ] . '" />';
			$content .= '<p>' . $icon . '<strong>' . ucwords( strtolower( $name ) ) . '</strong> &mdash; ' . EventTypes::$level_texts[ strtolower( $name ) ] . '</p>';
		}
		return $content;
	}

	/**
	 * Get the loggers of a specific class.
	 *
	 * @param   string $class  The class of loggers ( 'alerting', 'debugging', 'logging').
	 * @return  string  The content to display about this class of loggers.
	 * @since    1.2.0
	 */
	private function get_loggers( $class ) {
		$handlers = new HandlerTypes();
		$content  = '';
		foreach ( $handlers->get_for_class( $class ) as $handler ) {
			$icon     = '<img style="width:18px;float:left;padding-right:6px;" src="' . $handler['icon'] . '" />';
			$content .= '<p>' . $icon . '<strong>' . $handler['name'] . '</strong> &mdash; ' . $handler['help'] . '</p>';
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
		$content  = '';
		$content  = '<p>' . decalog_esc_html__( 'Because your site takes part in a sites network, admin ability to view and configure logs differ as follows:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . esc_html_x( 'Network Admin', 'WordPress multisite', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Can set loggers, can view all items in all WordPress logs.', 'decalog' ) . ( Role::SUPER_ADMIN === Role::admin_type() ? ' <strong><em>' . decalog_esc_html__( 'That\'s your current role.', 'decalog' ) . '</em></strong>' : '' ) . '</p>';
		$content .= '<p><strong>' . esc_html_x( 'Sites Admin', 'WordPress multisite', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Can\'t set loggers, can only view items regarding their own sites in all authorized WordPress logs.', 'decalog' ) . ( Role::LOCAL_ADMIN === Role::admin_type() ? ' <strong><em>' . decalog_esc_html__( 'That\'s your current role.', 'decalog' ) . '</em></strong>' : '' ) . '</p>';
		return $content;
	}

	/**
	 * Displays inline help for loggers tab.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_settings_loggers() {
		$tabs = [];
		// Overview.
		$content  = '<p>' . sprintf( decalog_esc_html__( 'This screen allows you to set the %s loggers.', 'decalog' ), DECALOG_PRODUCT_NAME ) . '</p>';
		$content .= '<p>' . decalog_esc_html__( 'A logger is a recorder of events, traces or metrics. It can filter them (accept or refuse to record them based on settings) then store them or send them to external services.', 'decalog' );
		$content .= ' ' . decalog_esc_html__( 'You can set as many loggers as you want. All the set loggers will receive all events, traces or metrics (depending of their types) and, regarding their own settings, will enrich them and record them or not.', 'decalog' ) . '</p>';
		$content .= '<p>' . decalog_esc_html__( 'Loggers are classified in six main categories: alerting, debugging, crash analytics, events logging, monitoring and tracing. You can find details on these categories on the corresponding tabs of this help.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => decalog_esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-overview',
			'content' => $content,
		];
		// Alerting.
		$content = '<p>' . decalog_esc_html__( 'These loggers allow you to send event-based alerts:', 'decalog' ) . '</p>';
		$tabs[]  = [
			'title'   => decalog_esc_html__( 'Alerting', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-alerting',
			'content' => $content . $this->get_loggers( 'alerting' ),
		];
		// Debugging.
		$content = '<p>' . decalog_esc_html__( 'These loggers can help you to debug your site:', 'decalog' ) . '</p>';
		$tabs[]  = [
			'title'   => decalog_esc_html__( 'Debugging', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-debugging',
			'content' => $content . $this->get_loggers( 'debugging' ),
		];
		// Crash.
		$content = '<p>' . decalog_esc_html__( 'These loggers capture crashes and send reports for crash analytics purpose:', 'decalog' ) . '</p>';
		$tabs[]  = [
			'title'   => decalog_esc_html__( 'Crash Analytics', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-analytics',
			'content' => $content . $this->get_loggers( 'analytics' ),
		];
		// Logging.
		$content = '<p>' . decalog_esc_html__( 'These loggers capture events for logging purpose:', 'decalog' ) . '</p>';
		$tabs[]  = [
			'title'   => decalog_esc_html__( 'Events Logging', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-logging',
			'content' => $content . $this->get_loggers( 'logging' ),
		];
		// Monitoring.
		$content = '<p>' . decalog_esc_html__( 'These loggers collect metrics', 'decalog' ) . '</p>';
		$tabs[]  = [
			'title'   => decalog_esc_html__( 'Monitoring', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-monitoring',
			'content' => $content . $this->get_loggers( 'metrics' ),
		];
		// Tracing.
		$content = '<p>' . decalog_esc_html__( 'These loggers capture traces:', 'decalog' ) . '</p>';
		$tabs[]  = [
			'title'   => decalog_esc_html__( 'Tracing', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-tracing',
			'content' => $content . $this->get_loggers( 'tracing' ),
		];

		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => decalog_esc_html__( 'Admin rights', 'decalog' ),
				'id'      => 'decalog-contextual-settings-loggers-rights',
				'content' => $this->get_admin_rights_content(),
			];
		}
		// Levels.
		$tabs[] = [
			'title'   => decalog_esc_html__( 'Events levels', 'decalog' ),
			'id'      => 'decalog-contextual-settings-loggers-levels',
			'content' => $this->get_levels_content(),
		];
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
		// Overview.
		$content  = '<p>' . sprintf( decalog_esc_html__( 'This screen allows you to set the way %s uses listeners.', 'decalog' ), DECALOG_PRODUCT_NAME ) . '</p>';
		$content .= '<p>' . decalog_esc_html__( 'A listener, as its name suggests, listen to a specific component (a "source") of your WordPress instance.', 'decalog' );
		$content .= ' ' . sprintf( decalog_esc_html__( 'You can choose to tell %s to activate all the available listeners, or you can manually select the sources to listen.', 'decalog' ), DECALOG_PRODUCT_NAME ) . '</p>';
		$tabs[]   = [
			'title'   => decalog_esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-settings-listeners-overview',
			'content' => $content,
		];
		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => decalog_esc_html__( 'Admin rights', 'decalog' ),
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
		$content = '<p>' . sprintf( decalog_esc_html__( 'This screen allows you to set misc options of %s.', 'decalog' ), DECALOG_PRODUCT_NAME ) . '</p>';
		if ( Environment::is_wordpress_multisite() ) {
			$content .= '<p><em>' . decalog_esc_html__( 'Note these options are global. They are set for all loggers, for all sites in your network.', 'decalog' ) . '</em></p>';
		} else {
			$content .= '<p><em>' . decalog_esc_html__( 'Note these options are global. They are set for all loggers.', 'decalog' ) . '</em></p>';
		}
		$tabs[] = [
			'title'   => decalog_esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-settings-options-overview',
			'content' => $content,
		];
		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => decalog_esc_html__( 'Admin rights', 'decalog' ),
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
		if ( ! isset( $this->tab ) ) {
			$this->set_contextual_settings_loggers();
			return;
		}
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

	/**
	 * Displays inline help for main viewer page.
	 *
	 * @since    1.2.0
	 */
	private function set_contextual_viewer_main() {
		$tabs = [];
		// Overview.
		if ( false !== strpos( $this->screen->id, 'page_decalog-viewer' ) ) {
			$content  = '<p>' . decalog_esc_html__( 'This screen displays the list of events belonging to a specific WordPress logger. This list is sorted with the most recent event at the top.', 'decalog' ) . '</p>';
			$content .= '<p>' . decalog_esc_html__( 'To move forward or backward in time, use the navigation buttons at the top or bottom right of this list.', 'decalog' ) . '</p>';
			$content .= '<p>' . decalog_esc_html__( 'You can restrict the display of events according to their severity levels. To do so, use the three links at the top left of the list.', 'decalog' ) . '</p>';
			$content .= '<p>' . decalog_esc_html__( 'You can change the events log being viewed (if you have set more than one WordPress logger) with the selector at the top left of the list (don\'t forget to click on the "apply" button).', 'decalog' ) . '</p>';
			$content .= '<p>' . decalog_esc_html__( 'To filter the displayed events, use the small blue funnel next to the filterable items. These filters are cumulative, you can activate simultaneously several filters.', 'decalog' ) . '<br/>';
			$content .= '<em>' . decalog_esc_html__( 'Note these filters are effective even on pseudonymized or obfuscated fields.', 'decalog' ) . '</em></p>';
			$tabs[]   = [
				'title'   => decalog_esc_html__( 'Overview', 'decalog' ),
				'id'      => 'decalog-contextual-viewer-main-overview',
				'content' => $content,
			];
		}
		if ( false !== strpos( $this->screen->id, 'page_decalog-tviewer' ) ) {
			$content  = '<p>' . decalog_esc_html__( 'This screen displays the list of traces belonging to a specific WordPress logger. This list is sorted with the most recent trace at the top.', 'decalog' ) . '</p>';
			$content .= '<p>' . decalog_esc_html__( 'To move forward or backward in time, use the navigation buttons at the top or bottom right of this list.', 'decalog' ) . '</p>';
			$content .= '<p>' . decalog_esc_html__( 'You can change the traces log being viewed (if you have set more than one WordPress logger) with the selector at the top left of the list (don\'t forget to click on the "apply" button).', 'decalog' ) . '</p>';
			$content .= '<p>' . decalog_esc_html__( 'To filter the displayed traces, use the small blue funnel next to the filterable items. These filters are cumulative, you can activate simultaneously several filters.', 'decalog' ) . '<br/>';
			$content .= '<em>' . decalog_esc_html__( 'Note these filters are effective even on pseudonymized or obfuscated fields.', 'decalog' ) . '</em></p>';
			$tabs[]   = [
				'title'   => decalog_esc_html__( 'Overview', 'decalog' ),
				'id'      => 'decalog-contextual-viewer-main-overview',
				'content' => $content,
			];
		}
		// Admin Rights.
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$tabs[] = [
				'title'   => decalog_esc_html__( 'Admin rights', 'decalog' ),
				'id'      => 'decalog-contextual-viewer-main-rights',
				'content' => $this->get_admin_rights_content(),
			];
		}
		// Levels.
		if ( false !== strpos( $this->screen->id, 'page_decalog-viewer' ) ) {
			$tabs[] = [
				'title'   => decalog_esc_html__( 'Events levels', 'decalog' ),
				'id'      => 'decalog-contextual-viewer-main-levels',
				'content' => $this->get_levels_content(),
			];
		}
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
		$content  = '<p>' . decalog_esc_html__( 'This screen displays the details of a specific event.', 'decalog' ) . ' ' . decalog_esc_html__( 'It consists of four to six boxes, depending on your settings, which give specific details of the event:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Event', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'General information about the event.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Content', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Event code and message.', 'decalog' ) . '</p>';
		$content .= '<p><strong>WordPress</strong> &mdash; ' . decalog_esc_html__( 'User and site where the event occurs.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'HTTP request', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'The detail of the request that led to this event.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'PHP introspection', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'The code location where this event was generated.', 'decalog' ) . '</p>';
		/* translators: like in the sentence "PHP backtrace" or "WordPress backtrace" */
		$content .= '<p><strong>' . sprintf( decalog_esc_html__( '%s backtrace', 'decalog' ), 'PHP' ) . ' / ' . sprintf( decalog_esc_html__( '%s backtrace', 'decalog' ), 'WordPress' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Two different views of the same information: the backtrace of the call that led to this event.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => decalog_esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-event-overview',
			'content' => $content,
		];
		// Layout.
		$content  = '<p>' . decalog_esc_html__( 'You can use the following controls to arrange the screen to suit your usage preferences:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Screen Options', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Use the Screen Options tab to choose which boxes to show.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Drag and Drop', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'To rearrange the boxes, drag and drop by clicking on the title bar of the selected box and releasing when you see a gray dotted-line rectangle appear in the location you want to place the box.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Box Controls', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Click the title bar of the box to expand or collapse it.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => decalog_esc_html__( 'Layout', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-event-layout',
			'content' => $content,
		];
		// Levels.
		$tabs[] = [
			'title'   => decalog_esc_html__( 'Events levels', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-event-levels',
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
	 * @since    3.0.0
	 */
	private function set_contextual_viewer_trace() {
		$tabs = [];
		// Overview.
		$content  = '<p>' . decalog_esc_html__( 'This screen displays the details of a specific trace.', 'decalog' ) . ' ' . decalog_esc_html__( 'It consists of three boxes which give specific details of the trace:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Trace', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'General information about the trace.', 'decalog' ) . '</p>';
		$content .= '<p><strong>WordPress</strong> &mdash; ' . decalog_esc_html__( 'User and site where the trace has been captured.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Spans', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Spans composing the trace in a timeline. Note: the alternative grey/white graduations correspond to 250 ms.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => decalog_esc_html__( 'Overview', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-trace-overview',
			'content' => $content,
		];
		// Layout.
		$content  = '<p>' . decalog_esc_html__( 'You can use the following controls to arrange the screen to suit your usage preferences:', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Screen Options', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Use the Screen Options tab to choose which boxes to show.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Drag and Drop', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'To rearrange the boxes, drag and drop by clicking on the title bar of the selected box and releasing when you see a gray dotted-line rectangle appear in the location you want to place the box.', 'decalog' ) . '</p>';
		$content .= '<p><strong>' . decalog_esc_html__( 'Box Controls', 'decalog' ) . '</strong> &mdash; ' . decalog_esc_html__( 'Click the title bar of the box to expand or collapse it.', 'decalog' ) . '</p>';
		$tabs[]   = [
			'title'   => decalog_esc_html__( 'Layout', 'decalog' ),
			'id'      => 'decalog-contextual-viewer-trace-layout',
			'content' => $content,
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
			return;
		}
		if ( isset( $this->trace_id ) ) {
			$this->set_contextual_viewer_trace();
			return;
		}
		$this->set_contextual_viewer_main();
	}

	/**
	 * Get the logging description.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_logging( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'LOGGING.md', $attributes );
	}

	/**
	 * Get the monitoring description.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_monitoring( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'MONITORING.md', $attributes );
	}

	/**
	 * Get the tracing description.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_tracing( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'TRACING.md', $attributes );
	}

}
