<?php
/**
 * PagerDuty handler for Monolog
 *
 * Handles all features of PagerDuty handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use DLMonolog\Formatter\FormatterInterface;
use DLMonolog\Formatter\JsonFormatter;
use Decalog\Formatter\NewRelicFormatter;

/**
 * Define the Monolog PagerDuty handler.
 *
 * Handles all features of PagerDuty handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.6.0
 */
class PagerDutyHandler extends AbstractBufferedHTTPHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $host       The New Relic ingestion host (for location selection).
	 * @param   string  $key        The New Relic API key.
	 * @param   boolean $buffered   Optional. Has the record to be buffered?.
	 * @param   integer $level      Optional. The min level to log.
	 * @param   boolean $bubble     Optional. Has the record to bubble?.
	 * @since    3.6.0
	 */
	public function __construct( string $host, string $key, bool $buffered = true, $level = Logger::ERROR, bool $bubble = true ) {
		parent::__construct( $level, $buffered, $bubble );
		$this->endpoint                              = $host;
		$this->post_args['headers']['Content-Type']  = 'application/json';
		$this->post_args['headers']['X-License-Key'] = $key;
	}

	/**
	 * Post events to the service.
	 *
	 * @param   array $events    The record to post.
	 * @since    3.6.0
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
		return new NewRelicFormatter( JsonFormatter::BATCH_MODE_JSON, true );
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
			$messages[] = $this->getFormatter()->format( $record );
		}
		if ( ! empty( $messages ) ) {
			$messages = '[{"logs":[' . implode( ',', $messages ) . ']}]';
			$this->write( [ $messages ] );
		}
	}

}
