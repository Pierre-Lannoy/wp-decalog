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
	 * Get the event viewer.
	 *
	 * @since 1.0.0
	 **/
	public function get() {
		echo '<div class="wrap">';
		if (isset($this->event)) {
			echo '<h2>AAA</h2>';
			//include(LWS_ADMIN_DIR.'partials/StationTab.php');
			settings_errors();
			/*echo '<form name="lws_station" method="post">';
			echo '<div id="dashboard-widgets-wrap">';
			wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
			wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
			echo '    <div id="dashboard-widgets" class="metabox-holder">';
			echo '        <div id="postbox-container-1" class="postbox-container">';
			do_meta_boxes($this->screen_id, 'advanced', null);
			echo '        </div>';
			echo '        <div id="postbox-container-2" class="postbox-container">';
			do_meta_boxes($this->screen_id, 'side', null);
			echo '        </div>';
			echo '        <div id="postbox-container-3" class="postbox-container">';
			do_meta_boxes($this->screen_id, 'column3', null);
			echo '        </div>';
			echo '        <div id="postbox-container-4" class="postbox-container">';
			do_meta_boxes($this->screen_id, 'column4', null);
			echo '        </div>';
			echo '    </div>';
			echo '</div>';
			echo '</form>';*/
		} else {
			echo '<h2>' . esc_html('Forbidden', 'decalog') . '</h2>';
			echo '<p>' . esc_html('The event of events log you tried to access is out of scope.', 'decalog') . '</p>';
			echo '<p>' . esc_html('If you think this is an error, please contact the network administrator with these details:.', 'decalog');
			echo '<ul>';
			echo '<li>' . sprintf(esc_html('Events log: %s', 'decalog'), '<code>' . $this->logid . '</code>') . '</li>';
			echo '<li>' . sprintf(esc_html('Event: %s', 'decalog'), '<code>' . $this->eventid . '</code>') . '</li>';
			echo '</ul>';
			echo '</p>';
		}
		echo '</div>';
	}

}
