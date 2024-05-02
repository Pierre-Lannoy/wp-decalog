<?php declare(strict_types=1);
/**
 * Elastic Cloud formatter for Monolog
 *
 * Handles all features of Elastic Cloud formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Formatter;

use Decalog\System\Http;
use DLMonolog\Formatter\ElasticsearchFormatter;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\ChannelTypes;

/**
 * Define the Monolog Elastic Cloud formatter.
 *
 * Handles all features of Elastic Cloud formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ElasticCloudFormatter extends ElasticsearchFormatter {

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return array The formatted record.
	 * @since   1.0.0
	 */
	public function format( array $record ): array {
		$record['@timestamp'] = date( 'c' );
		$record['_index']     = $this->index;
		$record['event']['kind']     = 'event';
		$record['event']['category']     = 'process';
		if ( array_key_exists( 'channel', $record ) ) {
			$record['event']['dataset'] = ChannelTypes::$channel_names_en[ strtoupper( $record['channel'] ) ];
			unset( $record['channel'] );
		} else {
			$record['event']['dataset'] = ChannelTypes::$channel_names_en['UNKNOWN'];
		}
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'class', $record['context'] ) ) {
			$record['event']['module'] = $record['context']['class'];
			unset( $record['context']['class'] );
		}
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'component', $record['context'] ) ) {
			$record['event']['provider'] = $record['context']['component'];
			unset( $record['context']['component'] );
		}
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'code', $record['context'] ) ) {
			$record['event']['code'] = $record['context']['code'];
			unset( $record['context']['code'] );
		}

		if ( array_key_exists( 'level', $record ) ) {
			$record['log']['syslog']['severity']['name'] = ucfirst( strtolower( EventTypes::$level_names[ $record['level'] ] ) );
			$record['log']['level'] = ucfirst( strtolower( EventTypes::$level_names[ $record['level'] ] ) );
			unset( $record['level'] );
		}
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'traceID', $record['context'] ) ) {
			$record['trace']['id'] = $record['context']['traceID'];
			unset( $record['context']['traceID'] );
		}

		if ( array_key_exists( 'context', $record ) && array_key_exists( 'instance', $record['context'] ) ) {
			$record['host']['name'] = $record['context']['instance'];
			$record['host']['hostname'] = $record['context']['instance'];
			unset( $record['context']['instance'] );
		}

		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'usersession', $record['extra'] ) ) {
			$record['session']['id'] = $record['extra']['usersession'];
			unset( $record['extra']['usersession'] );
		}

		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'ip', $record['extra'] ) ) {
			$record['client']['address'] = $record['extra']['ip'];
			$record['client']['ip'] = $record['extra']['ip'];
			unset( $record['extra']['ip'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'http_method', $record['extra'] ) ) {
			if ( in_array( strtolower( $record['extra']['http_method'] ), Http::$verbs, true ) ) {
				$record['http']['request']['method'] = $record['extra']['http_method'];
				unset( $record['extra']['http_method'] );
			}
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'referrer', $record['extra'] ) ) {
			$record['http']['request']['referrer'] = $record['extra']['referrer'];
			unset( $record['extra']['referrer'] );
		}

		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'userid', $record['extra'] ) ) {
			$record['user']['id'] = $record['extra']['userid'] ;
			unset( $record['extra']['userid'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'username', $record['extra'] ) ) {
			$record['user']['name']['text'] = $record['extra']['username'];
			unset( $record['extra']['username'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'sitedomain', $record['extra'] ) ) {
			$record['url']['domain'] = $record['extra']['sitedomain'];
			unset( $record['extra']['sitedomain'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'url', $record['extra'] ) ) {
			$record['url']['path'] = $record['extra']['url'];
			unset( $record['extra']['url'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'ua', $record['extra'] ) ) {
			$record['user_agent']['original'] = $record['extra']['ua'];
			unset( $record['extra']['ua'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'server', $record['extra'] ) ) {
			$record['server']['domain'] = $record['extra']['server'];
			unset( $record['extra']['server'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'siteid', $record['extra'] ) ) {
			$record['site']['id'] = $record['extra']['siteid'];
			unset( $record['extra']['siteid'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'sitename', $record['extra'] ) ) {
			$record['site']['name'] = $record['extra']['sitename'];
			unset( $record['extra']['sitename'] );
		}
		return $record;
	}
}
