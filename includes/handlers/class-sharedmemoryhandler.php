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

use Decalog\System\Option;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\GenericFormatter;
use Decalog\System\SharedMemory;
use Decalog\System\Environment;
use malkusch\lock\mutex\FlockMutex;
use malkusch\lock\mutex\SemaphoreMutex;

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
	 * The buffer size.
	 *
	 * @since  2.0.0
	 * @var    integer    $buffer    The number of messages in buffer.
	 */
	private $buffer = 4000;

	/**
	 * The read index.
	 *
	 * @since  2.0.0
	 * @var    string    $index    The index for data.
	 */
	private static $index = '';

	/**
	 * Get relevant ftok.
	 *
	 * @since    2.0.0
	 */
	private static function ftok() {
		if ( 1 === Environment::exec_mode() ) {
			return ftok( __FILE__, 'c' );
		} else {
			return ftok( __FILE__, 'w' );
		}
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    2.0.0
	 */
	public function __construct( $level = Logger::INFO, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new GenericFormatter();
	}

	/**
	 * Write the record in memory.
	 *
	 * @param   array $record    The record to write.
	 * @since    2.0.0
	 */
	protected function write( array $record ): void {
		// phpcs:ignore
		$messages = unserialize( $record['formatted'] );
		$mutex    = new FlockMutex( fopen( __FILE__, 'r' ), 1 );
		$ftok     = self::ftok();
		$mutex->synchronized(function () use ($messages, $ftok) {
			$sm   = new SharedMemory( $ftok );
			$data = $sm->read();
			foreach ( $messages as $message ) {
				if ( is_array( $message ) ) {
					$date = new \DateTime();
					$data[ $date->format( 'YmdHisu' ) ] = $message;
				}
			}
			$data = array_slice( $data, -$this->buffer );
			if ( false === $sm->write( $data ) ) {
				//error_log( 'ERROR' );
			}
		});
	}

	/**
	 * Read the current records.
	 *
	 * @return  array   The current records, ordered.
	 * @since    2.0.0
	 */
	public static function read(): array {
		$mutex = new FlockMutex( fopen( __FILE__, 'r' ), 1 );
		$ftok  = ftok( __FILE__, 'w' );
		$data1 = $mutex->synchronized(function () use ($ftok) {
			$log  = new SharedMemory( $ftok );
			$data = $log->read();
			return $data;
		});
		$ftok  = ftok( __FILE__, 'c' );
		$data2 = $mutex->synchronized(function () use ($ftok) {
			$log  = new SharedMemory( $ftok );
			$data = $log->read();
			return $data;
		});
		$data = array_merge( $data1, $data2 );
		uksort($data, 'strcmp' );
		$result = [];
		foreach ( $data as $key => $line ) {
			if ( 0 < strcmp( $key, self::$index ) ) {
				$result[$key] = $line;
				self::$index = $key;
			}
		}
		return $result;
	}
}
