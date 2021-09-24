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

use Decalog\System\Http;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Plugin\Feature\EventTypes;

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
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   1.0.0
	 */
	public function format( array $record ): string {
		if ( array_key_exists( $record['level'], EventTypes::$level_names ) ) {
			$level = EventTypes::$level_names[ $record['level'] ];
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
