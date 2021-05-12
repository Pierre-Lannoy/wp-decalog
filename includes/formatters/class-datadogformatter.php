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
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use PODeviceDetector\API\Device;

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
	 * List of the available level severities.
	 *
	 * @since   3.0.0
	 * @var string[] $level_names Logging levels severities.
	 */
	public static $level_severities = [
		Logger::DEBUG     => 'info',
		Logger::INFO      => 'info',
		Logger::NOTICE    => 'info',
		Logger::WARNING   => 'warning',
		Logger::ERROR     => 'error',
		Logger::CRITICAL  => 'error',
		Logger::ALERT     => 'error',
		Logger::EMERGENCY => 'error',
	];

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   3.0.0
	 */
	public function format( array $record ): string {
		$event           = [];
		$event['dd']     = [
			'trace_id' => DECALOG_TRACEID,
		];
		$event['source'] = DECALOG_PRODUCT_NAME;
		$event['host']   = gethostname();
		if ( array_key_exists( 'channel', $record ) ) {
			$event['service'] = ChannelTypes::$channel_names_en[ strtoupper( $record['channel'] ) ];
		} else {
			$event['service'] = ChannelTypes::$channel_names_en['UNKNOWN'];
		}
		if ( array_key_exists( 'message', $record ) ) {
			$event['message'] = $record['message'];
		} else {
			$event['message'] = '<no messsage>';
		}
		if ( array_key_exists( 'level', $record ) ) {
			if ( array_key_exists( $record['level'], self::$level_severities ) ) {
				$event['message'] = EventTypes::$level_emojis[ $record['level'] ] . ' ' . ucfirst( strtolower( EventTypes::$level_names[ $record['level'] ] ) ) . ' ¶ ' . $event['message'];
				$event['status']  = self::$level_severities[ $record['level'] ];
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
			if ( array_key_exists( 'code', $record['context'] ) ) {
				$event['message'] = str_replace( '¶', '[' . $record['context']['code'] . ']', $event['message'] );
			}
		}
		if ( array_key_exists( 'extra', $record ) && 0 < count( $record['extra'] ) ) {
			$event['extra'] = $record['extra'];
		}

		// phpcs:ignore
		return parent::format( $event );
	}
}
