<?php declare(strict_types=1);
/**
 * Google Analytics formatter for Monolog
 *
 * Handles all features of Google Analytics formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Formatter;

use Decalog\Plugin\Feature\ClassTypes;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\ChannelTypes;
use Decalog\System\Environment;
use Decalog\System\Http;
use Decalog\System\UserAgent;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use PODeviceDetector\API\Device;

/**
 * Define the Monolog Google Analytics formatter.
 *
 * Handles all features of Google Analytics formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class GAnalyticsFormatter implements FormatterInterface {

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   2.4.0
	 */
	public function format( array $record ): string {
		$line        = [];
		$line['cid'] = 0;
		$line['an']  = str_replace( '/', '_', str_replace( [ 'https://', 'http://' ], '', get_site_url() ) );
		$line['dl']  = Environment::get_current_url();
		$line['av']  = Environment::wordpress_version_text( true );
		if ( array_key_exists( 'channel', $record ) ) {
			$line['cd'] = ChannelTypes::$channel_names_en[ strtoupper( $record['channel'] ) ];
		} else {
			$line['cd'] = ChannelTypes::$channel_names_en['UNKNOWN'];
		}
		if ( array_key_exists( 'level', $record ) ) {
			if ( array_key_exists( $record['level'], EventTypes::$level_names ) ) {
				$level = ucfirst( strtolower( EventTypes::$level_names[ $record['level'] ] ) );
			} else {
				$level = 'Unknown';
			}
		} else {
			$level = 'Unknown';
		}
		if ( array_key_exists( 'context', $record ) ) {
			$context = $record['context'];
			if ( array_key_exists( 'class', $context ) ) {
				$class       = ucfirst( strtolower( strtolower( $context['class'] ) ) );
				$line['exf'] = ( 'PHP' === strtoupper( $context['class'] ) ? 1 : 0 );
			}
		} else {
			$class = '';
		}
		$line['exd'] = $class . $level;
		if ( array_key_exists( 'extra', $record ) ) {
			$extra = $record['extra'];
			if ( array_key_exists( 'userid', $extra ) && is_scalar( $extra['userid'] ) ) {
				$line['cid'] = substr( (string) $extra['userid'], 0, 66 );
			}
			if ( array_key_exists( 'ip', $extra ) && is_string( $extra['ip'] ) ) {
				$line['uip'] = substr( $extra['ip'], 0, 66 );
			}
			if ( array_key_exists( 'ua', $extra ) && is_string( $extra['ua'] ) ) {
				$line['ua'] = $extra['ua'];
			}
		}
		// phpcs:ignore
		return serialize( $line );
	}
	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   2.4.0
	 */
	public function formatBatch( array $records ): string {
		$messages = [];
		foreach ( $records as $record ) {
			$messages[] = maybe_unserialize( $this->format( $record ) );
		}
		// phpcs:ignore
		return serialize( $messages );
	}
}
