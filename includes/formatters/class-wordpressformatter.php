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
		$message             = [];
		$values              = array();
		$values['timestamp'] = date( 'Y-m-d H:i:s' );
		if ( array_key_exists( 'level', $record ) ) {
			if ( array_key_exists( $record['level'], self::$levels ) ) {
				$values['level'] = strtolower( self::$levels[ $record['level'] ] );
			}
		}
		if ( array_key_exists( 'channel', $record ) ) {
			$values['channel'] = strtolower( $record['channel'] );
		}
		if ( array_key_exists( 'message', $record ) ) {
			$values['message'] = substr( $record['message'], 0, 1000 );
		}
		// Context formatting.
		if ( array_key_exists( 'context', $record ) ) {
			$context = $record['context'];
			if ( array_key_exists( 'class', $context ) ) {
				if ( in_array( $context['class'], ClassTypes::$classes ) ) {
					$values['class'] = strtolower( $context['class'] );
				}
			}
			if ( array_key_exists( 'component', $context ) ) {
				$values['component'] = substr( $context['component'], 0, 26 );
			}
			if ( array_key_exists( 'version', $context ) ) {
				$values['version'] = substr( $context['version'], 0, 13 );
			}
			if ( array_key_exists( 'code', $context ) ) {
				$values['code'] = (int) $context['code'];
			}
		}
		// Extra formatting.
		if ( array_key_exists( 'extra', $record ) ) {
			$extra = $record['extra'];
			if ( array_key_exists( 'siteid', $extra ) ) {
				$values['site_id'] = (int) $extra['siteid'];
			}
			if ( array_key_exists( 'sitename', $extra ) ) {
				$values['site_name'] = substr( $extra['sitename'], 0, 250 );
			}
			if ( array_key_exists( 'userid', $extra ) ) {
				$values['user_id'] = substr( (string) $extra['userid'], 0, 66 );
			}
			if ( array_key_exists( 'username', $extra ) ) {
				$values['user_name'] = substr( $extra['username'], 0, 250 );
			}
			if ( array_key_exists( 'ip', $extra ) ) {
				$values['remote_ip'] = substr( $extra['ip'], 0, 66 );
			}
			if ( array_key_exists( 'url', $extra ) ) {
				$values['url'] = substr( $extra['url'], 0, 2083 );
			}
			if ( array_key_exists( 'http_method', $extra ) ) {
				if ( in_array( strtolower( $extra['http_method'] ), Http::$verbs ) ) {
					$values['verb'] = strtolower( $extra['http_method'] );
				}
			}
			if ( array_key_exists( 'server', $extra ) ) {
				$values['server'] = substr( $extra['server'], 0, 250 );
			}
			if ( array_key_exists( 'referrer', $extra ) && $extra['referrer'] ) {
				$values['referrer'] = substr( $extra['referrer'], 0, 250 );
			}
			if ( array_key_exists( 'file', $extra ) && $extra['file'] ) {
				$values['file'] = substr( $extra['file'], 0, 250 );
			}
			if ( array_key_exists( 'line', $extra ) && $extra['line'] ) {
				$values['line'] = (int) $extra['line'];
			}
			if ( array_key_exists( 'class', $extra ) && $extra['class'] ) {
				$values['classname'] = substr( $extra['class'], 0, 100 );
			}
			if ( array_key_exists( 'function', $extra ) && $extra['function'] ) {
				$values['function'] = substr( $extra['function'], 0, 100 );
			}
			if ( array_key_exists( 'stack', $extra ) && $extra['stack'] ) {
				$s = serialize($extra['stack']);
				if (strlen($s) < 10000) {
					$values['stack'] = $s;
				} else {
					$values['stack'] = seralize(['This backtrace was not recorded: size exceeds limit.']);
				}
			}
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
