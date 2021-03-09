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

use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use Prometheus\RenderTextFormat;

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
	 *
	 * @param   int     $profile    The profile of collected metrics (500, 550 or 600).
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $url        The base endpoint.
	 * @param   int     $model      The model to use for labels.
	 * @param   string  $id         Optional. The job id.
	 * @since    3.0.0
	 */
	public function __construct( int $profile, int $sampling, string $url, int $model, string $id = 'wp_decalog' ) {
		parent::__construct( $profile, $sampling );
		$this->endpoint                             = $url . '/metrics/job' . $id;
		$this->template                             = $model;
		$this->post_args['headers']['Content-Type'] = RenderTextFormat::MIME_TYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function close(): void {
	}

	/**
	 * Post events to the service.
	 *
	 * @param   array $events    The record to post.
	 * @since    3.0.0
	 */
	protected function write( array $events ): void {
		/*$this->post_args['headers']['Bugsnag-Sent-At'] = gmdate( 'c' );
		if ( 1 === count( $events ) ) {
			$body                    = [
				'apiKey'         => $this->post_args['headers']['Bugsnag-Api-Key'],
				'payloadVersion' => $this->post_args['headers']['Bugsnag-Payload-Version'],
				'notifier'       => (object) [
					'name'    => DECALOG_PRODUCT_NAME,
					'version' => DECALOG_VERSION,
					'url'     => DECALOG_PRODUCT_URL,
				],
				'events'         => maybe_unserialize( $events[0] ),
			];
			$this->post_args['body'] = wp_json_encode( $body );
			parent::write( $this->post_args );
		}*/
	}

}
