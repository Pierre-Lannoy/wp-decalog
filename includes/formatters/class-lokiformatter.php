<?php declare(strict_types=1);
/**
 * Loki formatter for Monolog
 *
 * Handles all features of Loki formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Formatter;

use Decalog\Plugin\Feature\ClassTypes;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\ChannelTypes;
use Decalog\System\Blog;
use Decalog\System\Environment;
use Decalog\System\Http;
use Decalog\System\User;
use Decalog\System\UserAgent;
use DLMonolog\Formatter\FormatterInterface;
use DLMonolog\Logger;
use PODeviceDetector\API\Device;
use Decalog\System\Hash;

/**
 * Define the Monolog Loki formatter.
 *
 * Handles all features of Loki formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class LokiFormatter implements FormatterInterface {

	/**
	 * Labels template.
	 *
	 * @since  2.4.0
	 * @var    integer    $template    The label templates ID.
	 */
	protected $template;

	/**
	 * Fixed job name.
	 *
	 * @since  2.4.0
	 * @var    string    $job    The fixed job name.
	 */
	protected $job;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   int     $model      The model to use for labels.
	 * @param   string  $id         The job id.
	 * @since    2.4.0
	 */
	public function __construct( int $model, string $id ) {
		$this->template = $model;
		$this->job      = $id;
	}

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   2.4.0
	 */
	public function format( array $record ): string {
		if ( array_key_exists( 'level', $record ) ) {
			$level_class = strtolower( EventTypes::$level_names[ $record['level'] ] );
		} else {
			$level_class = 'unknown';
		}
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'traceID', $record['context'] ) ) {
			$record['traceID'] = $record['context']['traceID'];
			unset( $record['context']['traceID'] );
		}
		if ( array_key_exists( 'extra', $record ) && array_key_exists( 'usersession', $record['extra'] ) ) {
			$record['sessionID'] = $record['extra']['usersession'];
			unset( $record['extra']['usersession'] );
		}
		unset( $record['context']['phase'] );
		$event  = [];
		$stream = [];
		$values = [];
		switch ( $this->template ) {
			case 1:
				$stream['job']      = $this->job;
				$stream['instance'] = DECALOG_INSTANCE_NAME;
				$stream['level']    = $level_class;
				break;
			case 2:
				$stream['job']      = $this->job;
				$stream['instance'] = DECALOG_INSTANCE_NAME;
				$stream['env']      = Environment::stage();
				break;
			case 3:
				$stream['job']      = $this->job;
				$stream['instance'] = DECALOG_INSTANCE_NAME;
				$stream['version']  = Environment::wordpress_version_text( true );
				break;
			case 4:
				$stream['job']   = $this->job;
				$stream['level'] = $level_class;
				$stream['env']   = Environment::stage();
				break;
			case 5:
				$stream['job']  = $this->job;
				$stream['site'] = Blog::get_current_blog_id( 0 );
				break;
			default:
				$stream['job']      = $this->job;
				$stream['instance'] = DECALOG_INSTANCE_NAME;
		}
		$date             = new \DateTime();
		$values[]         = (string) ( $date->format( 'Uu' ) * 1000 );
		$values[]         = $this->build_logline( $record );
		$event['streams'] = [
			(object) [
				'stream' => (object) $stream,
				'values' => [ $values ],
			],
		];
		// phpcs:ignore
		return wp_json_encode( (object) $event );
	}

	/**
	 * Recursively build the log line.
	 *
	 * @param   array   $fragments  A (sub)set of values to format.
	 * @param   string  $id         Optional. The left part of the keys.
	 * @param   string  $separator  Optional. The keys separator.
	 * @return  string  The formatted (sub)log line.
	 * @since   2.4.0
	 */
	protected function build_logline( array $fragments, string $id = '', string $separator = '_' ): string {
		$result = '';
		foreach ( $fragments as $key => $fragment ) {
			if ( is_integer( $key ) ) {
				$key = (string) $key;
				if ( 1 === strlen( $key ) ) {
					$key = '0' . $key;
				}
			}
			$name = $id . ( '' === $id ? '' : $separator ) . $key;
			if ( is_array( $fragment ) ) {
				$result .= ( '' === $result ? '' : ' ' ) . $this->build_logline( $fragment, $name );
			}
			if ( is_scalar( $fragment ) ) {
				if ( in_array( $key, [ 'traceID', 'sessionID', 'environment', 'class', 'channel', 'function', 'ip', 'server', 'level_name', 'http_method', 'version', 'file', 'referrer' ], true ) ) {
					$result .= ( '' === $result ? '' : ' ' ) . $name . '=' . str_replace( '"', '', $fragment ) . '';
				} elseif ( is_string( $fragment ) ) {
					$result .= ( '' === $result ? '' : ' ' ) . $name . '="' . str_replace( '"', '\"', $fragment ) . '"';
				} else {
					$result .= ( '' === $result ? '' : ' ' ) . $name . '=' . $fragment;
				}
			}
		}
		return $result;
	}

	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   2.4.0
	 */
	public function formatBatch( array $records ): string {
		if ( 0 < count( $records ) ) {
			return $this->format( $records[0] );
		}
		return '';
	}
}
