<?php
/**
 * WordPress mail handler for Monolog
 *
 * Handles all features of WordPress mail handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Decalog\Plugin\Feature\EventTypes;
use DLMonolog\Logger;
use DLMonolog\Handler\AbstractProcessingHandler;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\NewlineFormatter;

/**
 * Define the Monolog WordPress mail handler.
 *
 * Handles all features of WordPress mail handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class MailHandler extends AbstractProcessingHandler {

	/**
	 * The recipients.
	 *
	 * @since  1.0.0
	 * @var    array    $recipients    The recipients of the mail.
	 */
	private $recipients = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $recipients    The recipients coma separated list.
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    1.0.0
	 */
	public function __construct( string $recipients, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->recipients = explode( ',', $recipients );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new NewlineFormatter();
	}

	/**
	 * Send the record by mail.
	 *
	 * @param   array $record    The record to send.
	 * @since    1.0.0
	 */
	protected function write( array $record ): void {
		if ( array_key_exists( 'level', $record ) && array_key_exists( $record['level'], EventTypes::$level_names ) ) {
			$level = EventTypes::$level_names[ $record['level'] ];
		} else {
			$level = '';
		}
		$subject = sprintf( 'DecaLog: %s', $level );
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'sitename', $record['extra'] ) ) {
			$subject .= sprintf( ' on %s', $record['extra']['sitename'] );
		}
		wp_mail( $this->recipients, $subject, $record['formatted'] );

	}
}
