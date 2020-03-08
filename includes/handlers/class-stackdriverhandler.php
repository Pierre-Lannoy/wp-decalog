<?php
/**
 * Stackdriver via Fluentd handler for Monolog
 *
 * Handles all features of Stackdriver via Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Monolog\Logger;
use Decalog\Plugin\Feature\DLogger;
use Monolog\Handler\SocketHandler;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\StackdriverFormatter;

/**
 * Define the Monolog Stackdriver via Fluentd handler.
 *
 * Handles all features of Stackdriver via Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class StackdriverHandler extends SocketHandler {

	/**
	 * @param string     $connection_string Socket connection string.
	 * @param integer    $timeout           The socket timeout.
	 * @param int|string $level             The minimum logging level at which this handler will be triggered.
	 * @param bool       $bubble            Whether the messages that are handled can bubble up the stack or not.
	 */
	public function __construct( string $connection_string, int $timeout, $level = Logger::DEBUG, bool $bubble = true ) {
		$new_timeout = $timeout / 1000;
		$old_timeout = ini_get( 'default_socket_timeout' );
		// phpcs:ignore
		ini_set( 'default_socket_timeout', (string) $new_timeout );
		parent::__construct( $connection_string, $level, $bubble );
		// phpcs:ignore
		ini_set( 'default_socket_timeout', (string) $old_timeout );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new StackdriverFormatter();
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
			DLogger::ban( 'stackdriverhandler' );
		}
	}
}
