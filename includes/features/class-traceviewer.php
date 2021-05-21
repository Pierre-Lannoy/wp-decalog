<?php
/**
 * Trace viewer
 *
 * Handles a view for a specific trace.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Storage\AbstractStorage;
use Decalog\Storage\APCuStorage;
use Decalog\Storage\DBTraceStorage;
use Decalog\System\Date;
use Decalog\System\Option;
use Decalog\System\Timezone;
use Feather;
use Feather\Icons;
use Decalog\System\User;
use Decalog\Plugin\Feature\Traces;

/**
 * Define the trace viewer functionality.
 *
 * Handles a view for a specific trace.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class TraceViewer {

	/**
	 * The internal logger.
	 *
	 * @since  3.0.0
	 * @access protected
	 * @var    DLogger    $logger    The plugin admin logger.
	 */
	protected $logger;

	/**
	 * The screen id.
	 *
	 * @since  3.0.0
	 * @var    string    $screen_id    The screen id.
	 */
	private static $screen_id = 'decalog_trace_viewer';

	/**
	 * The full trace detail.
	 *
	 * @since  3.0.0
	 * @var    array    $trace    The full trace detail.
	 */
	private $trace = null;

	/**
	 * The traces log id.
	 *
	 * @since  3.0.0
	 * @var    array    $logid    The traces log id.
	 */
	private $logid = null;

	/**
	 * The trace id.
	 *
	 * @since  3.0.0
	 * @var    array    $traceid    The trace id.
	 */
	private $traceid = null;

	/**
	 * Colors for timeline.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [
		'main request' => '#73879C',
		'server'       => '#73879C',
		'wordpress'    => '#73879C',
		'core'         => '#3398DB',
		'plugin'       => '#9B59B6',
		'theme'        => '#b2c326',
		'db'           => '#BDC3C6',
	];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $logid      The events log id.
	 * @param   string  $traceid    The specific trace id.
	 * @param   DLogger $logger     The internal logger.
	 * @since    3.0.0
	 */
	public function __construct( $logid, $traceid, $logger ) {
		$this->logid   = $logid;
		$this->logger  = $logger;
		$this->traceid = $traceid;
		$this->trace   = null;
		$log           = null;
		$loggers       = Option::network_get( 'loggers' );
		if ( array_key_exists( $this->logid, $loggers ) ) {
			$bucket_name = 'decalog_' . str_replace( '-', '', $this->logid );
			switch ( $loggers[ $this->logid ]['configuration']['constant-storage'] ) {
				case 'apcu':
					$storage = new APCuStorage( $bucket_name );
					break;
				default:
					$storage = new DBTraceStorage( $bucket_name );
			}
			$log = $storage->get_by_id( $this->traceid );
		}
		if ( isset( $log ) ) {
			foreach ( Traces::get() as $logged ) {
				if ( $logged['id'] === $this->logid ) {
					if ( ! array_key_exists( 'limit', $logged ) || in_array( $log['site_id'], $logged ['limit'] ) ) {
						$this->trace          = $log;
						$this->trace['spans'] = json_decode( $this->trace['spans'], true );
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
	 * @since 3.0.0
	 */
	public function display_screen_settings( $current, $screen ) {
		if ( is_object( $screen ) && false !== strpos( $screen->id, 'page_decalog-tviewer' ) && false === strpos( $current, 'id="option-decalog-tviewer"' ) ) {
			$current .= '<div id="option-decalog-tviewer" class="metabox-prefs custom-options-panel requires-autosave"><input type="hidden" name="_wpnonce-decalog_tviewer" value="' . wp_create_nonce( 'save_settings_decalog_tviewer' ) . '" />';
			$current .= $this->get_options();
			$current .= '</div>';
		}
		return $current;
	}

	/**
	 * Add options.
	 *
	 * @since 3.0.0
	 */
	public function add_metaboxes_options() {
		$this->add_metaboxes();
	}

	/**
	 * Get the box options.
	 *
	 * @return string The HTML code to append.
	 * @since 3.0.0
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
	 * @since 3.0.0
	 */
	public function meta_box_prefs() {
		global $wp_meta_boxes;
		$result = '';
		if ( empty( $wp_meta_boxes[ self::$screen_id ] ) ) {
			return '';
		}
		$hidden = get_hidden_meta_boxes( self::$screen_id );
		foreach ( array_keys( $wp_meta_boxes[ self::$screen_id ] ) as $context ) {
			foreach ( [ 'high', 'core', 'default', 'low' ] as $priority ) {
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
	 * @since 3.0.0
	 **/
	public function get() {
		echo '<div class="wrap">';
		if ( isset( $this->trace ) ) {
			$icon = '<img style="width:30px;float:left;padding-right:8px;" src="' . Icons::get_base64( 'clock', '#ABCFF9', '#192783' ) . '" />';
			$name = ChannelTypes::$channel_names[ strtoupper( $this->trace['channel'] ) ] . '&nbsp;#' . $this->trace['id'];
			// phpcs:ignore
			echo '<h2>' . $icon . $name . '</h2>';
			settings_errors();
			echo '<form name="decalog_trace" method="post">';
			echo '<div id="dashboard-full-wrap">';
			echo '    <div id="dashboard-full" class="metabox-holder">';
			echo '        <div id="postbox-container-0" class="postbox-container" style="width:100%;margin-bottom: -10px;">';
			do_meta_boxes( self::$screen_id, 'full', null );
			echo '        </div>';
			echo '    </div>';
			echo '</div>';
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
			echo '<p>' . esc_html__( 'The trace or traces log you tried to access is out of your scope.', 'decalog' ) . '</p>';
			echo '<p>' . esc_html__( 'If you think this is an error, please contact the network administrator with these details:', 'decalog' );
			echo '<ul>';
			// phpcs:ignore
			echo '<li>' . sprintf( esc_html__( 'Traces log: %s', 'decalog' ), '<code>' . $this->logid . '</code>' ) . '</li>';
			// phpcs:ignore
			echo '<li>' . sprintf( esc_html__( 'Trace: %s', 'decalog' ), '<code>' . $this->traceid . '</code>' ) . '</li>';
			echo '</ul>';
			echo '</p>';
			$this->logger->warning( sprintf( 'Trying to access out of scope trace #%s from traces log {%s}.', $this->traceid, $this->logid ), 403 );
		}
		echo '</div>';
	}

	/**
	 * Print the single span visualization.
	 *
	 * @since 3.0.0
	 */
	public function get_span( $span, $level = 0, $start = 0, $duration = 0 ) {
		$result = '<div class="decalog-span-wrap">';
		// Span text
		$result .= '<div class="decalog-span-text">';
		$result .= str_pad( '', ( $level * 3 ) * 6, '&nbsp;' ) . '<strong>' . $span['resource'] . '</strong>' . ( 0 === $level ? '' : '&nbsp;&nbsp;' . $span['name'] );
		$result .= '</div>';
		// Span timeline
		$bblank = round( 100 * ( $span['start'] - $start ) / $duration, 3 );
		$lblank = round( 100 * ( $span['duration'] ) / $duration, 3 );
		$eblank = 100.0 - $bblank - $lblank;
		$tick   = round( 100 * 500000 / $duration, 3 );
		$color  = $this->colors['wordpress'];
		if ( array_key_exists( strtolower( $span['resource'] ), $this->colors ) ) {
			$color = $this->colors[ strtolower( $span['resource'] ) ];
		}
		$style = '';
		if ( 80 > $lblank ) {
			if ( 10 < $eblank ) {
				$style = 'style="margin-left:100%;"';
			}
		}
		$result .= '<div class="decalog-span-timeline" style="background-size: ' . $tick . '% 100%;">';
		$result .= '<div class="decalog-span-timeline-blank" style="width:' . $bblank . '%">';
		$result .= '</div>';
		$result .= '<div class="decalog-span-timeline-line" style="background-color:' . $color . ';width:' . $lblank . '%">';
		$result .= '<span class="decalog-span-timeline-text" ' . $style . '>' . (int) round( ( $span['duration'] / 1000 ), 0 ) . '&nbsp;ms</span>';
		$result .= '</div>';
		$result .= '<div class="decalog-span-timeline-blank" style="width:' . $eblank . '%;">';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '</div>';
		if ( $span['subspans'] && is_array( $span['subspans'] ) && 0 < count( $span['subspans'] ) ) {
			foreach ( $span['subspans'] as $s ) {
				$result .= $this->get_span( $s, $level + 1, $start, $duration );
			}
		}
		return $result;
	}

	/**
	 * Print the trace visualization.
	 *
	 * @since 3.0.0
	 */
	public function get_spans() {
		$result = '<div class="decalog-spans-wrap" style="width:100%">';
		if ( $this->trace['spans'] && is_array( $this->trace['spans'] ) && 0 < count( $this->trace['spans'] ) ) {
			foreach ( $this->trace['spans'] as $span ) {
				$result .= $this->get_span( $span, 0, $span['start'], $span['duration'] );
			}
		} else {
			$result .= 'NOTHING';
		}
		$result .= '</div>';
		$this->output_activity_block( $result );
	}

	/**
	 * Add footer scripts.
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
	 */
	public function add_metaboxes() {
		// Full column.
		add_meta_box( 'decalog-tspans', esc_html__( 'Spans', 'decalog' ), [ $this, 'get_spans' ], self::$screen_id, 'full' );
		// Left column.
		add_meta_box( 'decalog-tmain', esc_html__( 'Trace', 'decalog' ), [ $this, 'trace_widget' ], self::$screen_id, 'advanced' );
		// Right column.
		add_meta_box( 'decalog-twordpress', 'WordPress', [ $this, 'wordpress_widget' ], self::$screen_id, 'side' );
	}

	/**
	 * Print an activity block.
	 *
	 * @param   string $content The content of the block.
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
	 */
	private function get_icon( $icon_name, $background = '#F9F9F9' ) {
		return '<img style="width:18px;float:left;padding-right:6px;" src="' . Feather\Icons::get_base64( $icon_name, $background, '#9999BB' ) . '" />';
	}

	/**
	 * Get an external link markup.
	 *
	 * @param   string $url The url.
	 * @return  string  The link markup, ready to print.
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
	 */
	public function trace_widget() {
		// Trace type.
		$icon     = '<img style="width:18px;float:left;padding-right:6px;" src="' . Icons::get_base64( 'clock', '#ABCFF9', '#192783' ) . '" />';
		$level    = esc_html__( 'Trace', 'decalog' );
		$channel  = ChannelTypes::$channel_names[ strtoupper( $this->trace['channel'] ) ];
		$content  = '<span style="width:40%;cursor: default;float:left">' . $icon . $level . '</span>';
		$content .= '<span style="width:60%;cursor: default;">' . $this->get_icon( 'activity', 'none' ) . $channel . '</span>';
		$trace    = $this->get_section( $content );
		// Trace time.
		$time    = Date::get_date_from_mysql_utc( $this->trace['timestamp'], Timezone::network_get()->getName(), 'Y-m-d H:i:s' );
		$dif     = Date::get_positive_time_diff_from_mysql_utc( $this->trace['timestamp'] );
		$content = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'clock' ) . $time . '</span> <span style="color:silver">(' . $dif . ')</span>';
		$hour    = $this->get_section( $content );

		$this->output_activity_block( $trace . $hour );
	}

	/**
	 * Get content of the WordPress widget box.
	 *
	 * @since 3.0.0
	 */
	public function wordpress_widget() {
		// User detail.
		if ( 'anonymous' === $this->trace['user_name'] ) {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user' ) . esc_html__( 'Anonymous user', 'decalog' ) . '</span>' );
		} elseif ( 0 === strpos( $this->trace['user_name'], '{' ) ) {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user' ) . esc_html__( 'Pseudonymized user', 'decalog' ) . '</span>' );
		} elseif ( 0 !== (int) $this->trace['user_id'] ) {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user-check' ) . User::get_user_string( (int) $this->trace['user_id'] ) . '</span>' );
		} else {
			$user = $this->get_section( '<span style="width:100%;cursor: default;word-break: break-all;">' . $this->get_icon( 'user-x' ) . esc_html__( 'Deleted user', 'decalog' ) . '</span>' );
		}
		// Site detail.
		$content = '<span style="width:100%;cursor: default;">' . $this->get_icon( 'layout' ) . $this->trace['site_name'] . '</span>';
		$site    = $this->get_section( $content );
		$this->output_activity_block( $user . $site );
	}

}
