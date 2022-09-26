<?php
/**
 * Zipkin tracing handler for Monolog
 *
 * Handles all features of Zipkin tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

/**
 * Define the Monolog Zipkin tracing handler.
 *
 * Handles all features of Zipkin tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class ZipkinTracingHandler extends AbstractTracingHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $url        The base endpoint.
	 * @param   string  $tags       Optional. The tags to add for each span.
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, int $sampling, string $url, string $tags = '' ) {
		parent::__construct( $uuid, 100, $sampling, $tags );
		$this->endpoint                             = $url . '/api/v2/spans';
		$this->post_args['headers']['Content-Type'] = 'application/json';
	}

}
