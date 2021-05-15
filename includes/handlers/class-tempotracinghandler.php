<?php
/**
 * Tempo tracing handler for Monolog
 *
 * Handles all features of Tempo tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

/**
 * Define the Monolog Tempo tracing handler.
 *
 * Handles all features of Tempo tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class TempoTracingHandler extends AbstractTracingHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   int     $format     The format in which to push data.
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $url        The base endpoint.
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, int $format, int $sampling, string $url ) {
		parent::__construct( $uuid, $format, $sampling );
		switch ( $this->format ) {
			case 100:
				$this->endpoint                             = $url;
				$this->post_args['headers']['Content-Type'] = 'application/json';
				break;
			case 200:
				$this->endpoint                             = $url . '/api/traces';
				$this->post_args['headers']['Content-Type'] = 'application/x-thrift';
				break;
		}
	}

}
