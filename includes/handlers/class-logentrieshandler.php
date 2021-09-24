<?php
/**
 * Logentries handler for Monolog
 *
 * Handles all features of Logentries handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use Decalog\Plugin\Feature\DLogger;
use DLMonolog\Handler\SocketHandler;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\FluentFormatter;

/**
 * Define the Monolog Logentries handler.
 *
 * Handles all features of Logentries handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class LogentriesHandler extends SocketHandler {

	/**
	 * @var string
	 */
	protected $token;

	/**
	 * @param string     $connection_string Socket connection string.
	 * @param string     $token             API token.
	 * @param integer    $timeout           The socket timeout.
	 * @param int|string $level             The minimum logging level at which this handler will be triggered.
	 * @param bool       $bubble            Whether the messages that are handled can bubble up the stack or not.
	 */
	public function __construct( string $connection_string, string $token, int $timeout, $level = Logger::DEBUG, bool $bubble = true ) {
		$connection_string = 'ssl://' . $connection_string . '.data.logs.insight.rapid7.com:443';
		$this->token       = $token;
		$new_timeout       = $timeout / 1000;
		$old_timeout       = ini_get( 'default_socket_timeout' );
		// phpcs:ignore
		ini_set( 'default_socket_timeout', (string) $new_timeout );
		parent::__construct( $connection_string, $level, $bubble );
		// phpcs:ignore
		ini_set( 'default_socket_timeout', (string) $old_timeout );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function generateDataStream( array $record ) : string {
		return $this->token . ' ' . $record['formatted'];
	}
}
