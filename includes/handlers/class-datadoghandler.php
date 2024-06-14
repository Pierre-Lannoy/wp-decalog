<?php
/**
 * Datadog handler for Monolog
 *
 * Handles all features of Datadog handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use DLMonolog\Formatter\FormatterInterface;
use DLMonolog\Formatter\JsonFormatter;
use Decalog\Formatter\DatadogFormatter;

/**
 * Define the Monolog Datadog handler.
 *
 * Handles all features of Datadog handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class DatadogHandler extends AbstractBufferedHTTPHandler {

	/**
	 * Normalized extended fields.
	 *
	 * @since  4.0.0
	 * @var    array $extended The normalized extended fields, ready to be added to the event.
	 */
	private $extended = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $host The Datadog ingestion host (for location selection).
	 * @param string $key The Datadog API key.
	 * @param string $extended Optional. Extended fields.
	 * @param boolean $buffered Optional. Has the record to be buffered?.
	 * @param integer $level Optional. The min level to log.
	 * @param boolean $bubble Optional. Has the record to bubble?.
	 *
	 * @since    3.0.0
	 */
	public function __construct( string $host, string $key, string $extended = '', bool $buffered = true, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $buffered, $bubble );
		$this->endpoint                             = defined( 'DECALOG_DATADOG_EVENTS_CUSTOM_ENDPOINT' ) ? DECALOG_DATADOG_EVENTS_CUSTOM_ENDPOINT : $host;
		$this->post_args['headers']['Content-Type'] = 'application/json';
		$this->post_args['headers']['DD-API-KEY']   = $key;
		$this->extended                             = decalog_normalize_extended_fields( $extended );
	}

	/**
	 * Post events to the service.
	 *
	 * @param array $events The record to post.
	 *
	 * @since    3.0.0
	 */
	protected function write( array $events ): void {
		if ( 1 === count( $events ) ) {
			$this->post_args['body'] = $events[0];
			parent::write( $this->post_args );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new DatadogFormatter( JsonFormatter::BATCH_MODE_JSON, true );
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
			if ( ! empty( $this->extended ) ) {
				$record['extended'] = $this->extended;
			}
			$messages[] = $this->getFormatter()->format( $record );
		}
		if ( ! empty( $messages ) ) {
			$messages = '[' . implode( ',', $messages ) . ']';
			$this->write( [ $messages ] );
		}
	}

}
