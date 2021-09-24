<?php
/**
 * WordPress handler for Monolog
 *
 * Handles all features of WordPress handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use DLMonolog\Handler\AbstractProcessingHandler;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;
use Decalog\Storage\DBStorage;
use Decalog\Storage\APCuStorage;

/**
 * Define the Monolog WordPress handler.
 *
 * Handles all features of WordPress handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class WordpressHandler extends AbstractProcessingHandler {

	/**
	 * The table name.
	 *
	 * @since  1.0.0
	 * @var    string    $table    The table name.
	 */
	private $table = '';

	/**
	 * The storage engine.
	 *
	 * @since  3.0.0
	 * @var    \Decalog\Storage\AbstractStorage    $storage    The storage engine.
	 */
	private $storage = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $table    The table name.
	 * @param   string  $storage  The storage type.
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    1.0.0
	 */
	public function __construct( string $table, string $storage, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->table = $table;
		switch ( $storage ) {
			case 'apcu':
				$this->storage = new APCuStorage( $this->table );
				break;
			default:
				$this->storage = new DBStorage( $this->table );
		}
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
	 * @since    1.0.0
	 */
	protected function write( array $record ): void {
		// phpcs:ignore
		$messages = unserialize( $record['formatted'] );
		if ( is_array( $messages ) ) {
			foreach ( $messages as $message ) {
				if ( is_array( $message ) ) {
					$this->storage->insert_value( $message );
				}
			}
		}
	}
}
