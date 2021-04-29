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
use Prometheus\RenderTextFormat;
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
	 * InfluxDB connexion parameters
	 *
	 * @since  3.0.0
	 * @var    array    $connexion    The InfluxDB connexion parameters.
	 */
	protected $connexion;

	/**
	 * Labels template.
	 *
	 * @since  3.0.0
	 * @var    integer    $template    The label templates ID.
	 */
	protected $template;

	/**
	 * Fixed job name.
	 *
	 * @since  3.0.0
	 * @var    string    $job    The fixed job name.
	 */
	protected $job;

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
		$this->job        = 'aaa';
		$this->template   = 1;
		$this->connection = [
			'url'       => $url,
			'org'       => $org,
			'token'     => $token,
			'bucket'    => $bucket,
			'precision' => InfluxWritePrecision::MS,
			'logFile'   => '/dev/null',
		];



		$this->endpoint = $url . '/metrics';
		$stream         = [];
		switch ( $this->template ) {
			case 1:
				$stream['job']         = $this->job;
				$stream['instance']    = gethostname();
				$stream['environment'] = Environment::stage();
				break;
			case 2:
				$stream['job']      = $this->job;
				$stream['instance'] = gethostname();
				$stream['version']  = Environment::wordpress_version_text( true );
				break;
			case 3:
				$stream['job']  = $this->job;
				$stream['site'] = Blog::get_current_blog_id( 0 );
				break;
			default:
				$stream['job']      = $this->job;
				$stream['instance'] = gethostname();
		}
		foreach ( $stream as $key => $value ) {
			$this->endpoint .= '/' . $key . '/' . $value;
		}
		$this->post_args['headers']['Content-Type'] = RenderTextFormat::MIME_TYPE;



		/*$client = new InfluxClient( $connection );

		$health       = $client->health();
		if ( 'pass' === $health->getStatus() ) {
			$this->influx = $client->createWriteApi();
			$ok           = true;
			//error_log( sprintf( 'Connected to InfluxDB v%s.', $health->getVersion() ) );
		} else {
			$message = preg_replace('/\[.*: /miU', '', $health->getMessage() );
			$message = str_replace( '(see https://curl.haxx.se/libcurl/c/libcurl-errors.html) ', '', $message );
			error_log( sprintf( 'Unable to connect to InfluxDB: %s.', $message ) );
		}*/
	}

	/**
	 * {@inheritdoc}
	 */
	public function close(): void {
		$monitor                 = new DMonitor( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$renderer                = new RenderTextFormat();
		$production              = $monitor->prod_registry()->getMetricFamilySamples();
		$development             = ( Logger::ALERT === $this->level ? $monitor->dev_registry()->getMetricFamilySamples() : [] );
		$this->post_args['body'] = $renderer->render( array_merge( $production, $development ) );
		//parent::send();
	}

}
