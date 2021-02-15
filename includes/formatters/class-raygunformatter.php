<?php declare(strict_types=1);
/**
 * Raygun formatter for Monolog
 *
 * Handles all features of Raygun formatter for Monolog.
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
use Decalog\System\User;
use Decalog\System\UserAgent;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use PODeviceDetector\API\Device;
use Decalog\System\Hash;

/**
 * Define the Monolog Raygun formatter.
 *
 * Handles all features of Raygun formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class RaygunFormatter implements FormatterInterface {

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   2.4.0
	 */
	public function format( array $record ): string {
		$event                 = [];
		$detail                = [];
		$error                 = [];
		$data                  = [];
		$stacktrace            = [];
		$environment           = [];
		$request               = [];
		$response              = [];
		$user                  = [];
		$meta                  = [];
		$detail['tags']        = [ Environment::stage() ];
		$event['occurredOn']   = gmdate( 'c' );
		$client                = [
			'name'      => DECALOG_PRODUCT_NAME,
			'version'   => DECALOG_VERSION,
			'clientUrl' => DECALOG_PRODUCT_URL,
		];
		$detail['machineName'] = gethostname();
		$detail['version']     = Environment::wordpress_version_text( true );
		if ( array_key_exists( 'level', $record ) ) {
			$level_class = ucfirst( strtolower( EventTypes::$level_names[ $record['level'] ] ) );
		}
		else {
			$level_class = 'Unknown';
		}
		if ( array_key_exists( 'message', $record ) ) {
			$error['message'] = '[' . $level_class . '] ' . substr( $record['message'], 0, 1000 );
		}
		if ( array_key_exists( 'channel', $record ) ) {
			$detail['tags'][] = strtolower( ChannelTypes::$channel_names_en[ strtoupper( $record['channel'] ) ] );
			$meta['channel']  = ChannelTypes::$channel_names_en[ strtoupper( $record['channel'] ) ];
		}
		$meta['level'] = $level_class;
		// Context formatting.
		if ( array_key_exists( 'context', $record ) ) {
			$context = $record['context'];
			if ( array_key_exists( 'component', $context ) ) {
				$error['className'] = str_replace( [ ' ', '-' ], '', $context['component'] ) . $level_class;
			}
			if ( array_key_exists( 'class', $context ) ) {
				$detail['tags'][] = strtolower( $context['class'] );
				$meta['class']    = ucfirst( strtolower( $context['class'] ) );
			}
			if ( array_key_exists( 'code', $context ) ) {
				$meta['code'] = $context['code'];
			}
			if ( array_key_exists( 'component', $context ) ) {
				$detail['tags'][] = strtolower( $context['component'] );
				if ( array_key_exists( 'version', $context ) ) {
					$meta['component'] = $context['component'] . ' ' . $context['version'];
				} else {
					$meta['component'] = $context['component'];
				}
			}
		}
		// Extra formatting.
		if ( array_key_exists( 'extra', $record ) ) {
			$extra = $record['extra'];
			if ( array_key_exists( 'userid', $extra ) && is_scalar( $extra['userid'] ) ) {
				if ( '{' === substr( (string) $extra['userid'], 0, 1 ) || 0 === (int) $extra['userid'] ) {
					$user['isAnonymous'] = true;
				} else {
					$user['identifier']  = substr( (string) $extra['userid'], 0, 66 );
					$user['isAnonymous'] = false;
					if ( array_key_exists( 'username', $extra ) && is_string( $extra['username'] ) ) {
						$user['fullName'] = substr( $extra['username'], 0, 250 );
					}
				}
			}
			if ( array_key_exists( 'ip', $extra ) && is_string( $extra['ip'] ) ) {
				$request['iPAddress'] = substr( $extra['ip'], 0, 66 );
			}
			if ( array_key_exists( 'http_method', $extra ) && is_string( $extra['http_method'] ) ) {
				if ( in_array( strtolower( $extra['http_method'] ), Http::$verbs, true ) ) {
					$request['httpMethod'] = strtoupper( $extra['http_method'] );
				}
			}
			if ( array_key_exists( 'url', $extra ) && is_string( $extra['url'] ) ) {
				$request['url'] = substr( $extra['url'], 0, 2083 );
			}
			if ( array_key_exists( 'server', $extra ) && is_string( $extra['server'] ) ) {
				$request['hostName'] = substr( $extra['server'], 0, 250 );
			}
			if ( class_exists( 'PODeviceDetector\API\Device' ) && array_key_exists( 'ua', $extra ) && $extra['ua'] && is_string( $extra['ua'] ) ) {
				$ua = UserAgent::get( $extra['ua'] );
				if ( 'UNK' !== $ua->os_name && 'UNK' !== $ua->os_version ) {
					$environment['osVersion'] = $ua->os_name . ' ' . $ua->os_version;
				}
				if ( $ua->class_is_bot ) {
					$environment['deviceManufacturer'] = $ua->bot_producer_name;
					$environment['deviceName']         = $ua->bot_name;
				} else {
					$environment['deviceManufacturer'] = ( '' !== $ua->brand_name ? $ua->brand_name : 'generic' );
					if ( '' !== $ua->model_name ) {
						$environment['deviceName'] = $ua->model_name;
					}
					$environment['browserName'] = $ua->client_name . ' ' . $ua->client_version;
				}
			}
		}
		if ( 0 < count( $client ) ) {
			$detail['client'] = (object) $client;
		}
		if ( 0 < count( $data ) ) {
			$error['data'] = (object) $data;
		}
		if ( 0 < count( $stacktrace ) ) {
			$error['stackTrace'] = (object) $stacktrace;
		}
		if ( 0 < count( $error ) ) {
			$detail['error'] = (object) $error;
		}
		if ( 0 < count( $environment ) ) {
			$detail['environment'] = (object) $environment;
		}
		if ( 0 < count( $request ) ) {
			$detail['request'] = (object) $request;
		}
		if ( 0 < count( $response ) ) {
			$detail['response'] = (object) $response;
		}
		if ( 0 < count( $user ) ) {
			$detail['user'] = (object) $user;
		}
		if ( 0 < count( $meta ) ) {
			$detail['userCustomData'] = (object) $meta;
		}
		$event['details'] = (object) $detail;
		// phpcs:ignore
		return serialize( (object) $event );
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
