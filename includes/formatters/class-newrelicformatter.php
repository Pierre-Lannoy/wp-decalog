<?php declare(strict_types=1);
/**
 * New Relic formatter for Monolog
 *
 * Handles all features of New Relic formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
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
 * Define the Monolog New Relic formatter.
 *
 * Handles all features of New Relic formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */
class NewRelicFormatter extends JsonFormatter {

	/**
	 * List of the available level severities.
	 *
	 * @since   3.2.0
	 * @var string[] $level_names Logging levels severities.
	 */
	public static $level_severities = [
		Logger::DEBUG     => 'DEBUG',
		Logger::INFO      => 'INFO',
		Logger::NOTICE    => 'INFO',
		Logger::WARNING   => 'WARN',
		Logger::ERROR     => 'ERROR',
		Logger::CRITICAL  => 'ERROR',
		Logger::ALERT     => 'ERROR',
		Logger::EMERGENCY => 'ERROR',
	];

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   3.2.0
	 */
	public function format( array $record ): string {
		$event              = [];
		$event['timestamp'] = $record['datetime']->getTimestamp();
		$event['hostname']  = DECALOG_INSTANCE_NAME;
		if ( array_key_exists( 'channel', $record ) ) {
			$event['service'] = ChannelTypes::$channel_names_en[ strtoupper( $record['channel'] ) ];
		} else {
			$event['service'] = ChannelTypes::$channel_names_en['UNKNOWN'];
		}
		if ( array_key_exists( 'message', $record ) ) {
			$event['message'] = str_replace( [ '"', '&ldquo;', '&rdquo;', ], '“', $record['message'] );
			$event['message'] = str_replace( '\'', '`', $event['message'] );
		} else {
			$event['message'] = '<no message>';
		}
		if ( array_key_exists( 'level', $record ) ) {
			if ( array_key_exists( $record['level'], self::$level_severities ) ) {
				$event['level'] = self::$level_severities[ $record['level'] ];
			} else {
				$event['level'] = 'ERROR';
			}
		} else {
			$event['level'] = 'ERROR';
		}
		$event['Trace.id']             = DECALOG_TRACEID;
		$event['Environment.platform'] = 'WordPress';
		$event['Environment.stage']    = strtolower( Environment::stage() );
		$event['Environment.version']  = Environment::wordpress_version_text( true );

		// Context formatting.
		if ( array_key_exists( 'context', $record ) ) {
			$context = $record['context'];
			if ( array_key_exists( 'class', $context ) ) {
				if ( in_array( $context['class'], ClassTypes::$classes, true ) ) {
					$event['Context.class'] = strtolower( $context['class'] );
				}
			}
			if ( array_key_exists( 'component', $context ) ) {
				$event['Context.component'] = substr( $context['component'], 0, 26 );
			}
			if ( array_key_exists( 'version', $context ) ) {
				$event['Context.version'] = substr( $context['version'], 0, 13 );
			}
			if ( array_key_exists( 'code', $context ) ) {
				$event['Context.code'] = (int) $context['code'];
			}
		}

		// Extra formatting.
		if ( array_key_exists( 'extra', $record ) ) {
			$extra = $record['extra'];
			if ( array_key_exists( 'siteid', $extra ) ) {
				$event['Site.id'] = (int) $extra['siteid'];
			}
			if ( array_key_exists( 'sitename', $extra ) && is_string( $extra['sitename'] ) ) {
				$event['Site.name'] = substr( $extra['sitename'], 0, 250 );
			}
			if ( array_key_exists( 'userid', $extra ) && is_scalar( $extra['userid'] ) ) {
				$event['User.id'] = substr( (string) $extra['userid'], 0, 66 );
			}
			if ( array_key_exists( 'username', $extra ) && is_string( $extra['username'] ) ) {
				$event['User.name'] = substr( $extra['username'], 0, 250 );
			}
			if ( array_key_exists( 'usersession', $extra ) && is_scalar( $extra['usersession'] ) ) {
				$event['User.session'] = substr( (string) $extra['usersession'], 0, 64 );
			}
			if ( array_key_exists( 'ip', $extra ) && is_string( $extra['ip'] ) ) {
				$event['Request.remoteip'] = substr( $extra['ip'], 0, 66 );
			}
			if ( array_key_exists( 'url', $extra ) && is_string( $extra['url'] ) ) {
				$event['Request.url'] = substr( $extra['url'], 0, 2083 );
			}
			if ( array_key_exists( 'http_method', $extra ) && is_string( $extra['http_method'] ) ) {
				if ( in_array( strtolower( $extra['http_method'] ), Http::$verbs, true ) ) {
					$event['Request.verb'] = $extra['http_method'];
				}
			}
			if ( array_key_exists( 'server', $extra ) && is_string( $extra['server'] ) ) {
				$event['Request.server'] = substr( $extra['server'], 0, 250 );
			}
			if ( array_key_exists( 'referrer', $extra ) && $extra['referrer'] && is_string( $extra['referrer'] ) ) {
				$event['Request.referrer'] = substr( $extra['referrer'], 0, 250 );
			}
			if ( array_key_exists( 'ua', $extra ) && $extra['ua'] && is_string( $extra['ua'] ) ) {
				$event['Request.useragent'] = substr( $extra['ua'], 0, 1024 );
			}
			if ( array_key_exists( 'file', $extra ) && $extra['file'] && is_string( $extra['file'] ) ) {
				$event['Source.file'] = substr( $extra['file'], 0, 250 );
			}
			if ( array_key_exists( 'line', $extra ) && $extra['line'] ) {
				$event['Source.line'] = (int) $extra['line'];
			}
			if ( array_key_exists( 'class', $extra ) && $extra['class'] && is_string( $extra['class'] ) ) {
				$event['Source.classname'] = substr( $extra['class'], 0, 100 );
			}
			if ( array_key_exists( 'function', $extra ) && $extra['function'] && is_string( $extra['function'] ) ) {
				$event['Source.function'] = substr( $extra['function'], 0, 100 );
			}
		}

		return parent::format( $event );
	}
}
