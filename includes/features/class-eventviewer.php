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
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $logid      The events log id.
	 * @param   string  $eventid    The specific event id.
	 * @since    1.0.0
	 */
	public function __construct($logid, $eventid) {
		if ( Events::loggers_count() > 0) {

		}
	}

	/**
	 * Get the event viewer.
	 *
	 * @since 1.0.0
	 **/
	public function get() {
		echo '<div class="wrap">';
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
		echo '</div>';
	}

}
