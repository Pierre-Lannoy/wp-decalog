<?php
/**
 * Grafana Cloud handler for Monolog
 *
 * Handles all features of Grafana Cloud handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\LokiFormatter;

/**
 * Define the Monolog Grafana Cloud handler.
 *
 * Handles  all features of Grafana Cloud handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class GrafanaTracingHandler extends AbstractTracingHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   string  $url        The agent url.
	 * @param   int     $format     The format in which to push data.
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $tags       Optional. The tags to add for each span.
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, string $url, int $format, int $sampling, string $tags = '' ) {
		parent::__construct( $uuid, $format, $sampling, $tags );
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
