<?php declare(strict_types=1);
/**
 * Fluentd formatter for Monolog
 *
 * Handles all features of Fluentd formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;

/**
 * Define the Monolog Fluentd formatter.
 *
 * Handles all features of Fluentd formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class FluentFormatter implements FormatterInterface {

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
		if ( array_key_exists( $record['level'], self::$levels ) ) {
			$level = self::$levels[ $record['level'] ];
		} else {
			$level = 'UNKNOWN';
		}
		$tag     = strtolower( DECALOG_PRODUCT_SHORTNAME . '.' . $record['channel'] . '.' . $level );
		$message = [
			'message' => $record['message'],
			'context' => $record['context'],
			'extra'   => $record['extra'],
		];
		return wp_json_encode( [ $tag, $record['datetime']->getTimestamp(), $message ] );
	}
	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   1.0.0
	 */
	public function formatBatch( array $records ): string {
		$message = '';
		foreach ( $records as $record ) {
			$message .= $this->format( $record );
		}
		return $message;
	}
}
