<?php
/**
 * InfluxDB 2 monitoring handler for Monolog
 *
 * Handles all features of InfluxDB 2 monitoring handler for Monolog.
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
use Prometheus\RenderLineFormat;
use Prometheus\CollectorRegistry;
use InfluxDB2\Client as InfluxClient;
use InfluxDB2\Model\WritePrecision as InfluxWritePrecision;

/**
 * Define the Monolog InfluxDB 2 monitoring handler.
 *
 * Handles all features of InfluxDB 2 monitoring handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class InfluxMonitoringHandler extends AbstractMonitoringHandler {

	/**
	 * InfluxDB connection parameters
	 *
	 * @since  3.0.0
	 * @var    array    $connection    The InfluxDB connection parameters.
	 */
	protected $connection;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   int     $profile    The profile of collected metrics (500, 550 or 600).
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $url        The base endpoint.
	 * @param   string  $org        The organization name.
	 * @param   string  $bucket     The bucket name.
	 * @param   string  $token      The token.
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, int $profile, int $sampling, string $url, string $org, string $bucket, string $token ) {
		parent::__construct( $uuid, $profile, $sampling );
		$this->connection = [
			'url'       => $url,
			'org'       => $org,
			'token'     => $token,
			'bucket'    => $bucket,
			'precision' => InfluxWritePrecision::MS,
			'logFile'   => '/dev/null',
		];
	}

	/**
	 * Post the record to the service.
	 *
	 * @since    3.0.0
	 */
	protected function send(): void {
		try {
			$client = new InfluxClient( $this->connection );
			$influx = $client->createWriteApi();
			$influx->write( $this->post_args['body'] );
		} catch ( \Throwable $e ) {
			//TODO: handle error, for now it's silent.
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function close(): void {
		$monitor                 = new DMonitor( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$renderer                = new RenderLineFormat();
		$production              = $monitor->prod_registry()->getMetricFamilySamples();
		$development             = ( Logger::ALERT === $this->level ? $monitor->dev_registry()->getMetricFamilySamples() : [] );
		$this->post_args['body'] = $renderer->render( array_merge( $production, $development ) );
		$this->send();
	}

}
