<?php
/**
 * DecaLog logger read handler
 *
 * Handles all logger reads.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Decalog;

use Decalog\Plugin\Feature\Log;
use Decalog\System\Role;
use Decalog\Plugin\Feature\DLogger;

/**
 * Define the item operations functionality.
 *
 * Handles all item operations.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class LoggerRoute extends \WP_REST_Controller {

	/**
	 * The internal logger.
	 *
	 * @since  1.0.0
	 * @var    DLogger    $logger    The plugin rest logger.
	 */
	protected $logger;

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since  2.0.0
	 */
	public function register_routes() {
		$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$this->register_route_livelog();
	}

	/**
	 * Register the routes for livelog.
	 *
	 * @since  2.0.0
	 */
	public function register_route_livelog() {
		register_rest_route(
			DECALOG_REST_NAMESPACE,
			'livelog',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_livelog' ],
					'permission_callback' => [ $this, 'get_livelog_permissions_check' ],
					'args'                => array_merge( $this->arg_schema_livelog() ),
					'schema'              => [ $this, 'get_schema' ],
				],
			]
		);
	}

	/**
	 * Get the query params for livelog.
	 *
	 * @return array    The schema fragment.
	 * @since  2.0.0
	 */
	public function arg_schema_livelog() {
		return [
			'index' => [
				'description'       => 'The index to start from.',
				'type'              => 'string',
				'required'          => false,
				'default'           => '0',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'level' => [
				'description'       => 'The minimum level to get.',
				'type'              => 'string',
				'enum'              => [ 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency' ],
				'required'          => false,
				'default'           => 'info',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Check if a given request has access to get livelogs
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function get_livelog_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			$this->logger->warning( 'Unauthenticated API call.', 401 );
			return new \WP_Error( 'rest_not_logged_in', 'You must be logged in to access live logs.', [ 'status' => 401 ] );
		}
		return Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type();
	}

	/**
	 * Get a list of items
	 *
	 * @param       integer     $page           The page number.
	 * @param       integer     $per_page       The number of items per page.
	 * @param       integer     $owner          The user ID filter.
	 * @param       integer     $site           The site ID filter.
	 * @param       string      $search         The search string.
	 * @param       string      $order          The order selector.
	 * @return      array       The list of items.
	 * @since  2.0.0
	 */
	protected function get_list( $page, $per_page, $owner, $site, $search, $order ) {
		return [];
	}

	/**
	 * Get a collection of items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_livelog( $request ) {
		if ( '0' === $request['index'] ) {

		}






		return new \WP_REST_Response( ['status'=>'OK'], 200 );



		$list = $this->get_list( $request['page'], $request['per_page'], $request['owner'], $request['site'], $request['search'] ? $request['search'] : '', $request['order'] );
		$data = [];
		foreach ( $list['data'] as $item ) {
			if ( 'details' === $request['view'] ) {
				$data[] = $item->as_details();
			} else {
				$data[] = $item->as_raw();
			}
		}
		if ( 'details' === $request['view'] ) {
			$result = [
				'has_previous' => $list['previous'] ? 1 : 0,
				'has_next'     => $list['next'] ? 1 : 0,
				'total_pages'  => $list['pages'],
				'current_page' => $list['page'],
				'total_items'  => $list['items'],
				'count_items'  => $list['count'],
				'items'        => $data,
			];
		} else {
			$result = $data;
		}
		return new \WP_REST_Response( $result, 200 );

	}

}