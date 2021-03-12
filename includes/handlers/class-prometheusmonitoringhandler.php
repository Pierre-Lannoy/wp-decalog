<?php
/**
 * Prometheus monitoring handler for Monolog
 *
 * Handles all features of Prometheus monitoring handler for Monolog.
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

/**
 * Define the Monolog Prometheus monitoring handler.
 *
 * Handles all features of Prometheus monitoring handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class PrometheusMonitoringHandler extends AbstractMonitoringHandler {

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
	 * @param   int     $model      The model to use for labels.
	 * @param   string  $id         Optional. The job id.
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, int $profile, int $sampling, string $url, int $model, string $id = 'wp_decalog' ) {
		parent::__construct( $uuid, $profile, $sampling );
		$this->job      = $id;
		$this->template = $model;
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
		parent::send();
	}

}
