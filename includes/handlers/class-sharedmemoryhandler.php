<?php
/**
 * Shared memory handler for Monolog
 *
 * Handles all features of shared memory handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Decalog\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;

/**
 * Define the Monolog shared memory handler.
 *
 * Handles all features of shared memory handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class SharedMemoryHandler extends AbstractProcessingHandler {

	/**
	 * The source filter.
	 *
	 * @since  2.0.0
	 * @var    string    $source    The single source to log; acts as a filter.
	 */
	private $source = '';

	/**
	 * The ftok letter.
	 *
	 * @since  2.0.0
	 * @var    string    $ftok    The ftok letter identifier.
	 */
	private $ftok = 'z';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $ftok     The ftok letter identifier.
	 * @param   string  $source   Optional. The single source to log; acts as a filter.
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    2.0.0
	 */
	public function __construct( string $ftok, string $source = '', $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->source = $source;
		$this->ftok   = $ftok;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new WordpressFormatter();
	}

	/**
	 * Write the record in the table.
	 *
	 * @param   array $record    The record to write.
	 * @since    2.0.0
	 */
	protected function write( array $record ): void {
		// phpcs:ignore
		/*$messages = unserialize( $record['formatted'] );
		if ( is_array( $messages ) ) {
			foreach ( $messages as $message ) {
				if ( is_array( $message ) ) {
					$this->insert_value( $message );
				}
			}
		}*/
	}
}
