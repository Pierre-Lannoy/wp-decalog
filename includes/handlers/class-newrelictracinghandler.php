<?php
/**
 * New Relic tracing handler for Monolog
 *
 * Handles all features of New Relic tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */

namespace Decalog\Handler;

/**
 * Define the Monolog New Relic tracing handler.
 *
 * Handles all features of New Relic tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */
class NewRelicTracingHandler extends AbstractTracingHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $host       The New Relic ingestion host (for location selection).
	 * @param   string  $key        The API key.
	 * @since    3.2.0
	 */
	public function __construct( string $uuid, int $sampling, string $host, string $key ) {
		parent::__construct( $uuid, 100, $sampling );
		$this->endpoint                                    = $host;
		$this->post_args['headers']['Content-Type']        = 'application/json';
		$this->post_args['headers']['Api-Key']             = $key;
		$this->post_args['headers']['Data-Format']         = 'zipkin';
		$this->post_args['headers']['Data-Format-Version'] = 2;
	}

}
