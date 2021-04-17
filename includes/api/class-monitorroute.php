<?php
/**
 * DecaLog Monitor read handler
 *
 * Handles all monitor reads.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog;

use Decalog\Plugin\Feature\Log;
use Decalog\System\Cache;
use Decalog\System\Role;
use Decalog\Plugin\Feature\DLogger;
use Decalog\System\Option;
use Prometheus\RenderTextFormat;

/**
 * Define the item operations functionality.
 *
 * Handles all item operations.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class MonitorRoute extends \WP_REST_Controller {

	/**
	 * The internal logger.
	 *
	 * @since  3.0.0
	 * @var    DLogger    $logger    The plugin rest logger.
	 */
	protected $logger;

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since  3.0.0
	 */
	public function register_routes() {
		$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$this->register_route_livelog();
	}

	/**
	 * Register the routes for livelog.
	 *
	 * @since  3.0.0
	 */
	public function register_route_livelog() {
		register_rest_route(
			DECALOG_REST_NAMESPACE,
			'metrics',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_metrics' ],
					'permission_callback' => [ $this, 'get_metrics_permissions_check' ],
					'args'                => array_merge( $this->arg_schema_metrics() ),
					'schema'              => [ $this, 'get_schema' ],
				],
			]
		);
	}

	/**
	 * Get the query params for metrics.
	 *
	 * @return array    The schema fragment.
	 * @since  3.0.0
	 */
	public function arg_schema_metrics() {
		return [
			'uuid' => [
				'description'       => 'The logger UUID.',
				'type'              => 'string',
				'required'          => true,
				'default'           => '00000000-0000-0000-0000-000000000000',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Check if a given request has access to get metrics
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function get_metrics_permissions_check( $request ) {
		if ( ! (bool) Option::network_get( 'metrics_authent' ) ) {
			return true;
		}
		if ( ! is_user_logged_in() ) {
			$this->logger->warning( 'Unauthenticated API call.', 401 );
			return new \WP_Error( 'rest_not_logged_in', 'You must be logged in to access metrics.', [ 'status' => 401 ] );
		}
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}
		return $user->has_cap( 'read_private_pages' );
	}

	/**
	 * Get a collection of metrics
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_metrics( $request ) {
		$record = Cache::get( 'metrics/' . $request['uuid'], true );
		if ( isset( $record ) && is_array( $record ) && array_key_exists( 'body', $record ) && array_key_exists( 'headers', $record ) && array_key_exists( 'timestamp', $record ) ) {
			header( 'Content-Type: ' . RenderTextFormat::MIME_TYPE );
			header( 'Age: ' . ( time() - $record['timestamp'] ) );
			//phpcs:ignore
			print( $record['body'] );
			exit();
		} else {
			return new \WP_Error( 'rest_resource_not_found', sprintf( 'Logger %s not found.', $request['uuid'] ), [ 'status' => 404 ] );
		}

	}

}