<?php declare(strict_types=1);
/**
 * Sematext formatter for Monolog
 *
 * Handles all features of Sematext formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Formatter;

use Decalog\System\Http;
use DLMonolog\Formatter\ElasticsearchFormatter;
use Decalog\Plugin\Feature\EventTypes;

/**
 * Define the Monolog Sematext formatter.
 *
 * Handles all features of Sematext formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class SematextFormatter extends ElasticsearchFormatter {

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return array The formatted record.
	 * @since   1.0.0
	 */
	public function format( array $record ): array {
		if ( array_key_exists( $record['level'], EventTypes::$level_names ) ) {
			$level = EventTypes::$level_names[ $record['level'] ];
		} else {
			$level = 'DEFAULT';
		}
		$record['severity'] = strtolower( $level );
		$record['source']   = strtolower( $record['channel'] );
		if ( array_key_exists( 'SERVER_NAME', $_SERVER ) ) {
			$record['host'] = filter_input( INPUT_SERVER, 'SERVER_NAME' );
		}
		$record['_index'] = $this->index;
		$record['_type']  = $this->type;
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'traceID', $record['context'] ) ) {
			$record['traceID'] = $record['context']['traceID'];
			unset( $record['context']['traceID'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'usersession', $record['extra'] ) ) {
			$record['sessionID'] = $record['extra']['usersession'];
			unset( $record['extra']['usersession'] );
		}
		return $record;
	}
}
