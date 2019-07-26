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

use Decalog\System\Option;
use Decalog\Log;

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
	 * @access   private
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
			array(
				'singular' => 'logger',
				'plural'   => 'loggers',
				'ajax'     => true,
			)
		);
		global $wp_version;
		if ( version_compare( $wp_version, '4.2-z', '>=' ) && $this->compat_fields && is_array( $this->compat_fields ) ) {
			array_push( $this->compat_fields, 'all_items' );
		}
		$this->loggers = [];
		foreach ( Option::get( 'loggers' ) as $key => $logger ) {
			$logger['uuid']  = $key;
			$this->loggers[] = $logger;
		}
		$this->handler_types   = new HandlerTypes();
		$this->processor_types = new ProcessorTypes();
	}

	/**
	 * Default column formatter.
	 *
	 * @return      string   The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	protected function column_name( $item ) {
		$edit   = esc_url(
			add_query_arg(
				array(
					'page'   => 'decalog-settings',
					'action' => 'form-edit',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
				),
				admin_url( 'options-general.php' )
			)
		);
		$delete = esc_url(
			add_query_arg(
				array(
					'page'   => 'decalog-settings',
					'action' => 'form-delete',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
				),
				admin_url( 'options-general.php' )
			)
		);
		$pause  = esc_url(
			add_query_arg(
				array(
					'page'   => 'decalog-settings',
					'action' => 'pause',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce('decalog-logger-pause-' . $item['uuid']),
				),
				admin_url( 'options-general.php' )
			)
		);
		$start  = esc_url(
			add_query_arg(
				array(
					'page'   => 'decalog-settings',
					'action' => 'start',
					'tab'    => 'loggers',
					'uuid'   => $item['uuid'],
					'nonce'  => wp_create_nonce('decalog-logger-start-' . $item['uuid']),
				),
				admin_url( 'options-general.php' )
			)
		);
		$view  = esc_url(
			add_query_arg(
				array(
					'page'   => 'decalog-viewer',
					'logger_id'   => $item['uuid'],
				),
				admin_url( 'tools.php' )
			)
		);

		$handler           = $this->handler_types->get( $item['handler'] );
		$icon              = '<img style="width:34px;float:left;padding-right:6px;" src="' . $handler['icon'] . '" />';
		$type              = $handler['name'] . ' - <strong>' . ( $item['running'] ? __( 'running', 'decalog' ) : __( 'paused', 'decalog' ) ) . '</strong>';
		$actions['edit']   = sprintf( '<a href="%s">' . __( 'Edit', 'decalog' ) . '</a>', $edit );
		$actions['delete'] = sprintf( '<a href="%s">' . __( 'Remove', 'decalog' ) . '</a>', $delete );
		if ( $item['running'] ) {
			$actions['pause'] = sprintf( '<a href="%s">' . __( 'Pause', 'decalog' ) . '</a>', $pause );
		} else {
			$actions['start'] = sprintf( '<a href="%s">' . __( 'Start', 'decalog' ) . '</a>', $start );
		}
		if ('WordpressHandler' === $handler['id']) {
			$actions['view'] = sprintf( '<a href="%s">' . __( 'View', 'decalog' ) . '</a>', $view );
		}
		return $icon . '&nbsp;' . sprintf( '<a href="%1$s">%2$s</a><br /><span style="color:silver">&nbsp;%3$s</span>%4$s', $edit, $item['name'], $type, $this->row_actions( $actions ) );
	}

	protected function column_details( $item ) {
		$list = [ __( 'Standard', 'decalog' ) ];
		foreach ( $item['processors'] as $processor ) {
			$list[] = $this->processor_types->get( $processor )['name'];
		}
		return implode( ', ', $list );
	}

	protected function column_level( $item ) {
		$name = Log::level_name( $item['level'] );
		$list = [ __( 'Standard', 'decalog' ) ];
		foreach ( $item['processors'] as $processor ) {
			$list[] = $this->processor_types->get( $processor )['name'];
		}
		return $name;
	}

	/**
	 * Enumerates columns.
	 *
	 * @return      array   The columns.
	 * @since    1.0.0
	 */
	public function get_columns() {
		$columns = array(
			'name'    => __( 'Logger', 'decalog' ),
			'level'   => __( 'Minimal level', 'decalog' ),
			'details' => __( 'Reported details', 'decalog' ),
		);
		return $columns;
	}

	/**
	 * Enumerates hidden columns.
	 *
	 * @return      array   The hidden columns.
	 * @since    1.0.0
	 */
	protected function get_hidden_columns() {
		return array();
	}

	/**
	 * Enumerates sortable columns.
	 *
	 * @return      array   The sortable columns.
	 * @since    1.0.0
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true ),
		);
		return $sortable_columns;
	}

	/**
	 * Enumerates bulk actions.
	 *
	 * @return      array   The bulk actions.
	 * @since    1.0.0
	 */
	public function get_bulk_actions() {
		return array();
	}

	public function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'name';
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';
		$result  = strcmp( strtolower( $a[ $orderby ] ), strtolower( $b[ $orderby ] ) );
		return ( $order === 'asc' ) ? $result : -$result;
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
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$data                  = $this->loggers;
		usort( $data, array( $this, 'usort_reorder' ) );
		$this->items = $data;
	}

}
