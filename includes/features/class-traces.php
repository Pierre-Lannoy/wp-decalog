<?php
/**
 * Events list
 *
 * Lists all events.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Logger;
use Decalog\Storage\AbstractStorage;
use Decalog\System\Date;
use Decalog\System\Option;
use Decalog\System\Role;
use Decalog\System\Timezone;
use Feather\Icons;
use Decalog\System\GeoIP;
use Decalog\System\Hash;
use Decalog\Storage\DBTraceStorage;
use Decalog\Storage\APCuStorage;


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Define the events list functionality.
 *
 * Lists all events.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class Traces extends \WP_List_Table {

	/**
	 * The available events logs.
	 *
	 * @since    3.0.0
	 * @var      array    $logs    The loggers list.
	 */
	private static $logs = [];

	/**
	 * The storage engine.
	 *
	 * @since  3.0.0
	 * @var    \Decalog\Storage\AbstractStorage    $storage    The storage engine.
	 */
	private $storage = null;

	/**
	 * The columns always shown.
	 *
	 * @since    3.0.0
	 * @var      array    $standard_columns    The columns always shown.
	 */
	private static $standard_columns = [];

	/**
	 * The columns which may be shown.
	 *
	 * @since    3.0.0
	 * @var      array    $extra_columns    The columns which may be shown.
	 */
	private static $extra_columns = [];

	/**
	 * The columns which must be shown to the current user.
	 *
	 * @since    3.0.0
	 * @var      array    $extra_columns    The columns which must be shown to the current user.
	 */
	private static $user_columns = [];

	/**
	 * The order of the columns.
	 *
	 * @since    3.0.0
	 * @var      array    $columns_order    The order of the columns.
	 */
	private static $columns_order = [];

	/**
	 * The events types icons.
	 *
	 * @since    3.0.0
	 * @var      array    $icons    The icons list.
	 */
	private $icons = [];

	/**
	 * The number of lines to display.
	 *
	 * @since    3.0.0
	 * @var      integer    $limit    The number of lines to display.
	 */
	private $limit = 25;

	/**
	 * The logger ID.
	 *
	 * @since    3.0.0
	 * @var      string    $logger    The logger ID.
	 */
	private $logger = null;

	/**
	 * The main filter.
	 *
	 * @since    3.0.0
	 * @var      array    $filters    The main filter.
	 */
	private $filters = [];

	/**
	 * Forces the site_id filter if set.
	 *
	 * @since    3.0.0
	 * @var      integer    $force_siteid    Forces the site_id filter if set.
	 */
	private $force_siteid = null;

	/**
	 * The token of the current session.
	 *
	 * @since    2.4.0
	 * @var      string    $selftoken    The token of the current session.
	 */
	private $selftoken = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'trace',
				'plural'   => 'traces',
				'ajax'     => true,
			]
		);
		$this->selftoken = Hash::simple_hash( wp_get_session_token(), false );
	}

	/**
	 * Default column formatter.
	 *
	 * @param   object $item   The current item to render.
	 * @param   string $column_name    The name of the current rendered column.
	 * @return  string  The cell formatted, ready to print.
	 * @since   3.0.0
	 */
	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * "event" column formatter.
	 *
	 * @param   object $item   The current item to render.
	 * @return  string  The cell formatted, ready to print.
	 * @since   3.0.0
	 */
	protected function column_trace( $item ) {
		$args            = [];
		$args['page']    = 'decalog-tviewer';
		$args['logid']   = $this->logger;
		$args['traceid'] = $item['id'];
		$url             = add_query_arg( $args, admin_url( 'admin.php' ) );
		$icon            = '<img style="width:18px;float:left;padding-right:6px;" src="' . Icons::get_base64( 'clock', '#ABCFF9', '#192783' ) . '" />';
		$name            = '<a href="' . $url . '">' . ChannelTypes::$channel_names[ strtoupper( $item['channel'] ) ] . '</a>' . $this->get_filter( 'channel', $item['channel'] ) . '&nbsp;<span style="color:silver">#' . $item['id'] . '</span>';
		/* translators: as in the sentence "TraceID xxxx" */
		$code   = '<br /><span style="color:silver">' . sprintf( esc_html__( 'TraceID %s', 'decalog' ), $item['trace_id'] ) . '</span>';
		$result = $icon . $name . $code;
		return $result;
	}

	/**
	 * "component" column formatter.
	 *
	 * @param   object $item   The current item to render.
	 * @return  string  The cell formatted, ready to print.
	 * @since   3.0.0
	 */
	protected function column_duration( $item ) {
		$name   = $item['duration'] . ' ms';
		$result = $name . '<br /><span style="color:silver">' . sprintf( esc_html__( '%d spans', 'decalog' ), $item['scount'] ) . '</span>';
		return $result;
	}

	/**
	 * "time" column formatter.
	 *
	 * @param   object $item   The current item to render.
	 * @return  string  The cell formatted, ready to print.
	 * @since   3.0.0
	 */
	protected function column_time( $item ) {
		$result  = Date::get_date_from_mysql_utc( $item['timestamp'], Timezone::network_get()->getName(), 'Y-m-d H:i:s' );
		$result .= '<br /><span style="color:silver">' . Date::get_positive_time_diff_from_mysql_utc( $item['timestamp'] ) . '</span>';
		return $result;
	}

	/**
	 * "site" column formatter.
	 *
	 * @param   object $item   The current item to render.
	 * @return  string  The cell formatted, ready to print.
	 * @since   3.0.0
	 */
	protected function column_site( $item ) {
		$name = $item['site_name'] . $this->get_filter( 'site_id', $item['site_id'] );
		// phpcs:ignore
		$result = $name . '<br /><span style="color:silver">' . sprintf(esc_html__('Site ID %s', 'decalog'), $item['site_id']) . '</span>';
		return $result;
	}

	/**
	 * "user" column formatter.
	 *
	 * @param   object $item   The current item to render.
	 * @return  string  The cell formatted, ready to print.
	 * @since   3.0.0
	 */
	protected function column_user( $item ) {
		$user = $item['user_name'];
		if ( 'anonymous' === $user ) {
			$user = '<em>' . esc_html__( 'Anonymous user', 'decalog' ) . '</em>';
		}
		$id = '';
		$se = '';
		if ( 0 === strpos( $item['user_name'], '{' ) ) {
			$user = '<em>' . esc_html__( 'Pseudonymized user', 'decalog' ) . '</em>';
		} elseif ( 0 !== (int) $item['user_id'] ) {
			// phpcs:ignore
			$id = sprintf( esc_html__( ' (UID %s)', 'decalog' ), $item[ 'user_id' ] );
			$se = '<br /><span style="color:silver">' . sprintf( esc_html__( 'Session #%s…%s', 'decalog' ), substr( $item[ 'user_session' ], 0, 2 ), substr( $item[ 'user_session' ], -2 ) ) . '</span>';
		}
		$result = $user . $id . $this->get_filter( 'user_id', $item['user_id'] ) . $se . ( '' !== $se ? $this->get_pose_shortcut( (int) $item['user_id'] ) : '' ) . ( '' !== $se ? $this->get_filter( 'user_session', $item['user_session'] ) : '' );
		return '<span' . ( ( $item['user_session'] ?? '') === $this->selftoken ? ' class="decalog-selftoken"' : '' ) . '>' . $result . '</span>';
	}

	/**
	 * Initialize the list view.
	 *
	 * @return  array   The columns to render.
	 * @since 3.0.0
	 */
	public function get_columns() {
		$columns = [];
		foreach ( self::$columns_order as $column ) {
			if ( array_key_exists( $column, self::$standard_columns ) ) {
				$columns[ $column ] = self::$standard_columns[ $column ];
				// phpcs:ignore
			} elseif ( array_key_exists( $column, self::$extra_columns ) && in_array( $column, self::$user_columns, true ) ) {
				$columns[ $column ] = self::$extra_columns[ $column ];
			}
		}
		return $columns;
	}

	/**
	 * Initialize storage.
	 *
	 * @since 3.0.0
	 */
	protected function init_storage() {
		$this->storage = null;
		if ( $this->logger ) {
			$loggers = Option::network_get( 'loggers' );
			if ( array_key_exists( $this->logger, $loggers ) ) {
				$bucket_name = 'decalog_' . str_replace( '-', '', $this->logger );
				switch ( $loggers[ $this->logger ]['configuration']['constant-storage'] ) {
					case 'apcu':
						$this->storage = new APCuStorage( $bucket_name );
						break;
					default:
						$this->storage = new DBTraceStorage( $bucket_name );
				}
			}
		}
	}

	/**
	 * Initialize values and filter.
	 *
	 * @since 3.0.0
	 */
	protected function init_values() {
		$this->limit = filter_input( INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $this->limit ) {
			$this->limit = 25;
		}
		$this->force_siteid = null;
		$this->logger       = filter_input( INPUT_GET, 'logger_id', FILTER_SANITIZE_STRING );
		if ( $this->logger ) {
			$this->set_level_access();
		} else {
			$this->set_first_available();
		}
		$this->init_storage();
		$this->filters = [];
		$level         = filter_input( INPUT_GET, 'level', FILTER_SANITIZE_STRING );
		if ( $level && array_key_exists( strtolower( $level ), EventTypes::$levels ) && 'debug' !== strtolower( $level ) ) {
			$this->filters['level'] = strtolower( $level );
		}
		foreach ( [ 'channel', 'site_id', 'user_id', 'user_session' ] as $f ) {
			$v = filter_input( INPUT_GET, $f, FILTER_SANITIZE_STRING );
			if ( $v ) {
				$this->filters[ $f ] = strtolower( $v );
			}
		}
		if ( $this->force_siteid ) {
			$this->filters['site_id'] = $this->force_siteid;
		}
	}

	/**
	 * Get the filter image.
	 *
	 * @param   string  $filter     The filter name.
	 * @param   string  $value      The filter value.
	 * @param   boolean $soft       Optional. The image must be softened.
	 * @return  string  The filter image, ready to print.
	 * @since   3.0.0
	 */
	protected function get_filter( $filter, $value, $soft = false ) {
		$filters = $this->filters;
		if ( array_key_exists( $filter, $this->filters ) ) {
			unset( $this->filters[ $filter ] );
			$url    = $this->get_page_url();
			$alt    = esc_html__( 'Remove this filter', 'decalog' );
			$fill   = '#9999FF';
			$stroke = '#0000AA';
		} else {
			$this->filters[ $filter ] = $value;
			$url                      = $this->get_page_url();
			$alt                      = esc_html__( 'Add as filter', 'decalog' );
			$fill                     = 'none';
			if ( $soft ) {
				$stroke = '#C0C0FF';
			} else {
				$stroke = '#3333AA';
			}
		}
		$this->filters = $filters;
		return '&nbsp;<a href="' . $url . '"><img title="' . $alt . '" style="width:11px;vertical-align:baseline;" src="' . Icons::get_base64( 'filter', $fill, $stroke ) . '" /></a>';
	}

	/**
	 * Get a shortcut to Sessions (if running).
	 *
	 * @param   string  $uid       The user id.
	 * @return  string  The shortcut image, ready to print.
	 * @since   2.4.0
	 */
	protected function get_pose_shortcut( $uid ) {
		if ( 0 === $uid || ! class_exists( 'POSessions\Plugin\Core' ) ) {
			return '';
		}
		$url    = esc_url( admin_url( 'admin.php?page=pose-manager&id=' ) . $uid );
		$alt    = esc_html__( 'See all sessions', 'decalog' );
		$fill   = '#C0C0FF';
		$stroke = '#3333AA';
		return '&nbsp;<a target="_blank" href="' . $url . '"><img title="' . $alt . '" style="width:11px;vertical-align:baseline;" src="' . Icons::get_base64( 'users', $fill, $stroke ) . '" /></a>';
	}

	/**
	 * Initialize the list view.
	 *
	 * @since 3.0.0
	 */
	public function prepare_items() {
		$this->init_values();
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$current_page          = $this->get_pagenum();
		$total_items           = $this->get_count();
		$this->items           = $this->get_list( ( $current_page - 1 ) * $this->limit, $this->limit );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $this->limit,
				'total_pages' => ceil( $total_items / $this->limit ),
			]
		);
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which Position of extra control.
	 * @since 3.0.0
	 */
	protected function display_tablenav( $which ) {
		echo '<div class="tablenav ' . esc_attr( $which ) . '">';
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		echo '<br class="clear" />';
		echo '</div>';
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which Position of extra control.
	 * @since 3.0.0
	 */
	public function extra_tablenav( $which ) {
		$list = $this;
		$args = compact( 'list' );
		foreach ( $args as $key => $val ) {
			$$key = $val;
		}
		if ( 'top' === $which ) {
			include DECALOG_ADMIN_DIR . 'partials/decalog-admin-view-traces-top.php';
		}
		if ( 'bottom' === $which ) {
			include DECALOG_ADMIN_DIR . 'partials/decalog-admin-view-traces-bottom.php';
		}
	}

	/**
	 * Get the page url with args.
	 *
	 * @return  string  The url.
	 * @since 3.0.0
	 */
	public function get_page_url() {
		$args              = [];
		$args['page']      = 'decalog-tviewer';
		$args['logger_id'] = $this->logger;
		if ( count( $this->filters ) > 0 ) {
			foreach ( $this->filters as $key => $filter ) {
				if ( '' !== $filter ) {
					$args[ $key ] = $filter;
				}
			}
		}
		if ( 25 !== $this->limit ) {
			$args['limit'] = $this->limit;
		}
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );
		return $url;
	}

	/**
	 * Get the available events logs.
	 *
	 * @return  array   The list of available events logs.
	 * @since    3.0.0
	 */
	public function get_loggers() {
		return self::$logs;
	}

	/**
	 * Get the available events logs.
	 *
	 * @return  array   The list of available events logs.
	 * @since    3.0.0
	 */
	public static function get() {
		return self::$logs;
	}

	/**
	 * Get the current events log id.
	 *
	 * @return  string   The current events log id.
	 * @since    3.0.0
	 */
	public function get_current_Log_id() {
		return $this->logger;
	}

	/**
	 * Get available lines breakdowns.
	 *
	 * @since 3.0.0
	 */
	public function get_line_number_select() {
		$_disp  = [ 25, 50, 100, 250, 500 ];
		$result = [];
		foreach ( $_disp as $d ) {
			$l          = [];
			$l['value'] = $d;
			// phpcs:ignore
			$l['text']     = sprintf( esc_html__( 'Show %d lines per page', 'decalog' ), $d );
			$l['selected'] = ( $d === (int) $this->limit ? 'selected="selected" ' : '' );
			$result[]      = $l;
		}
		return $result;
	}

	/**
	 * Set the level access to an events log.
	 *
	 * @since    3.0.0
	 */
	private function set_level_access() {
		$this->force_siteid = null;
		$id                 = $this->logger;
		$this->logger       = null;
		foreach ( self::$logs as $log ) {
			if ( $id === $log['id'] ) {
				$this->logger = $id;
				if ( array_key_exists( 'limit', $log ) ) {
					$this->force_siteid = $log['limit'];
				}
			}
		}
	}

	/**
	 * Set the level access to an events log.
	 *
	 * @since    3.0.0
	 */
	private function set_first_available() {
		$this->force_siteid = null;
		$this->logger       = null;
		foreach ( self::$logs as $log ) {
			if ( array_key_exists( 'limit', $log ) ) {
				$this->force_siteid = $log['limit'];
			}
			$this->logger = $log['id'];
			break;
		}
	}

	/**
	 * Get list of logged errors.
	 *
	 * @param integer $offset The offset to record.
	 * @param integer $rowcount Optional. The number of rows to return.
	 * @return array An array containing the filtered logged errors.
	 * @since 3.0.0
	 */
	protected function get_list( $offset = null, $rowcount = null ) {
		return ( $this->storage ? $this->storage->get_list( $this->filters, $offset, $rowcount ) : [] );
	}

	/**
	 * Count logged errors.
	 *
	 * @return integer The count of the filtered logged errors.
	 * @since 3.0.0
	 */
	protected function get_count() {
		return ( $this->storage ? $this->storage->get_count( $this->filters ) : 0 );
	}

	/**
	 * Initialize the meta class and set its columns properties.
	 *
	 * @since    3.0.0
	 */
	private static function load_columns() {
		self::$standard_columns             = [];
		self::$standard_columns['trace']    = esc_html__( 'Trace', 'decalog' );
		self::$standard_columns['duration'] = esc_html__( 'Duration', 'decalog' );
		self::$standard_columns['time']     = esc_html__( 'Time', 'decalog' );
		self::$extra_columns                = [];
		self::$extra_columns['site']        = esc_html__( 'Site', 'decalog' );
		self::$extra_columns['user']        = esc_html__( 'User', 'decalog' );
		self::$columns_order                = [ 'trace', 'duration', 'time', 'site', 'user' ];
		self::$user_columns                 = [];
		foreach ( self::$extra_columns as $key => $extra_column ) {
			if ( 'site' !== $key || ( 'site' === $key && is_multisite() ) ) {
				self::$user_columns[] = $key;
			}
		}
	}

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		self::$logs = [];
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			if ( 'WordpressTracingHandler' === $logger['handler'] ) {
				if ( array_key_exists( 'configuration', $logger ) ) {
					if ( array_key_exists( 'local', $logger['configuration'] ) ) {
						$local = $logger['configuration']['local'];
					} else {
						$local = false;
					}
					if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || ( Role::LOCAL_ADMIN === Role::admin_type() && $local ) || Role::override_privileges() ) {
						$log = [
							'name'    => $logger['name'],
							'running' => $logger['running'],
							'id'      => $key,
						];
						if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
							$log['limit'] = get_current_blog_id();
						}
						self::$logs[] = $log;
					}
				}
			}
		}
		uasort(
			self::$logs,
			function ( $a, $b ) {
				if ( $a['running'] === $b['running'] ) {
					return strcasecmp( str_replace( ' ', '', $a['name'] ), str_replace( ' ', '', $b['name'] ) );
				} return $a['running'] ? -1 : 1;
			}
		);
		self::load_columns();
	}

	/**
	 * Get the number of available logs.
	 *
	 * @return  integer     The number of logs.
	 * @since    3.0.0
	 */
	public static function loggers_count() {
		return count( self::$logs );
	}
}

Traces::init();
