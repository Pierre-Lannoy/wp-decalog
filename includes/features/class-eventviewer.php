<?php
/**
 * Event viewer
 *
 * Handles a view for a specific event.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Feather;
use Decalog\System\Database;

/**
 * Define the event viewer functionality.
 *
 * Handles a view for a specific event.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class EventViewer {

	/**
	 * The screen id.
	 *
	 * @since  1.0.0
	 * @var    string    $screen_id    The screen id.
	 */
	private static $screen_id = 'decalog_event_viewer';

	/**
	 * The full event detail.
	 *
	 * @since  1.0.0
	 * @var    array    $event    The full event detail.
	 */
	private $event = null;

	/**
	 * The events log id.
	 *
	 * @since  1.0.0
	 * @var    array    $logid    The events log id.
	 */
	private $logid = null;

	/**
	 * The event id.
	 *
	 * @since  1.0.0
	 * @var    array    $eventid    The event id.
	 */
	private $eventid = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $logid      The events log id.
	 * @param   string  $eventid    The specific event id.
	 * @since    1.0.0
	 */
	public function __construct($logid, $eventid) {
		$this->logid = $logid;
		$this->eventid = $eventid;
		$this->event = null;
		$database = new Database();
		$lines = $database->load_lines('decalog_' . str_replace( '-', '', $this->logid ), 'id', [$this->eventid]);
		if (1 === count($lines)) {
			foreach (Events::get() as $log) {
				if ($log['id'] === $this->logid) {
					if (0 === count($log ['limit']) || in_array($lines[0]['site_id'], $log ['limit']) ) {
						$this->event = $lines[0];
						break;
					}
				}
			}
		}
	}

	/**
	 * Append custom panel HTML to the "Screen Options" box of the current page.
	 * Callback for the 'screen_settings' filter.
	 *
	 * @param string $current Current content.
	 * @return string The HTML code to append to "Screen Options"
	 */
	public function display_screen_settings($current, $screen){
		if ( ! is_object( $screen ) || 'tools_page_decalog-viewer' !== $screen->id ) {
			return $current;
		}
		$current .= '<div class="metabox-prefs custom-options-panel requires-autosave"><input type="hidden" name="_wpnonce-decalog_viewer" value="' . wp_create_nonce('save_settings_decalog_viewer') . '" />';
		$current .= $this->get_options();
		$current .= '</div>';
		return $current ;
	}

	/**
	 * Add options.
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes_options() {
		$this->add_metaboxes();
	}

	/**
	 * Get the box options.
	 *
	 * @return string The HTML code to append.
	 * @since 1.0.0
	 */
	public function get_options() {
		$result = '<fieldset class="metabox-prefs">';
		$result .= '<legend>' . __('Boxes', 'live-weather-station') . '</legend>';
		$result .= $this->meta_box_prefs();
		$result .= '</fieldset>';
		return $result;
	}

	/**
	 * Prints the meta box preferences.
	 *
	 * @return string The HTML code to append.
	 * @since 1.0.0
	 */
	public function meta_box_prefs() {
		global $wp_meta_boxes;
		$result = '';
		if (empty($wp_meta_boxes[self::$screen_id])) {
			return '';
		}
		$hidden = get_hidden_meta_boxes(self::$screen_id);
		foreach (array_keys($wp_meta_boxes[self::$screen_id]) as $context) {
			foreach (array('high', 'core', 'default', 'low') as $priority) {
				if (!isset( $wp_meta_boxes[self::$screen_id][$context][$priority])) {
					continue;
				}
				foreach ($wp_meta_boxes[self::$screen_id][$context][$priority] as $box) {
					if (false === $box || !$box['title']) {
						continue;
					}
					if ('submitdiv' === $box['id'] || 'linksubmitdiv' === $box['id']) {
						continue;
					}
					$box_id = $box['id'];
					$result .= '<label for="' . $box_id . '-hide">';
					$result .= '<input class="hide-postbox-tog" name="' . $box_id . '-hide" type="checkbox" id="' . $box_id . '-hide" value="' . $box_id . '"' . (!in_array($box_id, $hidden) ? ' checked="checked"' : '') . ' />';
					$result .= $box['title'] . '</label>';
				}
			}
		}
		return $result;
	}

	/**
	 * Get the event viewer.
	 *
	 * @since 1.0.0
	 **/
	public function get() {
		echo '<div class="wrap">';
		if (isset($this->event)) {
			$icon = '<img style="width:30px;float:left;padding-right:8px;" src="' . EventTypes::$icons[ $this->event['level'] ] . '" />';
			$name = ChannelTypes::$channel_names[ strtoupper( $this->event['channel'] ) ] . '&nbsp;#' . $this->event['id'];
			echo '<h2>' . $icon . $name . '</h2>';
			settings_errors();
			echo '<form name="decalog_event" method="post">';
			echo '<div id="dashboard-widgets-wrap">';
			wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
			wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
			echo '    <div id="dashboard-widgets" class="metabox-holder">';
			echo '        <div id="postbox-container-1" class="postbox-container">';
			do_meta_boxes(self::$screen_id, 'advanced', null);
			echo '        </div>';
			echo '        <div id="postbox-container-2" class="postbox-container">';
			do_meta_boxes(self::$screen_id, 'side', null);
			echo '        </div>';
			echo '        <div id="postbox-container-3" class="postbox-container">';
			do_meta_boxes(self::$screen_id, 'column3', null);
			echo '        </div>';
			echo '        <div id="postbox-container-4" class="postbox-container">';
			do_meta_boxes(self::$screen_id, 'column4', null);
			echo '        </div>';
			echo '    </div>';
			echo '</div>';
			echo '</form>';
		} else {
			echo '<h2>' . esc_html('Forbidden', 'decalog') . '</h2>';
			settings_errors();
			echo '<p>' . esc_html('The event or events log you tried to access is out of your scope.', 'decalog') . '</p>';
			echo '<p>' . esc_html('If you think this is an error, please contact the network administrator with these details:.', 'decalog');
			echo '<ul>';
			echo '<li>' . sprintf(esc_html('Events log: %s', 'decalog'), '<code>' . $this->logid . '</code>') . '</li>';
			echo '<li>' . sprintf(esc_html('Event: %s', 'decalog'), '<code>' . $this->eventid . '</code>') . '</li>';
			echo '</ul>';
			echo '</p>';
		}
		echo '</div>';
	}

	/**
	 * Add footer scripts.
	 *
	 * @since 1.0.0
	 */
	public function add_footer() {
		$result = '<script language="javascript" type="text/javascript">';
		$result .= "    jQuery(document).ready( function($) {";
		$result .= "        $('.if-js-closed').removeClass('if-js-closed').addClass('closed');";
		$result .= "        if(typeof postboxes !== 'undefined')";
		$result .= "            postboxes.add_postbox_toggles('" . self::$screen_id . "');";
		$result .= "    });";
		$result .= '</script>';
		echo $result;
	}

	/**
	 * Add all the needed meta boxes.
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {
		// Left column
		add_meta_box('decalog-main', __('Event', 'live-weather-station' ), [$this, 'event_widget'], self::$screen_id, 'advanced');
		add_meta_box('decalog-php', __('PHP introspection', 'live-weather-station' ), [$this, 'php_widget'], self::$screen_id, 'advanced');
		add_meta_box('decalog-phpbacktrace', __('PHP backtrace', 'live-weather-station' ), [$this, 'phpbacktrace_widget'], self::$screen_id, 'advanced');
		// Right column
		add_meta_box('decalog-wordpress', 'WordPress', [$this, 'wordpress_widget'], self::$screen_id, 'side');
		add_meta_box('decalog-http', __('HTTP request', 'live-weather-station' ), [$this, 'http_widget'], self::$screen_id, 'side');
		add_meta_box('decalog-wpbacktrace', __('WordPress backtrace', 'live-weather-station' ), [$this, 'wpbacktrace_widget'], self::$screen_id, 'side');
	}

	/**
	 * Get content of the event widget box.
	 *
	 * @since 1.0.0
	 */
	public function event_widget() {
		echo 'AAA';
	}

	/**
	 * Get content of the WordPress widget box.
	 *
	 * @since 1.0.0
	 */
	public function wordpress_widget() {
		echo 'AAA';
	}

	/**
	 * Get content of the http widget box.
	 *
	 * @since 1.0.0
	 */
	public function http_widget() {
		echo 'AAA';
	}

	/**
	 * Get content of the php widget box.
	 *
	 * @since 1.0.0
	 */
	public function php_widget() {
		echo 'AAA';
	}

	/**
	 * Get content of the php backtrace widget box.
	 *
	 * @since 1.0.0
	 */
	public function phpbacktrace_widget() {
		echo 'AAA';
	}

	/**
	 * Get content of the WordPress backtrace widget box.
	 *
	 * @since 1.0.0
	 */
	public function wpbacktrace_widget() {
		echo 'AAA';
	}

}
