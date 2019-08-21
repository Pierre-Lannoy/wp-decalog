<?php
/**
 * Fluentd handler for Monolog
 *
 * Handles all features of Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Monolog\Logger;
use Decalog\API\DLogger;
use Monolog\Handler\SocketHandler;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\FluentFormatter;

/**
 * Define the Monolog Fluentd handler.
 *
 * Handles all features of Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class FluentHandler extends SocketHandler {

	/**
	 * @param string     $connectionString Socket connection string
	 * @param int|string $level            The minimum logging level at which this handler will be triggered
	 * @param bool       $bubble           Whether the messages that are handled can bubble up the stack or not
	 */
	public function __construct( string $connectionString, $level = Logger::DEBUG, bool $bubble = true ) {
		$timeout = ini_get( 'default_socket_timeout' );
		ini_set( 'default_socket_timeout', '0.2' );
		parent::__construct( $connectionString, $level, $bubble );
		ini_set( 'default_socket_timeout', (string) $timeout );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new FluentFormatter();
	}

	/**
	 * Write to the socket.
	 *
	 * @param array $record The record to write.
	 */
	protected function write( array $record ): void {
		try {
			parent::write( $record );
		} catch ( \Throwable $t ) {
			DLogger::ban( 'fluenthandler' );
		}
	}
}
