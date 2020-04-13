<?php declare(strict_types=1);
/**
 * Stackdriver via Fluentd formatter for Monolog
 *
 * Handles all features of Stackdriver via Fluentd formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Formatter;

use Decalog\System\Http;
use Monolog\Formatter\FormatterInterface;
use Decalog\Plugin\Feature\EventTypes;

/**
 * Define the Monolog Stackdriver via Fluentd formatter.
 *
 * Handles all features of Stackdriver via Fluentd formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class StackdriverFormatter implements FormatterInterface {

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
			$level = 'DEFAULT';
		}
		$tag     = strtolower( DECALOG_PRODUCT_SHORTNAME . '.' . $record['channel'] . '.' . $level );
		$message = [
			'severity' => $level,
			'message'  => $record['message'],
			'context'  => $record['context'],
			'extra'    => $record['extra'],
		];
		if ( array_key_exists( 'file', $record['extra'] ) && $record['extra']['file'] && is_string( $record['extra']['file'] ) ) {
			$message['logging.googleapis.com/sourceLocation']['file'] = $record['extra']['file'];
		}
		if ( array_key_exists( 'line', $record['extra'] ) && $record['extra']['line'] ) {
			$message['logging.googleapis.com/sourceLocation']['line'] = (int) $record['extra']['line'];
		}
		if ( array_key_exists( 'function', $record['extra'] ) && $record['extra']['function'] && is_string( $record['extra']['function'] ) ) {
			$message['logging.googleapis.com/sourceLocation']['function'] = $record['extra']['function'];
			if ( array_key_exists( 'class', $record['extra'] ) && $record['extra']['class'] && is_string( $record['extra']['class'] ) ) {
				$message['logging.googleapis.com/sourceLocation']['function'] = $record['extra']['class'] . '::' . $message['logging.googleapis.com/sourceLocation']['function'];
			}
		}
		if ( array_key_exists( 'ip', $record['extra'] ) && is_string( $record['extra']['ip'] ) ) {
			$message['httpRequest']['remoteIp'] = $record['extra']['ip'];
		}
		if ( array_key_exists( 'url', $record['extra'] ) && is_string( $record['extra']['url'] ) ) {
			$message['httpRequest']['requestUrl'] = $record['extra']['url'];
		}
		if ( array_key_exists( 'http_method', $record['extra'] ) && is_string( $record['extra']['http_method'] ) ) {
			if ( in_array( strtolower( $record['extra']['http_method'] ), Http::$verbs, true ) ) {
				$message['httpRequest']['requestMethod'] = strtoupper( $record['extra']['http_method'] );
			}
		}
		if ( array_key_exists( 'code', $record['context'] ) && is_numeric( $record['context']['code'] ) ) {
			if ( 0 < (int) $record['context']['code'] && array_key_exists( (int) $record['context']['code'], Http::$http_status_codes ) ) {
				$message['httpRequest']['status'] = (int) $record['context']['code'];
			}
		}
		if ( array_key_exists( 'referrer', $record['extra'] ) && is_string( $record['extra']['referrer'] ) ) {
			$message['httpRequest']['referer'] = $record['extra']['referrer'];
		}
		if ( array_key_exists( 'ua', $record['extra'] ) && is_string( $record['extra']['ua'] ) ) {
			$message['httpRequest']['userAgent'] = $record['extra']['ua'];
		}
		$message['logging.googleapis.com/labels']['decalog.wordpress.org/logger']            = 'StackdriverHandler';
		$message['logging.googleapis.com/labels']['decalog.wordpress.org/version']           = DECALOG_VERSION;
		$message['logging.googleapis.com/labels']['decalog.wordpress.org/context/class']     = $record['context']['class'];
		$message['logging.googleapis.com/labels']['decalog.wordpress.org/context/component'] = $record['context']['component'];
		$message['logging.googleapis.com/labels']['decalog.wordpress.org/context/channel']   = $record['channel'];
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
