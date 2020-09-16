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
use Decalog\Formatter\WordpressFormatter;
use Decalog\System\SharedMemory;

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
	 * The logger uuid.
	 *
	 * @since  2.0.0
	 * @var    string    $uuid    The logger uuid.
	 */
	private $uuid = '';

	/**
	 * The access mode.
	 *
	 * @since  2.0.0
	 * @var    integer    $mode    The access mode.
	 */
	private static $mode = 0666;

	/**
	 * The opened blocks.
	 *
	 * @since  2.0.0
	 * @var    array    $blocks    The opened blocks.
	 */
	private static $blocks = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid     The logger uuid.
	 * @param   string  $ftok     The ftok letter identifier.
	 * @param   string  $source   Optional. The single source to log; acts as a filter.
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    2.0.0
	 */
	public function __construct( string $uuid, string $ftok, string $source = '', $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->source = $source;
		$this->uuid   = $uuid;
		$free         = [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y' ];
		$loggers      = Option::network_get( 'loggers' );
		if ( 'z' === $ftok ) {
			foreach( $loggers as $logger ) {
				if ( 'SharedMemoryHandler' === $logger['handler'] ) {
					$free = array_diff( $free, [ $logger['configuration']['ftok'] ] );
				}
			}
			$ftok                                      = $free[ array_rand( $free ) ];
			$loggers[ $uuid ]['configuration']['ftok'] = $ftok;
			Option::network_set( 'loggers', $loggers );
		}
		$this->ftok = $ftok;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new WordpressFormatter();
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
		if ( is_array( $messages ) ) {
			//error_log( 'writing needed' );
			$id   = ftok( __FILE__, (string) $this->ftok );
			$sm   = new SharedMemory( $id );
			$data = (string) $sm->read();
			if ( '' === $data ) {
				$data = '{}';
			}
			$data = \json_decode( $data, true );
			//error_log( 'old messages: ' . count( $data ) );
			foreach ( $messages as $message ) {
				if ( is_array( $message ) ) {
					$date = new \DateTime();
					if ( array_key_exists( 'trace', $message ) ) {
						unset( $message['trace'] );
					}
					$data[ $date->format( 'YmdHisu' ) ] = $message;
				}
			}
			if ( 1000 < count( $data ) ) {
				$loggers = Option::network_get( 'loggers' );
				unset( $loggers[$this->uuid] );
				Option::network_set( 'loggers', $loggers );
				error_log('DELETING');
				//$sm->delete();
			} elseif ( 0 < count( $data ) ) {
				error_log('WRITING');
				$sm->write( 'aaa' );
				error_log('WRITING... DONE');
			}
		} else {
			//error_log('nothing to write');
		}
	}

	/**
	 * Write the record in memory.
	 *
	 * @param   array $record    The record to write.
	 * @since    2.0.0
	 */
	protected function old_write( array $record ): void {
		// phpcs:ignore
		$messages = unserialize( $record['formatted'] );
		if ( is_array( $messages ) ) {
			//error_log('W');
			$id   = ftok( __FILE__, (string) $this->ftok );
			$data = [];
			// phpcs:ignore
			//set_error_handler( function() { /* ignore errors */ } );
			// phpcs:ignore
			//error_log('id => ' . $id);
			//$shmid = @shmop_open( $id, 'w', self::$mode, 0 );
			//error_log('     Passed' . $shmid);
			// phpcs:ignore
			//restore_error_handler();
			/*try {
				$shmid = @shmop_open( $id, 'w', self::$mode, 0 );
			} catch ( \Throwable $t ) {
				$shmid = false;
			}
			if ( is_resource( $shmid ) ) {
				//error_log('existing');
				try {
					$data = shmop_read( $shmid, 0, shmop_size( $shmid ) );
					shmop_delete( $shmid );
					shmop_close( $shmid );
					$data = \json_decode( $data, true );

				} catch ( \Throwable $t ) {
					$data = [];
				}
			} else {
				//error_log('NOT existing');
			}*/
			foreach ( $messages as $message ) {
				if ( is_array( $message ) ) {
					$date = new \DateTime();
					if ( array_key_exists( 'trace', $message ) ) {
						unset( $message['trace'] );
					}
					$data[ $date->format( 'YmdHisu' ) ] = $message;
				}
			}
			/*if ( 100 < count( $data ) ) {
				$loggers = Option::network_get( 'loggers' );
				//unset( $loggers[$this->uuid] );
				//Option::network_set( 'loggers', $loggers );
			} else*/if ( 0 < count( $data ) ) {
				//try {
				$data = wp_json_encode( $data );
				$size = mb_strlen( $data, 'UTF-8' );
				// phpcs:ignore
				$shmid = shmop_open( $id, 'c', self::$mode, $size );





				if ( is_resource( $shmid ) ) {
					$result = (bool) @shmop_write( $shmid, $data, 0 );
					error_log('  -- done');
				} else {
					error_log('BOUH !');
				}
				/*} catch ( \Throwable $t ) {
					$result = false;
				} finally {
					shmop_close( $shmid );
				}*/
			}
		} else {
			error_log('nothing to write');
		}
		//self::free( $id );
	}

	/**
	 * Wait for block.
	 * Warning: only for same process access.
	 *
	 * @param   string $block    The block id.
	 * @since    2.0.0
	 */
	private static function wait( string $block ): void {
		while ( in_array( $block, self::$blocks, true ) ) {
			usleep( 500 );
		}
	}

	/**
	 * Acquire a block semaphore.
	 * Warning: only for same process access.
	 *
	 * @param   string $block    The block id.
	 * @since    2.0.0
	 */
	private static function acquire( string $block ): void {
		self::wait( $block );
		self::$blocks[] = $block;
	}

	/**
	 * Free a block semaphore.
	 * Warning: only for same process access.
	 *
	 * @param   string $block    The block id.
	 * @since    2.0.0
	 */
	private static function free( string $block ): void {
		self::$blocks[] = array_diff( self::$blocks[], [$block] );
	}

	/**
	 * Read the waiting records.
	 *
	 * @param   string $uuid    The logger uuid.
	 * @return  array   The waiting records.
	 * @since    2.0.0
	 */
	public static function read( string $uuid ): array {
		$loggers = Option::network_get( 'loggers' );
		if ( array_key_exists( $uuid, $loggers ) ) {
			//error_log('reading');
			$logger = $loggers[ $uuid ];
			if ( array_key_exists( 'configuration', $logger ) && array_key_exists( 'ftok', $logger['configuration'] ) && 'z' !== (string) $logger['configuration']['ftok'] ) {
				$id   = ftok( __FILE__, (string) $logger['configuration']['ftok'] );
				$log  = new SharedMemory( $id );
				$data = (string) $log->read();
				$log->write( '' );
				if ( '' === $data ) {
					$data = '{}';
				}
				return \json_decode( $data, true );
			}
		} else {
			//error_log('unable to read');
		}
		return [];
	}

	/**
	 * Read the waiting records.
	 *
	 * @param   string $uuid    The logger uuid.
	 * @return  array   The waiting records.
	 * @since    2.0.0
	 */
	public static function old_read( string $uuid ): array {
		$loggers = Option::network_get( 'loggers' );
		if ( array_key_exists( $uuid, $loggers ) ) {
			error_log('reading');
			$logger = $loggers[ $uuid ];
			if ( array_key_exists( 'configuration', $logger ) && array_key_exists( 'ftok', $logger['configuration'] ) && 'z' !== (string) $logger['configuration']['ftok'] ) {
				$id = ftok( __FILE__, (string) $logger['configuration']['ftok'] );
				//self::acquire( $id );
				// phpcs:ignore

				set_error_handler( function() { /* ignore errors */ } );
				$shmid = shmop_open( $id, 'w', self::$mode, 0 );
				restore_error_handler();

				if ( $shmid ) {
					error_log('opened');
					try {
						$data = shmop_read( $shmid, 0, shmop_size( $shmid ) );
						shmop_delete( $shmid );
						shmop_close( $shmid );
					} catch ( \Throwable $t ) {
						$data = '{}';
					}
					return \json_decode( $data, true );
				} else {
					error_log('unable to open');
				}
				//self::free( $id );

			}
		} else {
			error_log('unable to read');
		}
		return [];
	}
}
