<?php
/**
 * Loggers list
 *
 * Lists all available loggers.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\Plugin\Feature\Log;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Define the loggers list functionality.
 *
 * Lists all available loggers.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Loggers extends \WP_List_Table {

	/**
	 * The loggers options handler.
	 *
	 * @since    1.0.0
	 * @var      array    $loggers    The loggers list.
	 */
	private $loggers = [];

	/**
	 * The HandlerTypes instance.
	 *
	 * @since  1.0.0
	 * @var    HandlerTypes    $handler_types    The handlers types.
	 */
	private $handler_types;

	/**
	 * The ProcessorTypes instance.
	 *
	 * @since  1.0.0
	 * @var    HandlerTypes    $processor_types    The processors types.
	 */
	private $processor_types;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'logger',
				'plural'   => 'loggers',
				'ajax'     => true,
			]
		);
		global $wp_version;
		if ( version_compare( $wp_version, '4.2-z', '>=' ) && $this->compat_fields && is_array( $this->compat_fields ) ) {
			array_push( $this->compat_fields, 'all_items' );
		}
		$this->loggers = [];
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			$logger['uuid']  = $key;
			$this->loggers[] = $logger;
		}
		$this->handler_types   = new HandlerTypes();
		$this->processor_types = new ProcessorTypes();
	}

	/**
	 * Default column formatter.
	 *
	 * @param   array  $item   The current item.
	 * @param   string $column_name The current column name.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * "name" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_name( $item ) {
		$actions           = [];
		$edit              = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'form-edit',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
				],
				admin_url( 'admin.php' )
			)
		);
		$delete            = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'form-delete',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
				],
				admin_url( 'admin.php' )
			)
		);
		$pause             = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'pause',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce( 'decalog-logger-pause-' . $item['uuid'] ),
				],
				admin_url( 'admin.php' )
			)
		);
		$test              = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'test',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce( 'decalog-logger-test-' . $item['uuid'] ),
				],
				admin_url( 'admin.php' )
			)
		);
		$start             = esc_url(
			add_query_arg(
				[
					'page'   => 'decalog-settings',
					'action' => 'start',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce( 'decalog-logger-start-' . $item['uuid'] ),
				],
				admin_url( 'admin.php' )
			)
		);
		$view              = esc_url(
			add_query_arg(
				[
					'page'      => 'decalog-viewer',
					'logger_id' => $item['uuid'],
				],
				admin_url( 'admin.php' )
			)
		);
		$handler           = $this->handler_types->get( $item['handler'] );
		$icon              = '<img style="width:38px;float:left;padding-right:6px;" src="' . $handler['icon'] . '" />';
		if ( 'system' !== $handler['class'] ) {
			$actions['edit']   = sprintf( '<a href="%s">' . esc_html__( 'Edit', 'decalog' ) . '</a>', $edit );
			$actions['delete'] = sprintf( '<a href="%s">' . esc_html__( 'Remove', 'decalog' ) . '</a>', $delete );
			if ( $item['running'] ) {
				$actions['pause'] = sprintf( '<a href="%s">' . esc_html__( 'Pause', 'decalog' ) . '</a>', $pause );
			} else {
				$actions['start'] = sprintf( '<a href="%s">' . esc_html__( 'Start', 'decalog' ) . '</a>', $start );
			}
			if ( 'WordpressHandler' === $handler['id'] ) {
				$actions['view'] = sprintf( '<a href="%s">' . esc_html__( 'View', 'decalog' ) . '</a>', $view );
			}
		}
		if ( $item['running'] && 'metrics' !== ( $this->handler_types->get( $item['handler'] ) )['class'] ) {
			$actions['test'] = sprintf( '<a href="%s">' . esc_html__( 'Send Test', 'decalog' ) . '</a>', $test );
		}
		return $icon . '&nbsp;' . sprintf( '<a href="%1$s">%2$s</a><br /><span style="color:silver">&nbsp;%3$s</span>%4$s', $edit, $item['name'], $handler['name'], $this->row_actions( $actions ) );
	}

	/**
	 * "status" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_status( $item ) {
		$running = '<span style="vertical-align: middle;font-size:10px;padding:2px 6px;display: inline-block;text-transform:uppercase;font-weight: bold;background-color:#4AA03E;color:#F9F9F9;border-radius:2px;cursor: default;word-break: break-word;">▶&nbsp;' . esc_html__( 'Running', 'decalog' ) . '</span>';
		$paused  = '<span style="vertical-align: middle;font-size:10px;padding:2px 6px;display: inline-block;text-transform:uppercase;font-weight: bold;background-color:#E0E0E0;color:#AAAAAA;border-radius:2px;cursor: default;word-break: break-word;">❙❙&nbsp;' . esc_html__( 'Paused', 'decalog' ) . '</span>';
		return ( $item['running'] ? $running : $paused );
	}

	/**
	 * "details" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_details( $item ) {
		$result = '';
		$class  = ( $this->handler_types->get( $item['handler'] ) )['class'];
		$list[] = '<span style="vertical-align: middle;font-size:9px;padding:2px 6px;text-transform:uppercase;font-weight: bold;background-color:#9999BB;color:#F9F9F9;border-radius:2px;cursor: default;word-break: break-word;">' . esc_html__( 'Standard', 'decalog' ) . '</span>';
		foreach ( $item['processors'] as $processor ) {
			$list[] = '<span style="vertical-align: middle;font-size:9px;padding:2px 6px;text-transform:uppercase;font-weight: bold;background-color:#9999BB;color:#F9F9F9;border-radius:2px;cursor: default;word-break: break-word;">' . str_replace( ' ', '&nbsp;', $this->processor_types->get( $processor )['name'] ) . '</span>';
		}
		if ( in_array( $class, [ 'metrics', 'tracing' ], true ) ) {
			if ( isset( $item['configuration']['sampling'] ) ) {
				$sampling = (float) ( ( (int) $item['configuration']['sampling'] ) / 10 );
				if ( 1.0 <= $sampling ) {
					$sampling = (string) ( ( (int) round( $sampling, 0 ) ) ) . '%';
				} else {
					$sampling = (string) ( ( (int) round( $sampling * 10, 0 ) ) ) . '‰';
				}
				$result .= '<span style="margin-bottom: 6px;vertical-align: middle;font-size:10px;display: inline-block;text-transform:uppercase;font-weight: 900;background-color:#FFFFFF;color:#5F656A;border-radius:2px;border: 1px solid #9999BB;border-radius:2px;cursor: default;word-break: break-word;">&nbsp;&nbsp;&nbsp;' . $sampling . '&nbsp;&nbsp;&nbsp;</span>';
			}
			if ( isset( $item['configuration']['profile'] ) ) {
				switch ( $item['configuration']['profile'] ) {
					case 550:
						$level = esc_html__( 'Forced', 'decalog' ) . ' / ' . esc_html__( 'Development', 'decalog' );
						break;
					case 600:
						$level = esc_html__( 'Forced', 'decalog' ) . ' / ' . esc_html__( 'Production', 'decalog' );
						break;
					default:
						if ( 'production' === Environment::stage() ) {
							$level = esc_html__( 'Automatic', 'decalog' ) . ' / ' . esc_html__( 'Production', 'decalog' );
						} else {
							$level = esc_html__( 'Automatic', 'decalog' ) . ' / ' . esc_html__( 'Development', 'decalog' );
						}
				}
				$result .= '<br/><span style="vertical-align: middle;font-size:9px;padding:2px 6px;text-transform:uppercase;font-weight: bold;background-color:#9999BB;color:#F9F9F9;border-radius:2px;cursor: default;word-break: break-word;">' . str_replace( ' ', '&nbsp;', $level ) . '</span>';
			}
		}
		if ( in_array( $class, [ 'alerting', 'logging', 'debugging', 'analytics' ], true ) ) {
			$level   = strtolower( Log::level_name( $item['level'] ) );
			$result .= '<span style="margin-bottom: 6px;vertical-align: middle;font-size:10px;display: inline-block;text-transform:uppercase;font-weight: 900;background-color:' . EventTypes::$levels_colors[ $level ][0] . ';color:' . EventTypes::$levels_colors[ $level ][1] . ';border-radius:2px;border: 1px solid ' . EventTypes::$levels_colors[ $level ][1] . ';cursor: default;word-break: break-word;">&nbsp;&nbsp;&nbsp;' . $level . '&nbsp;&nbsp;&nbsp;</span>';
			$result .= '<br/>' . implode( ' ', $list );
		}
		return $result;
	}

	/**
	 * "minimal level" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_type( $item ) {
		return $this->handler_types->get_class_name( ( $this->handler_types->get( $item['handler'] ) )['class'] );
	}

	/**
	 * Enumerates columns.
	 *
	 * @return      array   The columns.
	 * @since    1.0.0
	 */
	public function get_columns() {
		$columns = [
			'name'    => esc_html__( 'Logger', 'decalog' ),
			'status'  => esc_html__( 'Status', 'decalog' ),
			'type'    => esc_html__( 'Type', 'decalog' ),
			'details' => esc_html__( 'Settings', 'decalog' ),
		];
		return $columns;
	}

	/**
	 * Enumerates hidden columns.
	 *
	 * @return      array   The hidden columns.
	 * @since    1.0.0
	 */
	protected function get_hidden_columns() {
		return [];
	}

	/**
	 * Enumerates sortable columns.
	 *
	 * @return      array   The sortable columns.
	 * @since    1.0.0
	 */
	protected function get_sortable_columns() {
		$sortable_columns = [
			'name' => [ 'name', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Enumerates bulk actions.
	 *
	 * @return      array   The bulk actions.
	 * @since    1.0.0
	 */
	public function get_bulk_actions() {
		return [];
	}

	/**
	 * Prepares the list to be displayed.
	 *
	 * @since    1.0.0
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$data                  = $this->loggers;
		usort(
			$data,
			function ( $a, $b ) {
				$orderby = ( ! is_null( filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING ) ) ) ? filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING ) : 'name';
				$order   = ( ! is_null( filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING ) ) ) ? filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING ) : 'desc';
				$result  = strcmp( strtolower( $a[ $orderby ] ), strtolower( $b[ $orderby ] ) );
				return ( 'asc' === $order ) ? -$result : $result;
			}
		);
		$this->items = $data;
	}

}
