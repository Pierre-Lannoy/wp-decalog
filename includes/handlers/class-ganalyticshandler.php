<?php
/**
 * Bugsnag handler for Monolog
 *
 * Handles all features of Bugsnag handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Decalog\System\Environment;
use Decalog\System\UserAgent;
use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\GAnalyticsFormatter;
use Decalog\System\Http;

/**
 * Define the Monolog Bugsnag handler.
 *
 * Handles all features of Bugsnag handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class GAnalyticsHandler extends AbstractBufferedHTTPHandler {

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
		$this->endpoint  = 'https://www.google-analytics.com/collect';
		$this->post_args = [
			'headers'    => [
				'User-Agent' => Http::user_agent()
			],
			'user-agent' => filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING ),
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
			parent::write( $this->post_args );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new GAnalyticsFormatter();
	}

	/**
	 * {@inheritdoc}
	 */
	public function handleBatch( array $records ): void {
		$messages = [];
		foreach ( $records as $record ) {
			if ( $record['level'] < $this->level ) {
				continue;
			}
			$messages[] = $record;
		}
		if ( ! empty( $messages ) ) {
			$messages = $this->getFormatter()->formatBatch( $messages );
			$this->write( [ $messages ] );
		}
	}

}
