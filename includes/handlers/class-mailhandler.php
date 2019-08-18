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

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;

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
		return new LineFormatter();
	}

	/**
	 * Send the record by mail.
	 *
	 * @param   array $record    The record to send.
	 * @since    1.0.0
	 */
	protected function write( array $record ): void {

		wp_mail( $this->recipients, 'test', $record['formatted'] );

	}
}
