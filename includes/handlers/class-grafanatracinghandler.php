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

use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
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
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, string $url, int $format, int $sampling ) {
		parent::__construct( $uuid, $format, $sampling );
		$this->endpoint                             = $url;
		$this->post_args['headers']['Content-Type'] = 'application/json';
	}

}
