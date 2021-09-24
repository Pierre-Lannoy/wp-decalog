<?php
/**
 * Loki handler for Monolog
 *
 * Handles all features of Loki handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\LokiFormatter;

/**
 * Define the Monolog Loki handler.
 *
 * Handles  all features of Loki handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class LokiHandler extends AbstractBufferedHTTPHandler {

	/**
	 * Labels template.
	 *
	 * @since  2.4.0
	 * @var    integer    $template    The label templates ID.
	 */
	protected $template;

	/**
	 * Fixed job name.
	 *
	 * @since  2.4.0
	 * @var    string    $job    The fixed job name.
	 */
	protected $job;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $url        The Loki url.
	 * @param   int     $model      The model to use for labels.
	 * @param   string  $id         The job id.
	 * @param   integer $level      Optional. The min level to log.
	 * @param   boolean $bubble     Optional. Has the record to bubble?.
	 * @since    2.4.0
	 */
	public function __construct( string $url, int $model, string $id = 'wp_decalog', $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, false, $bubble );
		$this->template                             = $model;
		$this->job                                  = $id;
		$this->endpoint                             = str_replace( 'https:/', 'https://', str_replace( 'http:/', 'http://', str_replace( '//', '/', $url . '/loki/api/v1/push' ) ) );
		$this->post_args['headers']['Content-Type'] = 'application/json';
	}

	/**
	 * Post events to the service.
	 *
	 * @param   array $events    The record to post.
	 * @since    2.4.0
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
		return new LokiFormatter( $this->template, $this->job );
	}

	/**
	 * {@inheritdoc}
	 */
	public function handleBatch( array $records ): void {
		foreach ( $records as $record ) {
			if ( $record['level'] < $this->level ) {
				continue;
			}
			$this->write( [ $this->getFormatter()->format( $record ) ] );
		}
	}

}
