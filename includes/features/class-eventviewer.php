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

use Decalog\Storage\AbstractStorage;
use Decalog\Storage\APCuStorage;
use Decalog\Storage\DBStorage;
use Decalog\System\Date;
use Decalog\System\Option;
use Decalog\System\PHP;
use Decalog\System\Timezone;
use Feather;
use Decalog\System\Database;
use Decalog\System\User;
use Decalog\System\UserAgent;
use Decalog\System\L10n;
use Decalog\System\GeoIP;

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
	 * The internal logger.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    DLogger    $logger    The plugin admin logger.
	 */
	protected $logger;

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
	 * The device.
	 *
	 * @since  1.0.0
	 * @var    object    $device    The device.
	 */
	private $device = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $logid      The events log id.
	 * @param   string  $eventid    The specific event id.
	 * @param   DLogger $logger     The internal logger.
	 * @since    1.0.0
	 */
	public function __construct( $logid, $eventid, $logger ) {
		$this->logid   = $logid;
		$this->logger  = $logger;
		$this->eventid = $eventid;
		$this->event   = null;
		$this->device  = UserAgent::get( '-' );
		$log           = null;
		$loggers       = Option::network_get( 'loggers' );
		if ( array_key_exists( $this->logid, $loggers ) ) {
			$bucket_name = 'decalog_' . str_replace( '-', '', $this->logid );
			switch ( $loggers[ $this->logid ]['configuration']['constant-storage'] ) {
				case 'apcu':
					$storage = new APCuStorage( $bucket_name );
					break;
				default:
					global $wpdb;
					$storage = new DBStorage( $bucket_name );
			}
			$log = $storage->get_by_id( $this->eventid );
		}
		if ( isset( $log ) ) {
			foreach ( Events::get() as $logged ) {
				if ( $logged['id'] === $this->logid ) {
					if ( ! array_key_exists( 'limit', $logged ) || in_array( $log['site_id'], $logged ['limit'] ) ) {
						$this->event  = $log;
						$this->device = UserAgent::get( $this->event['user_agent'] );
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
	 * @param object $screen The current screen.
	 * @return string The HTML code to append to "Screen Options".
	 * @since 1.0.0
	 */
	public function display_screen_settings( $current, $screen ) {
		if ( is_object( $screen ) && false !== strpos( $screen->id, 'page_decalog-viewer' ) && false === strpos( $current, 'id="option-decalog-viewer"' ) ) {
			$current .= '<div id="option-decalog-viewer" class="metabox-prefs custom-options-panel requires-autosave"><input type="hidden" name="_wpnonce-decalog_viewer" value="' . wp_create_nonce( 'save_settings_decalog_viewer' ) . '" />';
			$current .= $this->get_options();
			$current .= '</div>';
		}
		return $current;
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
		$result  = '<fieldset class="metabox-prefs">';
		$result .= '<legend>' . esc_html__( 'Boxes', 'decalog' ) . '</legend>';
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
		if ( empty( $wp_meta_boxes[ self::$screen_id ] ) ) {
			return '';
		}
		$hidden = get_hidden_meta_boxes( self::$screen_id );
		foreach ( array_keys( $wp_meta_boxes[ self::$screen_id ] ) as $context ) {
			foreach ( array( 'high', 'core', 'default', 'low' ) as $priority ) {
				if ( ! isset( $wp_meta_boxes[ self::$screen_id ][ $context ][ $priority ] ) ) {
					continue;
				}
				foreach ( $wp_meta_boxes[ self::$screen_id ][ $context ][ $priority ] as $box ) {
					if ( false === $box || ! $box['title'] ) {
						continue;
					}
					if ( 'submitdiv' === $box['id'] || 'linksubmitdiv' === $box['id'] ) {
						continue;
					}
					$box_id  = $box['id'];
					$result .= '<label for="' . $box_id . '-hide">';
					$result .= '<input class="hide-postbox-tog" name="' . $box_id . '-hide" type="checkbox" id="' . $box_id . '-hide" value="' . $box_id . '"' . ( ! in_array( $box_id, $hidden, false ) ? ' checked="checked"' : '' ) . ' />';
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
		if ( isset( $this->event ) ) {
			$icon = '<img style="width:30px;float:left;padding-right:8px;" src="' . EventTypes::$icons[ $this->event['level'] ] . '" />';
			$name = ChannelTypes::$channel_names[ strtoupper( $this->event['channel'] ) ] . '&nbsp;#' . $this->event['id'];
			// phpcs:ignore
			echo '<h2>' . $icon . $name . '</h2>';
			settings_errors();
			echo '<form name="decalog_event" method="post">';
			echo '<div id="dashboard-widgets-wrap">';
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
			echo '    <div id="dashboard-widgets" class="metabox-holder">';
			echo '        <div id="postbox-container-1" class="postbox-container">';
			do_meta_boxes( self::$screen_id, 'advanced', null );
			echo '        </div>';
			echo '        <div id="postbox-container-2" class="postbox-container">';
			do_meta_boxes( self::$screen_id, 'side', null );
			echo '        </div>';
			echo '        <div id="postbox-container-3" class="postbox-container">';
			do_meta_boxes( self::$screen_id, 'column3', null );
			echo '        </div>';
			echo '        <div id="postbox-container-4" class="postbox-container">';
			do_meta_boxes( self::$screen_id, 'column4', null );
			echo '        </div>';
			echo '    </div>';
			echo '</div>';
			echo '</form>';
		} else {
			echo '<h2>' . esc_html__( 'Forbidden', 'decalog' ) . '</h2>';
			settings_errors();
			echo '<p>' . esc_html__( 'The event or events log you tried to access is out of your scope.', 'decalog' ) . '</p>';
			echo '<p>' . esc_html__( 'If you think this is an error, please contact the network administrator with these details:', 'decalog' );
			echo '<ul>';
			// phpcs:ignore
			echo '<li>' . sprintf( esc_html__( 'Events log: %s', 'decalog' ), '<code>' . $this->logid . '</code>' ) . '</li>';
			// phpcs:ignore
			echo '<li>' . sprintf( esc_html__( 'Event: %s', 'decalog' ), '<code>' . $this->eventid . '</code>' ) . '</li>';
			echo '</ul>';
			echo '</p>';
			$this->logger->warning( sprintf( 'Trying to access out of scope event #%s from events log {%s}.', $this->eventid, $this->logid ), 403 );
		}
		echo '</div>';
	}

	/**
	 * Add footer scripts.
	 *
	 * @since 1.0.0
	 */
	public function add_footer() {
		$result  = '<script>';
		$result .= '    jQuery(document).ready( function($) {';
		$result .= "        $('.if-js-closed').removeClass('if-js-closed').addClass('closed');";
		$result .= "        if(typeof postboxes !== 'undefined')";
		$result .= "            postboxes.add_postbox_toggles('" . self::$screen_id . "');";
		$result .= '    });';
		$result .= '</script>';
		// phpcs:ignore
		echo $result;
	}

	/**
	 * Add all the needed meta boxes.
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {
		// Left column.
		add_meta_box( 'decalog-main', esc_html__( 'Event', 'decalog' ), [ $this, 'event_widget' ], self::$screen_id, 'advanced' );
		add_meta_box( 'decalog-message', esc_html__( 'Content', 'decalog' ), [ $this, 'message_widget' ], self::$screen_id, 'advanced' );
		add_meta_box( 'decalog-wordpress', 'WordPress', [ $this, 'wordpress_widget' ], self::$screen_id, 'advanced' );
		if ( $this->device->class_is_bot ) {
			add_meta_box( 'decalog-bot', esc_html__( 'Bot details', 'decalog' ), [ $this, 'bot_widget' ], self::$screen_id, 'advanced' );
		} elseif ( $this->device->class_is_desktop || $this->device->class_is_mobile ) {
			add_meta_box( 'decalog-device', esc_html__( 'Device details', 'decalog' ), [ $this, 'device_widget' ], self::$screen_id, 'advanced' );
		} elseif ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			add_meta_box( 'decalog-other', esc_html__( 'Client details', 'decalog' ), [ $this, 'call_widget' ], self::$screen_id, 'advanced' );
		}
		if ( 'unknown' !== ( $this->event['verb'] ?? 'unknown' ) ) {
			add_meta_box( 'decalog-http', esc_html__( 'HTTP request', 'decalog' ), [ $this, 'http_widget' ], self::$screen_id, 'advanced' );
		}
		add_meta_box( 'decalog-php', esc_html__( 'PHP introspection', 'decalog' ), [ $this, 'php_widget' ], self::$screen_id, 'advanced' );
		// Right column.
		/* translators: like in the sentence "PHP backtrace" or "WordPress backtrace" */
		add_meta_box( 'decalog-wpbacktrace', sprintf( esc_html__( '%s backtrace', 'decalog' ), 'WordPress' ), [ $this, 'wpbacktrace_widget' ], self::$screen_id, 'side' );
		/* translators: like in the sentence "PHP backtrace" or "WordPress backtrace" */
		add_meta_box( 'decalog-phpbacktrace', sprintf( esc_html__( '%s backtrace', 'decalog' ), 'PHP' ), [ $this, 'phpbacktrace_widget' ], self::$screen_id, 'side' );
	}

	/**
	 * Print an activity block.
	 *
	 * @param   string $content The content of the block.
	 * @since 1.0.0
	 */
	private function output_activity_block( $content ) {
		echo '<div class="activity-block" style="padding-bottom: 0;padding-top: 0;">';
		// phpcs:ignore
		echo $content;
		echo '</div>';
	}

	/**
	 * Get a section to include in a block.
	 *
	 * @param   string $content The content of the section.
	 * @return  string  The section, ready to print.
	 * @since 1.0.0
	 */
	private function get_section( $content ) {
		return '<div style="margin-bottom: 10px;">' . $content . '</div>';
	}

	/**
	 * Get an icon.
	 *
	 * @param   string $icon_name The name of the icon.
	 * @param   string $background The background color.
	 * @return  string  The icon, as image, ready to print.
	 * @since 1.0.0
	 */
	private function get_icon( $icon_name, $background = '#F9F9F9' ) {
		return '<img style="width:18px;float:left;padding-right:6px;" src="' . Feather\Icons::get_base64( $icon_name, $background, '#9999BB' ) . '" />';
	}

	/**
	 * Get an external link markup.
	 *
	 * @param   string $url The url.
	 * @return  string  The link markup, ready to print.
	 * @since 1.0.0
	 */
	private function get_external_link( $url ) {
		if ( '' === $url ) {
			return '';
		}
		return '<a href="' . $url . '" target="_blank"><img style="width:10px;padding-left:4px;padding-right:6px;vertical-align:text-top;" src="' . Feather\Icons::get_base64( 'external-link', 'none', '#9999BB' ) . '" /></a>';
	}

	/**
	 * Get an intenel link markup.
	 *
	 * @param   string $url The url.
	 * @param   string $anchor The anchor.
	 * @return  string  The link markup, ready to print.
	 * @since 1.0.0
	 */
	private function get_internal_link( $url, $anchor ) {
		if ( '' === $url ) {
			return $anchor;
		}
		return '<a href="' . $url . '" style="text-decoration:none;color:inherit;">' . $anchor . '</a>';
	}

	/**
	 * Get content of the event widget box.
	 *
	 * @since 1.0.0
	 */
	public function event_widget() {
		// Event type.
		$icon     = '<img style="width:18px;float:left;padding-right:6px;" src="' . EventTypes::$icons[ $this->event['level'] ] . '" />';
		$level    = ucwords( strtolower( EventTypes::$level_names[ EventTypes::$levels[ $this->event['level'] ] ] ) );
		$channel  = ChannelTypes::$channel_names[ strtoupper( $this->event['channel'] ) ];
		$content  = '<span style="width:40%;cursor: default;float:left">' . $icon . $level . '</span>';
		$content .= '<span style="width:60%;cursor: default;">' . $this->get_icon( 'activity', 'none' ) . $channel . '</span>';
		$event    = $this->get_section( $content );
		// Event time.
		$time    = Date::get_date_from_mysql_utc( $this->event['timestamp'], Timezone::network_get()->getName(), 'Y-m-d H:i:s' );
		$dif     = Date::get_positive_time_diff_from_mysql_utc( $this->event['timestamp'] );
		$content = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'clock' ) . $time . '</span> <span style="color:silver">(' . $dif . ')</span>';
		$hour    = $this->get_section( $content );
		// Event source.
		$class     = ClassTypes::$classe_names[ strtolower( $this->event['class'] ) ];
		$component = $this->event['component'] . ' ' . $this->event['version'];
		$content   = '<span style="width:40%;cursor: default;float:left">' . $this->get_icon( 'folder' ) . $class . '</span>';
		$content  .= '<span style="width:60%;cursor: default;">' . $this->get_icon( 'box' ) . $component . '</span>';
		$source    = $this->get_section( $content );

		$this->output_activity_block( $event . $hour . $source );
	}

	/**
	 * Get content of the message widget box.
	 *
	 * @since 1.0.0
	 */
	public function message_widget() {
		// Event code.
		$content = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'tag' ) . $this->event['code'] . '</span>';
		$error   = $this->get_section( $content );
		// Event message.
		$content = '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'message-square' ) . $this->event['message'] . '</span>';
		$message = $this->get_section( $content );

		$this->output_activity_block( $error . $message );
	}

	/**
	 * Get content of the WordPress widget box.
	 *
	 * @since 1.0.0
	 */
	public function wordpress_widget() {
		// User detail.
		if ( 'anonymous' === $this->event['user_name'] ) {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user' ) . esc_html__( 'Anonymous user', 'decalog' ) . '</span>' );
		} elseif ( 0 === strpos( $this->event['user_name'], '{' ) ) {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user' ) . esc_html__( 'Pseudonymized user', 'decalog' ) . '</span>' );
		} elseif ( 0 !== (int) $this->event['user_id'] ) {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user-check' ) . User::get_user_string( (int) $this->event['user_id'] ) . '</span>' );
		} else {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user-x' ) . esc_html__( 'Deleted user', 'decalog' ) . '</span>' );
		}
		// Site detail.
		$content = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'layout' ) . $this->event['site_name'] . '</span>';
		$site    = $this->get_section( $content );

		$this->output_activity_block( $user . $site );
	}

	/**
	 * Get content of the http widget box.
	 *
	 * @since 1.0.0
	 */
	public function http_widget() {
		// Server detail.
		$ip   = $this->event['remote_ip'];
		$icon = '';
		if ( 0 === strpos( $ip, '{' ) ) {
			$ip = esc_html__( 'obfuscated IP', 'decalog' );
		} else {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
				$geoip = new GeoIP();
				$icon  = $geoip->get_flag( $ip, '', 'width:14px;padding-left:4px;padding-right:4px;vertical-align:baseline;' );
			}
		}
		// phpcs:ignore
		$ip      = sprintf( esc_html__( 'from %s', 'decalog' ), $icon . $ip );
		$content = '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'layout' ) . $this->event['server'] . ' ' .  $ip  . '</span>';
		$server  = $this->get_section( $content );
		// Request detail.
		$verb = $this->event['verb'];
		if ( '-' !== $verb ) {
			$verb = '<span style="vertical-align: middle;font-size:8px;padding:2px 6px;text-transform:uppercase;font-weight: bold;background-color:#9999BB;color:#F9F9F9;border-radius:2px;cursor: default;word-break: break-word;">' . $verb . '</span>';
		} else {
			$verb = '';
		}
		$content = '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'server' ) . $this->event['url'] . '&nbsp;' . $verb . '</span>';
		$request = $this->get_section( $content );
		// referrer detail.
		if ( isset( $this->event['referrer'] ) ) {
			$content  = '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'arrow-left-circle' ) . $this->event['referrer'] . '</span>';
			$referrer = $this->get_section( $content );
		}
		$this->output_activity_block( $server . $request . $referrer );
	}

	/**
	 * Get content of the device widget box.
	 *
	 * @since 1.0.0
	 */
	private function get_client() {
		if ( $this->device->client_is_browser ) {
			$url     = [
				'site' => $this->event['site_id'],
				'type' => 'browser',
				'id'   => $this->device->client_short_name,
			];
			$iclient = '<img style="width:16px;float:left;padding-right:6px;" src="' . $this->device->browser_icon_base64() . '" />';
			$client  = $this->get_internal_link( UserAgent::get_analytics_url( $url ), ( '-' !== $this->device->client_name ? $this->device->client_name : esc_html__( 'Generic', 'decalog' ) ) ) . ( '-' !== $this->device->client_version ? ' ' . $this->device->client_version : '' );
			$content = '<span style="width:100%;cursor: default;">' . $iclient . $client . '</span> <span style="color:silver">(' . $this->device->client_engine . ')</span>';
			return $this->get_section( $content );
		}
		$url = [
			'site' => $this->event['site_id'],
			'type' => 'browser',
			'id'   => $this->device->client_short_name,
		];
		if ( '' !== $this->device->client_name ) {
			$client  = $this->get_internal_link( UserAgent::get_analytics_url( $url ), ( '-' !== $this->device->client_name ? $this->device->client_name : esc_html__( 'Generic', 'decalog' ) ) ) . ( '-' !== $this->device->client_version ? ' ' . $this->device->client_version : '' );
			$content = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'play-circle' ) . $client . '</span> <span style="color:silver">' . $this->device->client_full_type . '</span>';
		} else {
			$content = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'play-circle' ) . esc_html__( 'Local shell', 'decalog' ) . '</span>';
		}

		return $this->get_section( $content );
	}

	/**
	 * Get content of the device widget box.
	 *
	 * @since 1.0.0
	 */
	public function device_widget() {
		if ( '' === $this->device->brand_short_name ) {
			$id = '-';
		} else {
			$id = $this->device->brand_short_name;
		}
		$url      = [
			'site'     => $this->event['site_id'],
			'type'     => 'device',
			'id'       => $id,
			'extended' => '' !== $this->device->model_name ? $this->device->model_name : '-',
		];
		$idevice  = '<img style="width:16px;float:left;padding-right:6px;" src="' . $this->device->brand_icon_base64() . '" />';
		$device   = ( '-' !== $this->device->brand_name && '' !== $this->device->brand_name ? $this->device->brand_name : esc_html__( 'Generic', 'decalog' ) ) . ( '-' !== $this->device->model_name ? ' ' . $this->device->model_name : '' );
		$device   = $this->get_internal_link( UserAgent::get_analytics_url( $url ), $device );
		$url      = [
			'site' => $this->event['site_id'],
			'type' => 'os',
			'id'   => $this->device->os_short_name,
		];
		$ios      = '<img style="width:16px;float:left;padding-right:6px;" src="' . $this->device->os_icon_base64() . '" />';
		$os       = ( '-' !== $this->device->os_name ? $this->get_internal_link( UserAgent::get_analytics_url( $url ), $this->device->os_name ) : esc_html__( 'Unknown', 'decalog' ) ) . ( '-' !== $this->device->os_version ? ' ' . $this->device->os_version : '' );
		$content  = '<span style="width:40%;cursor: default;float:left">' . $idevice . $device . '</span>';
		$content .= '<span style="width:60%;cursor: default;">' . $ios . $os . '</span>';
		$model    = $this->get_section( $content );

		$this->output_activity_block( $model . $this->get_client() );
	}

	/**
	 * Get content of the bot widget box.
	 *
	 * @since 1.0.0
	 */
	public function bot_widget() {
		$ibot    = '<img style="width:16px;float:left;padding-right:7px;padding-left:1px;" src="' . $this->device->bot_icon_base64() . '" />';
		$bot     = ( '-' !== $this->device->bot_name ? $this->device->bot_name : esc_html__( 'Unknown', 'decalog' ) );
		if ( '' !== $this->device->bot_url )
		$content = '<span style="width:100%;cursor: default;">' . $ibot . $bot . $this->get_external_link( $this->device->bot_url ) . '</span>';
		$model   = $this->get_section( $content );

		$imanuf   = $this->get_icon( 'home' );
		$manuf    = ( '-' !== $this->device->bot_producer_name ? $this->device->bot_producer_name : esc_html__( 'Unknown', 'decalog' ) );
		$itype    = $this->get_icon( 'info' );
		$type     = $this->device->bot_full_category;
		$content  = '<span style="width:40%;cursor: default;float:left">' . $itype . $type .'</span>';
		$content .= '<span style="width:60%;cursor: default;">' . $imanuf . $manuf . $this->get_external_link( $this->device->bot_producer_url ) . '</span>';
		$prod     = $this->get_section( $content );

		$this->output_activity_block( $model . $prod );
	}

	/**
	 * Get content of the call widget box.
	 *
	 * @since 1.0.0
	 */
	public function call_widget() {

		$this->output_activity_block( $this->get_client() );
	}

	/**
	 * Get content of the php widget box.
	 *
	 * @since 1.0.0
	 */
	public function php_widget() {
		// File detail.
		$element = PHP::normalized_file( $this->event['file'] );
		$element = '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'file-text' ) . $element . ':' . ( $this->event['line'] ?? '' ) . '</span>';
		$file    = $this->get_section( $element );
		// Function detail.
		$element  = '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'code', 'none' ) . ( $this->event['function'] ?? '' ) . '</span>';
		$function = $this->get_section( $element );
		// Function detail.
		$element = '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'layers' ) . ( $this->event['classname'] ?? '' ) . '</span>';
		$class   = $this->get_section( $element );
		$this->output_activity_block( $class . $function . $file );
	}

	/**
	 * Get content of the php backtrace widget box.
	 *
	 * @since 1.0.0
	 */
	public function phpbacktrace_widget() {
		// phpcs:ignore
		$trace   = unserialize( $this->event['trace'] ?? '' );
		$content = '';
		if ( is_array( $trace ) ) {
			if ( array_key_exists( 'error', $trace ) ) {
				$error   = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'alert-triangle' ) . $trace['error'] . '</span>';
				$content = $this->get_section( $error );
			} else {
				foreach ( array_reverse( $trace['callstack'] ) as $idx => $item ) {
					if ( array_key_exists( 'call', $item ) ) {
						if ( $idx < 10 ) {
							$element = '<span style="font-family:monospace;font-size:8px;font-weight: bold;vertical-align: middle;padding:3px 5px;background-color:#F9F9F9;color:#9999BB;border:2px solid #9999BB;border-radius:50%;cursor: default;">' . $idx . '</span> &nbsp;' . $item['call'];
						} else {
							$element = '<span style="font-family:monospace;font-size:8px;font-weight: bold;vertical-align: middle;padding:3px;background-color:#F9F9F9;color:#9999BB;border:2px solid #9999BB;border-radius:50%;cursor: default;">' . $idx . '</span> &nbsp;' . $item['call'];
						}
						if ( array_key_exists( 'file', $item ) ) {
							$element .= '<br/><span style="float:left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="width:100%;cursor: default;">' . $this->get_icon( 'file-text' ) . $item['file'] . '</span>';
						}
					} else {
						$element = '<span style="float:left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="width:100%;cursor: default;">' . $this->get_icon( 'eye-off' ) . esc_html__( 'No backtrace available', 'decalog' ) . '</span>';
					}
					$content .= $this->get_section( $element );
				}
			}
		} else {
			$content = '<span style="float:left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="width:100%;cursor: default;">' . $this->get_icon( 'eye-off' ) . esc_html__( 'No backtrace available', 'decalog' ) . '</span>';
		}
		$this->output_activity_block( $content );
	}

	/**
	 * Get content of the WordPress backtrace widget box.
	 *
	 * @since 1.0.0
	 */
	public function wpbacktrace_widget() {
		// phpcs:ignore
		$trace   = unserialize( $this->event['trace'] ?? '' );
		$content = '';
		if ( is_array( $trace ) ) {
			if ( array_key_exists( 'error', $trace ) ) {
				$error   = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'alert-triangle' ) . $trace['error'] . '</span>';
				$content = $this->get_section( $error );
			} else {
				foreach ( array_reverse( $trace['wordpress'] ) as $idx => $item ) {
					if ( '' !== $item ) {
						if ( $idx < 10 ) {
							$element = '<span style="font-family:monospace;font-size:8px;font-weight: bold;vertical-align: middle;padding:3px 5px;background-color:#F9F9F9;color:#9999BB;border:2px solid #9999BB;border-radius:50%;cursor: default;">' . $idx . '</span> &nbsp;' . $item;
						} else {
							$element = '<span style="font-family:monospace;font-size:8px;font-weight: bold;vertical-align: middle;padding:3px;background-color:#F9F9F9;color:#9999BB;border:2px solid #9999BB;border-radius:50%;cursor: default;">' . $idx . '</span> &nbsp;' . $item;
						}
					} else {
						$element = '<span style="float:left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="width:100%;cursor: default;">' . $this->get_icon( 'eye-off' ) . esc_html__( 'No backtrace available', 'decalog' ) . '</span>';
					}
					$content .= $this->get_section( $element );
				}
			}
		} else {
			$content = '<span style="float:left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="width:100%;cursor: default;">' . $this->get_icon( 'eye-off' ) . esc_html__( 'No backtrace available', 'decalog' ) . '</span>';
		}
		$this->output_activity_block( $content );
	}

}
