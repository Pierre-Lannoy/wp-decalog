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
use Decalog\Plugin\Feature\Wpcli;
use Decalog\Handler\SharedMemoryHandler;

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
	 * @since  2.0.0
	 * @var    DLogger    $logger    The plugin rest logger.
	 */
	protected $logger;

	/**
	 * The acceptable levels.
	 *
	 * @since  2.0.0
	 * @var    array    $levels    The acceptable levels.
	 */
	protected $levels = [ 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency' ];

	/**
	 * The acceptable modes.
	 *
	 * @since  2.0.0
	 * @var    array    $modes    The acceptable modes.
	 */
	protected $modes =  [ 'wp', 'http', 'php' ];

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
				'enum'              => $this->levels,
				'required'          => false,
				'default'           => 'info',
				'sanitize_callback' => [ $this, 'sanitize_level' ],
			],
			'mode'  => [
				'description'       => 'The details shown.',
				'type'              => 'string',
				'enum'              => $this->modes,
				'required'          => false,
				'default'           => 'wp',
				'sanitize_callback' => [ $this, 'sanitize_mode' ],
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
	 * Sanitization callback for level.
	 *
	 * @param   mixed             $value      Value of the arg.
	 * @param   \WP_REST_Request  $request    Current request object.
	 * @param   string            $param      Name of the arg.
	 * @return  string  The level sanitized.
	 * @since  2.0.0
	 */
	public function sanitize_level( $value, $request = null, $param = null ) {
		$result = 'info';
		if ( in_array( (string) $value, $this->levels, true ) ) {
			$result = (string) $value;
		}
		return $result;
	}

	/**
	 * Sanitization callback for mode.
	 *
	 * @param   mixed             $value      Value of the arg.
	 * @param   \WP_REST_Request  $request    Current request object.
	 * @param   string            $param      Name of the arg.
	 * @return  string  The mode sanitized.
	 * @since  2.0.0
	 */
	public function sanitize_mode( $value, $request = null, $param = null ) {
		$result = 'wp';
		if ( in_array( (string) $value, $this->levels, true ) ) {
			$result = (string) $value;
		}
		return $result;
	}

	/**
	 * Get a collection of items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_livelog( $request ) {
		if ( '0' === $request['index'] ) {
			$index = array_key_last( SharedMemoryHandler::read() );
			if ( ! isset( $index ) ) {
				$index = '0';
			}
			$records = [];
			$this->logger->notice( 'Live console launched.' );
		} else {
			$records = Wpcli::records_format( Wpcli::records_filter( SharedMemoryHandler::read(), [ 'level' => $request['level'] ], $request['index'] ), $request['mode'], false, 200 );
			$index   = array_key_last( $records );
			if ( ! isset( $index ) ) {
				$index = $request['index'];
			}
		}
		$result            = [];
		$result['index']   = $index;
		$result['records'] = $records;
		return new \WP_REST_Response( $result, 200 );
	}

}