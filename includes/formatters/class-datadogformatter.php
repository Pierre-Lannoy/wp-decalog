<?php declare(strict_types=1);
/**
 * Datadog formatter for Monolog
 *
 * Handles all features of Datadog formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Formatter;

use Decalog\Plugin\Feature\ClassTypes;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\ChannelTypes;
use Decalog\System\Environment;
use Decalog\System\Http;
use Decalog\System\UserAgent;
use DLMonolog\Formatter\JsonFormatter;
use DLMonolog\Logger;

/**
 * Define the Monolog Datadog formatter.
 *
 * Handles all features of Datadog formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class DatadogFormatter extends JsonFormatter {

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   3.0.0
	 */
	public function format( array $record ): string {
		$event                = [];
		$event['ddsource']    = 'WordPress';
		$event['ddtags']      = 'wp_env:' . strtolower( Environment::stage() ) . ',wp_version:' . strtolower( Environment::wordpress_version_text( true ) );
		$event['dd.trace_id'] = base_convert( substr( DECALOG_TRACEID, 16, 16 ), 16, 10 );
		$event['host']        = DECALOG_INSTANCE_NAME;
		if ( array_key_exists( 'channel', $record ) ) {
			$event['service'] = strtolower( str_replace( ' ', '_', ChannelTypes::$channel_names_en[ strtoupper( $record['channel'] ) ] ) );
		} else {
			$event['service'] = str_replace( ' ', '_', ChannelTypes::$channel_names_en['UNKNOWN'] );
		}
		if ( array_key_exists( 'message', $record ) ) {
			$event['message'] = $record['message'];
		} else {
			$event['message'] = '<no message>';
		}
		if ( array_key_exists( 'level', $record ) ) {
			if ( array_key_exists( $record['level'], EventTypes::$level_names ) ) {
				$event['status']  = strtolower( EventTypes::$level_names[ $record['level'] ] );
			} else {
				$event['status'] = 'error';
			}
		} else {
			$event['status'] = 'error';
		}
		if ( array_key_exists( 'context', $record ) && 0 < count( $record['context'] ) ) {
			$event['context'] = $record['context'];
			if ( array_key_exists( 'phase', $event['context'] ) ) {
				unset( $event['context']['phase'] );
			}
			if ( array_key_exists( 'traceID', $event['context'] ) ) {
				unset( $event['context']['traceID'] );
			}
			if ( array_key_exists( 'code', $record['context'] ) ) {
				$event['message'] = str_replace( '¶', '[' . $record['context']['code'] . ']', $event['message'] );
			}
		}
		if ( array_key_exists( 'extra', $record ) && 0 < count( $record['extra'] ) ) {
			$event['extra'] = $record['extra'];
		}
		if ( array_key_exists( 'extended', $record ) && 0 < count( $record['extended'] ) ) {
			foreach ( $record['extended'] as $key => $value ) {
				if ( array_key_exists( $key, $event ) ) {
					$event[ 'wp_' . $key ] = $event[ $key ];
				}
				$event[ $key ] = $value;
			}
			unset( $event['extended'] );
		}

		return parent::format( $event );
	}
}
