<?php
/**
 * Abstract tracing handler for Monolog
 *
 * Handles all features of abstract tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

use Decalog\Listener\AbstractListener;
use Decalog\Plugin\Feature\ChannelTypes;
use Decalog\Plugin\Feature\DTracer;
use Decalog\Plugin\Feature\Log;
use Decalog\Storage\AbstractStorage;
use Decalog\System\Blog;
use Decalog\System\Environment;
use Decalog\System\Hash;
use Decalog\System\Http;
use Decalog\System\Option;
use Decalog\System\User;
use Decalog\System\UUID;
use DLMonolog\Logger;
use DLMonolog\Handler\AbstractProcessingHandler;
use DLMonolog\Handler\HandlerInterface;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;
use Decalog\System\Cache;
use Jaeger\Thrift\Span as JSpan;
use Jaeger\Thrift\Process as JProcess;
use Jaeger\Thrift\Batch as JBatch;
use Jaeger\Thrift\TagType as JTagType;
use Jaeger\Thrift\Tag as JTag;
use Thrift\Protocol\TBinaryProtocol as TProtocol;
use Thrift\Transport\TMemoryBuffer as TTransport;

/**
 * Define the Monolog abstract tracing handler.
 *
 * Handles all features of abstract tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
abstract class AbstractTracingHandler extends AbstractProcessingHandler {

	/**
	 * Logger UUID.
	 *
	 * @since  3.0.0
	 * @var    string  $uuid       The UUID of the logger.
	 */
	protected $uuid = null;

	/**
	 * Logger format.
	 *
	 * @since  3.0.0
	 * @var    integer  $format       The format of the logger.
	 */
	protected $format = null;

	/**
	 * The storage if needed.
	 *
	 * @since  3.0.0
	 * @var    AbstractStorage  $storage       The format of the logger.
	 */
	protected $storage = null;

	/**
	 * Post args.
	 *
	 * @since  3.0.0
	 * @var    array    $post_args    The args for the post request.
	 */
	protected $post_args = [];

	/**
	 * URL to post.
	 *
	 * @since  3.0.0
	 * @var    string    $endpoint    The url.
	 */
	protected $endpoint = '';

	/**
	 * Verb to use.
	 *
	 * @since  3.0.0
	 * @var    string    $verb    The verb to use.
	 */
	protected $verb = 'POST';

	/**
	 * Privacy options.
	 *
	 * @since  3.0.0
	 * @var    array    $privacy    Privacy options.
	 */
	protected $privacy = [];

	/**
	 * Activated processors.
	 *
	 * @since  3.0.0
	 * @var    array    $processors    Activated processors.
	 */
	protected $processors = [];

	/**
	 * The traces.
	 *
	 * @since  3.0.0
	 * @var    array    $traces    The traces.
	 */
	protected $traces = [];

	/**
	 * Error control.
	 *
	 * @since  3.0.0
	 * @var    boolean    $error_control    Error control.
	 */
	protected $error_control = true;

	/**
	 * Running monitors.
	 *
	 * @since  3.0.0
	 * @var    array    $running    Running monitors.
	 */
	private static $running = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   int     $format     The format in which to push data:
	 *                              100 - Zipkin.
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @since    3.0.0
	 */
	public function __construct( $uuid, $format, $sampling ) {
		$this->uuid   = $uuid;
		$this->format = $format;
		parent::__construct( Logger::EMERGENCY, true );
		$this->post_args = [
			'headers'    => [
				'User-Agent'     => Http::user_agent(),
				'Decalog-No-Log' => 'outbound',
			],
			'user-agent' => Http::user_agent(),
		];
		if ( ! in_array( $this->uuid, self::$running, true ) ) {
			// phpcs:ignore
			if ( $sampling >= mt_rand( 1, 1000 ) ) {
				add_action( 'shutdown', [ $this, 'close' ], AbstractListener::$tracer_priority, 0 );
				$loggers = Option::network_get( 'loggers' );
				if ( array_key_exists( $this->uuid, $loggers ) ) {
					if ( array_key_exists( 'privacy', $loggers[ $this->uuid ] ) ) {
						$this->privacy = $loggers[ $this->uuid ]['privacy'];
					}
					if ( array_key_exists( 'processors', $loggers[ $this->uuid ] ) ) {
						$this->processors = $loggers[ $this->uuid ]['processors'];
					}
				}
			}
			self::$running[] = $this->uuid;
		}
	}

	/**
	 * Applies Privacy and Processors options.
	 *
	 * @since    3.0.0
	 */
	private function filter_process(): void {
		$remove = [ 'php.', 'http.', 'wp.' ];
		foreach ( $this->processors as $processor ) {
			switch ( $processor ) {
				case 'IntrospectionProcessor':
					$remove = array_diff( $remove, [ 'php.' ] );
					break;
				case 'WWWProcessor':
					$remove = array_diff( $remove, [ 'http.' ] );
					break;
				case 'WordpressProcessor':
					$remove = array_diff( $remove, [ 'wp.' ] );
					break;
			}
		}
		foreach ( $this->traces as $index => $span ) {
			if ( array_key_exists( 'tags', $span ) ) {
				if ( 0 === count( $remove ) || ! isset( $span['parentId'] ) || 'Server' === $span['localEndpoint']['serviceName'] ) {
					$new_tags = $span['tags'];
				} else {
					$new_tags = [];
					foreach ( $span['tags'] as $key => $value ) {
						$add = true;
						foreach ( $remove as $r ) {
							if ( false !== strpos( $key, $r ) ) {
								$add = false;
							}
						}
						if ( $add ) {
							$new_tags[ $key ] = $value;
						}
					}
				}
				foreach ( $new_tags as $key => $value ) {
					if ( ( $this->privacy['obfuscation'] && false !== strpos( $key, 'remoteip' ) ) ||
						 ( $this->privacy['pseudonymization'] && false !== strpos( $key, 'userid' ) ) ) {
						$new_tags[ $key ] = Hash::simple_hash( $value );
					}
					if ( is_null( $value ) || ( is_array( $value ) && 0 === count( $value ) ) || ( is_string( $value ) && '' === $value ) ) {
						unset( $new_tags[ $key ] );
					}
					if ( is_numeric( $value ) ) {
						$new_tags[ $key ] = (string) $new_tags[ $key ];
					}
				}
				if ( 0 === count( $new_tags ) ) {
					unset( $this->traces[ $index ]['tags'] );
				} else {
					$this->traces[ $index ]['tags'] = $new_tags;
				}
			}
		}
	}

	/**
	 * Computes Zipkin format.
	 *
	 * @return  string  The formatted body, ready to send.
	 * @since    3.0.0
	 */
	private function zipkin_format(): string {
		return wp_json_encode( $this->traces );
	}

	/**
	 * Computes jaeger.thrift format.
	 *
	 * @return  string  The formatted body, ready to send.
	 * @since    3.0.0
	 */
	private function jaeger_format(): string {
		$spans   = [];
		$process = new JProcess(
			[
				'serviceName' => 'WP',
			]
		);
		foreach ( $this->traces as $span ) {
			$s = [
				'traceIdLow'  => (int) base_convert( substr( $span['traceId'], 16, 16 ), 16, 10 ),
				'traceIdHigh' => (int) base_convert( substr( $span['traceId'], 0, 16 ), 16, 10 ),
				'spanId'      => (int) base_convert( $span['id'], 16, 10 ),
				'startTime'   => (int) $span['timestamp'],
				'duration'    => (int) $span['duration'],
				'flags'       => 1,
			];
			if ( isset( $span['parentId'] ) ) {
				$s['parentSpanId']  = (int) base_convert( $span['parentId'], 16, 10 );
				$s['operationName'] = $span['localEndpoint']['serviceName'] . ' [' . $span['name'] . ']';

			} else {
				$s['parentSpanId']  = 0;
				$s['operationName'] = $span['localEndpoint']['serviceName'] . ' [' . str_replace( 'CALL:', '', $span['name'] . ']' );
			}
			if ( isset( $span['tags'] ) && is_array( $span['tags'] ) && 0 < count( $span['tags'] ) ) {
				foreach ( $span['tags'] as $key => $tag ) {
					$s['tags'][] = new JTag(
						[
							'key'   => $key,
							'vType' => JTagType::STRING,
							'vStr'  => (string) $tag,
						]
					);
				}
			}
			$spans[] = new JSpan( $s );
		}
		$batch    = new JBatch(
			[
				'spans'   => $spans,
				'process' => $process,
			]
		);
		$protocol = new TProtocol( new TTransport() );
		$batch->write( $protocol );
		return $protocol->getTransport()->getBuffer();
	}

	/**
	 * Get the channel tag.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel tag.
	 * @since 3.0.0
	 */
	private function channel_tag( $id = 0 ) {
		if ( $id >= count( ChannelTypes::$channels ) ) {
			$id = 0;
		}
		return ChannelTypes::$channels[ $id ];
	}

	/**
	 * Computes DD/0.3 format.
	 *
	 * @return  string  The formatted body, ready to send.
	 * @since    3.0.0
	 */
	private function datadog_format(): string {
		$spans = [];
		foreach ( $this->traces as $span ) {
			$s = [
				'type'      => 'web',
				'start'     => (int) ( $span['timestamp'] * 1000 ),
				'duration'  => (int) ( $span['duration'] * 1000 ),
				'parent_id' => (int) base_convert( $span['parentId'] ?? 0, 16, 10 ),
				'span_id'   => (int) base_convert( $span['id'], 16, 10 ),
				'trace_id'  => '¶' . base_convert( substr( $span['traceId'], 16, 16 ), 16, 10 ) . '¶',
				'service'   => substr( strtolower( str_replace( ' ', '_', ChannelTypes::$channel_names_en[ strtoupper( $this->channel_tag( Environment::exec_mode() ) ) ] ) ), 0, 100 ),
				'resource'  => substr( ucwords( $span['localEndpoint']['serviceName'] ), 0, 100 ),
				'name'      => substr( $span['name'], 0, 100 ),
			];
			if ( isset( $span['tags'] ) && is_array( $span['tags'] ) && 0 < count( $span['tags'] ) ) {
				$s['meta'] = $span['tags'];
			}
			if ( 0 === $s['parent_id'] ) {
				unset( $s['parent_id'] );
			}
			$spans[] = '[' . str_replace( [ '"\u00b6', '\u00b6"' ], [ '', '' ], wp_json_encode( $s ) ) . ']';
		}
		return '[' . implode( ',', $spans ) . ']';
	}

	/**
	 * Transform a flat span array to a hierarchical one..
	 *
	 * @param   array   $spans  The spans in a flat-array format.
	 * @return  array  The hierarchic array of spans.
	 * @since    3.0.0
	 */
	private function get_hierarchy( $spans ) {
		$hierarchy = [];
		foreach ( $spans as $span ) {
			$span['subspans']              = [];
			$hierarchy[ $span['span_id'] ] = $span;
		}
		foreach ( $spans as &$span ) {
			$hierarchy[ $span['parent_id'] ]['subspans'][] = &$hierarchy[ $span['span_id'] ];
			unset( $hierarchy[ $span['span_id'] ]['span_id'] );
			unset( $hierarchy[ $span['span_id'] ]['parent_id'] );
		}
		foreach ( $hierarchy as $parent => $data ) {
			if ( 0 !== $parent ) {
				unset( $hierarchy[ $parent ] );
			}
		}
		return isset( $hierarchy[0], $hierarchy[0]['subspans'] ) ? $hierarchy[0]['subspans'] : [];
	}

	/**
	 * Computes DecaLog specific format.
	 *
	 * @return  array  The formatted body array, ready to store.
	 * @since    3.0.0
	 */
	private function decalog_format(): array {
		$trace              = [];
		$trace['trace_id']  = DECALOG_TRACEID;
		$trace['timestamp'] = date( 'Y-m-d H:i:s' );
		$trace['channel']   = strtolower( $this->channel_tag( Environment::exec_mode() ) );
		$trace['duration']  = 0;
		foreach ( $this->traces as $span ) {
			if ( $trace['duration'] < (int) $span['duration'] ) {
				$trace['duration'] = (int) $span['duration'];
			}
		}
		$trace['duration']  = (int) ( $trace['duration'] / 1000 );
		$trace['scount']    = count( $this->traces );
		$trace['site_id']   = Blog::get_current_blog_id( 0 );
		$trace['site_name'] = Blog::get_current_blog_name();
		$trace['user_id']   = User::get_current_user_id( 0 );
		$trace['user_name'] = User::get_current_user_name();
		if ( 0 !== (int) $trace['user_id'] ) {
			$trace['user_session'] = Hash::simple_hash( wp_get_session_token(), false );
		} else {
			$trace['user_session'] = '';
		}
		if ( $this->privacy['pseudonymization'] ) {
			if ( $trace['user_id'] > 0 ) {
				$trace['user_id'] = Hash::simple_hash( (string) $trace['user_id'] );
				if ( array_key_exists( 'user_name', $trace ) ) {
					$trace['user_name'] = Hash::simple_hash( $trace['user_name'] );
				}
			}
		}
		$spans = [];
		foreach ( $this->traces as $span ) {
			$s       = [
				'start'     => (int) $span['timestamp'],
				'duration'  => (int) $span['duration'],
				'parent_id' => $span['parentId'] ?? 0,
				'span_id'   => $span['id'],
				'resource'  => ucwords( $span['localEndpoint']['serviceName'] ),
				'name'      => $span['name'],
			];
			$spans[] = $s;
		}
		$trace['spans'] = wp_json_encode( $this->get_hierarchy( $spans ) );
		return $trace;
	}

	/**
	 * {@inheritdoc}
	 */
	public function close(): void {
		$tracer       = new DTracer( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$this->traces = $tracer->traces();
		$this->filter_process();
		switch ( $this->format ) {
			case 100:
				$this->post_args['body'] = $this->zipkin_format();
				break;
			case 200:
				$this->post_args['body'] = $this->jaeger_format();
				break;
			case 300:
				$this->post_args['body'] = $this->datadog_format();
				break;
			case 400:
				$this->post_args['body'] = $this->decalog_format();
				break;
		}
		if ( '' !== $this->post_args['body'] ) {
			$this->send();
		}
	}

	/**
	 * Post the record to the service.
	 *
	 * @since    3.0.0
	 */
	protected function send(): void {
		if ( 'POST' === $this->verb ) {
			$result = wp_remote_post( esc_url_raw( $this->endpoint ), $this->post_args );
		}
		if ( 'GET' === $this->verb ) {
			$result = wp_remote_get( esc_url_raw( $this->endpoint ), $this->post_args );
		}
		if ( 'PUT' === $this->verb ) {
			$result = decalog_remote_put( esc_url_raw( $this->endpoint ), $this->post_args );
		}
		if ( 'STORAGE' === $this->verb ) {
			if ( isset( $this->storage ) ) {
				$this->storage->insert_value( $this->post_args['body'] );
			}
		} else {
			$code    = wp_remote_retrieve_response_code( $result );
			$message = wp_remote_retrieve_response_message( $result );
			if ( '' === $message ) {
				$message = 'Unknow error';
			}
			if ( $this->error_control ) {
				$message = 'Pushing traces to ' . $this->endpoint . ' => ' . $message;
				$logger  = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
				if ( in_array( (int) $code, Http::$http_effective_pass_codes, true ) ) {
					$logger->debug( $message, $code );
				} elseif ( in_array( (int) $code, Http::$http_success_codes, true ) ) {
					$logger->info( $message, $code );
				} elseif ( '' === $code ) {
					$logger->error( $message, 999 );
				} else {
					$logger->warning( $message, $code );
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function write( array $record ): void {
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle( array $record ): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush(): void {
	}

	/**
	 * {@inheritdoc}
	 */
	public function __destruct() {
		// suppress the parent behavior since we already have register_shutdown_function()
		// to call close(), and the reference contained there will prevent this from being
		// GC'd until the end of the request
	}

}
