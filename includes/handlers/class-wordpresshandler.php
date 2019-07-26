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

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;

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
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $table    The table name.
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?
	 * @since    1.0.0
	 */
	public function __construct( string $table, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->table = $table;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new WordpressFormatter();
	}

	/**
	 * Update table with current message.
	 *
	 * @param   array $value  The values to update or insert in the table.
	 * @return integer The inserted id if anny.
	 * @since    1.0.0
	 */
	private function insert_value( $value ) {
		global $wpdb;
		if ( $wpdb->insert( $this->table, $value, '%s' ) ) {
			return $wpdb->insert_id;
		}
		return 0;
	}

	/**
	 * Write the record in the table.
	 *
	 * @param   array $record    The record to write.
	 * @since    1.0.0
	 */
	protected function write( array $record ): void {
		$messages = unserialize( $record['formatted'] );
		if ( is_array( $messages ) ) {
			foreach ( $messages as $message ) {
				if ( is_array( $message ) ) {
					$this->insert_value( $message );
				}
			}
		}
	}
}
