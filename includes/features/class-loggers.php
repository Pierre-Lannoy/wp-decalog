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
	 * The domain options handler.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $domains    The Adr_Sync_Options_Domains instance.
	 */
	private $domains = null;

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
		//$this->domains = new Adr_Sync_Options_Domains( ADRS_SLUG, ADRS_VERSION );
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

	/**
	 * Enumerates columns.
	 *
	 * @return      array   The columns.
	 * @since    1.0.0
	 */
	public function get_columns() {
		$columns = array(
			'name'       => __( 'Name', 'adr-sync' ),
			'repository' => __( 'GitHub repository', 'adr-sync' ),
			'term'       => __( 'Domain', 'adr-sync' ),
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
			'term' => array( 'term', false ),
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
		$data = [];
		//$data                  = $this->domains->get_list();
		usort( $data, array( $this, 'usort_reorder' ) );
		$this->items = $data;
	}

}
