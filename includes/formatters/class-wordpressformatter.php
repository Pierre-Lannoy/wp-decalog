<?php declare(strict_types=1);
/**
 * WordPress formatter for Monolog
 *
 * Handles all features of WordPress formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;

/**
 * Define the Monolog WordPress formatter.
 *
 * Handles all features of WordPress formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class WordpressFormatter implements FormatterInterface {

	/**
	 * This is a static variable and not a constant to serve as an extension point for custom levels
	 *
	 * @var string[] $levels Logging levels with the levels as key
	 */
	protected static $levels = [
		Logger::DEBUG     => 'DEBUG',
		Logger::INFO      => 'INFO',
		Logger::NOTICE    => 'NOTICE',
		Logger::WARNING   => 'WARNING',
		Logger::ERROR     => 'ERROR',
		Logger::CRITICAL  => 'CRITICAL',
		Logger::ALERT     => 'ALERT',
		Logger::EMERGENCY => 'EMERGENCY',
	];


	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   1.0.0
	 */
	public function format( array $record ): string {
		$message = [];
		$values              = array();
		$values['timestamp'] = date( 'Y-m-d H:i:s' );
		if (array_key_exists('level', $record)) {
			if (array_key_exists($record['level'], self::$levels)) {
				$values['level'] = strtolower(self::$levels[$record['level']]);
			}
		}
		if (array_key_exists('channel', $record)) {
			$values['channel'] = strtolower($record['channel']);
		}


		$message[] = $values;
		return serialize( $message );
	}
	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   1.0.0
	 */
	public function formatBatch( array $records ): string {
		$messages = [];
		foreach ( $records as $record ) {
			$a = $this->format( $record );
			if ( 1 === count( $a ) ) {
				$messages[] = $a[0];
			}
		}
		return serialize( $messages );
	}
}
