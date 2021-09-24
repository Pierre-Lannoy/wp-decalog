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

use Decalog\Plugin\Feature\ClassTypes;
use Decalog\System\Http;
use Decalog\Formatter\WordpressFormatter;
use DLMonolog\Logger;

/**
 * Define the Monolog WordPress formatter.
 *
 * Handles all features of WordPress formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class NewlineFormatter extends WordpressFormatter {

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   1.0.0
	 */
	public function format( array $record ): string {
		$message = '';
		// phpcs:ignore
		$values = unserialize( parent::format( $record ) );
		if ( count( $values ) > 0 ) {
			$value = $values[0];
			if ( array_key_exists( 'trace', $value ) ) {
				unset( $value['trace'] );
			}
			foreach ( $value as $key => $item ) {
				$message .= $key . ': ' . $item . PHP_EOL;
			}
		}
		return $message;
	}
	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   1.0.0
	 */
	public function formatBatch( array $records ): string {
		if ( count( $records ) > 0 ) {
			return $this->format( $records[0] );
		}
		return 'No Content';
	}
}
