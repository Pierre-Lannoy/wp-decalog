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

use Decalog\Plugin\Feature\DTracer;
use Decalog\System\Environment;
use Decalog\System\Hash;
use Decalog\System\Http;
use Decalog\System\Option;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;
use Decalog\System\Cache;

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
				add_action( 'shutdown', [ $this, 'close' ], PHP_INT_MAX - 2, 0 );
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
		$remove = [];
		foreach ( $this->processors as $processor ) {
			switch ( $processor ) {
				case 'IntrospectionProcessor':
					$remove[] = 'file.';
					break;
				case 'WWWProcessor':
					$remove[] = 'http.';
					break;
				case 'WordpressProcessor':
					$remove[] = 'wp.';
					break;
			}
		}
		foreach ( $this->traces as $index => $span ) {
			if ( array_key_exists( 'tags', $span ) ) {
				if ( 0 === count( $remove ) || ! array_key_exists( 'parentID', $span ) ) {
					$new_tags = $span['tags'];
				} else {
					$new_tags = [];
					foreach ( $remove as $r ) {
						foreach ( $span['tags'] as $key => $value ) {
							if ( false === strpos( $key, $r ) ) {
								$new_tags[ $key ] = $value;
							}
						}
					}
				}
				foreach ( $new_tags as $key => $value ) {
					if ( ( $this->privacy['obfuscation'] && false !== strpos( $key, 'remoteip' ) ) ||
						 ( $this->privacy['pseudonymization'] && false !== strpos( $key, 'userid' ) ) ) {
						$new_tags[ $key ] = Hash::simple_hash( $value );
					}
				}
				$this->traces[ $index ]['tags'] = $new_tags;
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
		//TODO: handle error.
		error_log( DECALOG_TRACEID . ' => HTTP ' . wp_remote_retrieve_response_code( $result ) . ' / ' . wp_remote_retrieve_response_message( $result ) );
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
