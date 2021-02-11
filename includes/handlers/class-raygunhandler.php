<?php
/**
 * Raygun handler for Monolog
 *
 * Handles all features of Raygun handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\RaygunFormatter;

/**
 * Define the Monolog Raygun handler.
 *
 * Handles all features of Raygun handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class RaygunHandler extends AbstractBufferedHTTPHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $key         The Bugsnag API key.
	 * @param   boolean $buffered   Optional. Has the record to be buffered?.
	 * @param   integer $level      Optional. The min level to log.
	 * @param   boolean $bubble     Optional. Has the record to bubble?.
	 * @since    2.4.0
	 */
	public function __construct( string $key, bool $buffered = true, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $buffered, $bubble );
		$this->endpoint  = 'https://api.raygun.com/entries';
		$this->post_args = [
			'headers' => [
				'Content-Type' => 'application/json',
				'X-ApiKey'     => $key,
			],
		];
	}

	/**
	 * Post events to the service.
	 *
	 * @param   array $events    The record to post.
	 * @since    2.4.0
	 */
	protected function write( array $events ): void {
		$this->post_args['headers']['Bugsnag-Sent-At'] = gmdate( 'c' );
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
			//parent::write( $this->post_args );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new RaygunFormatter();
	}

	/**
	 * {@inheritdoc}
	 */
	public function handleBatch( array $records ): void {
		foreach ( $records as $record ) {
			if ( $record['level'] < $this->level ) {
				continue;
			}
			$this->write( [ $record ] );
		}
	}

}
