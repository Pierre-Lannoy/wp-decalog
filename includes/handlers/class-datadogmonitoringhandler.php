<?php
/**
 * Datadog monitoring handler for Monolog
 *
 * Handles all features of Datadog monitoring handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

use Decalog\System\Blog;
use Decalog\System\Environment;
use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use Decalog\Plugin\Feature\DMonitor;
use Prometheus\RenderDatadogFormat;
use Prometheus\CollectorRegistry;

/**
 * Define the Monolog Datadog monitoring handler.
 *
 * Handles all features of Datadog monitoring handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class DatadogMonitoringHandler extends AbstractMonitoringHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   string  $host       The Tempo hostname.
	 * @param   string  $key        The API key.
	 * @param   int     $profile    The profile of collected metrics (500, 550 or 600).
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, string $host, string $key, int $profile, int $sampling ) {
		parent::__construct( $uuid, $profile, $sampling );
		$this->endpoint                             = $host;
		$this->post_args['headers']['Content-Type'] = 'application/json';
		$this->post_args['headers']['DD-API-KEY']   = $key;
	}

	/**
	 * {@inheritdoc}
	 */
	public function close(): void {
		$monitor  = new DMonitor( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$renderer = new RenderDatadogFormat();
		if ( $monitor->prod_registry() && $monitor->dev_registry() ) {
			$production              = $monitor->prod_registry()->getMetricFamilySamples();
			$development             = ( Logger::ALERT === $this->level ? $monitor->dev_registry()->getMetricFamilySamples() : [] );
			$this->post_args['body'] = $renderer->render( array_merge( $production, $development ) );
			parent::send();
		}
	}

}
