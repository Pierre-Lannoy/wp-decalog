<?php
/**
 * Jaeger tracing handler for Monolog
 *
 * Handles all features of Jaeger tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

/**
 * Define the Monolog Jaeger tracing handler.
 *
 * Handles all features of Jaeger tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class JaegerTracingHandler extends AbstractTracingHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   int     $format     The format in which to push data.
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $url        The base endpoint.
	 * @param   string  $tags       Optional. The tags to add for each span.
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, int $format, int $sampling, string $url, string $tags = '' ) {
		parent::__construct( $uuid, $format, $sampling, $tags );
		$this->endpoint                             = $url . '/api/traces';
		$this->post_args['headers']['Content-Type'] = 'application/x-thrift';
	}

}
