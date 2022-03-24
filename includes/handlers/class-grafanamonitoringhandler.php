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
use DLMonolog\Logger;
use DLMonolog\Formatter\FormatterInterface;
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
class GrafanaMonitoringHandler extends AbstractMonitoringHandler {

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
	 * @param   string  $host       The Tempo hostname.
	 * @param   string  $user       The user name.
	 * @param   string  $key        The API key.
	 * @param   int     $profile    The profile of collected metrics (500, 550 or 600).
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   int     $model      The model to use for labels.
	 * @param   string  $id         Optional. The job id.
	 * @param   string  $filters    Optional. The filter to exclude metrics.
	 *
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, string $host, string $user, string $key, int $profile, int $sampling, int $model, string $id = 'wp_decalog', string $filters = '' ) {
		parent::__construct( $uuid, $profile, $sampling, $filters );
		$this->job      = $id;
		$this->template = $model;
		$this->endpoint = 'https://' . $user . ':' . $key . '@' . $host . '.grafana.net/api/prom/push';
		$stream         = [];
		switch ( $this->template ) {
			case 1:
				$stream['job']         = $this->job;
				$stream['instance']    = DECALOG_INSTANCE_NAME;
				$stream['environment'] = Environment::stage();
				break;
			case 2:
				$stream['job']      = $this->job;
				$stream['instance'] = DECALOG_INSTANCE_NAME;
				$stream['version']  = Environment::wordpress_version_text( true );
				break;
			case 3:
				$stream['job']  = $this->job;
				$stream['site'] = Blog::get_current_blog_id( 0 );
				break;
			default:
				$stream['job']      = $this->job;
				$stream['instance'] = DECALOG_INSTANCE_NAME;
		}
		foreach ( $stream as $key => $value ) {
			//$this->endpoint .= '/' . $key . '/' . $value;
		}
		$this->post_args['headers']['Content-Type'] = RenderTextFormat::MIME_TYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function close(): void {
		$monitor  = new DMonitor( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$renderer = new RenderTextFormat();
		if ( $monitor->prod_registry() && $monitor->dev_registry() ) {
			$production              = $monitor->prod_registry()->getMetricFamilySamples();
			$development             = ( Logger::ALERT === $this->level ? $monitor->dev_registry()->getMetricFamilySamples() : [] );
			$this->post_args['body'] = $renderer->render( $this->filter( $production, $development ) );
			parent::send();
		}
	}

}
