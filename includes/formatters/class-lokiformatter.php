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
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
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
		$event  = [];
		$stream = [];
		$values = [];
		switch ( $this->template ) {
			case 1:
				$stream['job']      = $this->job;
				$stream['instance'] = gethostname();
				$stream['level']    = $level_class;
				break;
			case 2:
				$stream['job']      = $this->job;
				$stream['instance'] = gethostname();
				$stream['env']      = Environment::stage();
				break;
			case 3:
				$stream['job']      = $this->job;
				$stream['instance'] = gethostname();
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
				$stream['instance'] = gethostname();
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
			$name = $id . ( '' === $id ? '' : $separator ) . $key;
			if ( is_array( $fragment ) ) {
				$result .= $this->build_logline( $fragment, $name );
			}
			if ( is_scalar( $fragment ) ) {
				if ( is_string( $fragment ) ) {
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
