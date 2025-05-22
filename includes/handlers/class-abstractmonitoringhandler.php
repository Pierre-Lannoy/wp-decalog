<?php
/**
 * Abstract metrics handler for Monolog
 *
 * Handles all features of abstract metrics handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

use Decalog\Listener\AbstractListener;
use Decalog\Plugin\Feature\Log;
use Decalog\System\Environment;
use Decalog\System\Http;
use DLMonolog\Logger;
use DLMonolog\Handler\AbstractProcessingHandler;
use DLMonolog\Handler\HandlerInterface;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;
use Decalog\System\Cache;

/**
 * Define the Monolog abstract metrics handler.
 *
 * Handles all features of abstract metrics handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
abstract class AbstractMonitoringHandler extends AbstractProcessingHandler {

	/**
	 * Logger UUID.
	 *
	 * @since  3.0.0
	 * @var    string  $uuid       The UUID of the logger.
	 */
	protected $uuid = null;

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
	 * Error control.
	 *
	 * @since  3.0.0
	 * @var    boolean    $error_control    Error control.
	 */
	protected $error_control = true;

	/**
	 * Filter to exclude metrics.
	 *
	 * @since  3.5.0
	 * @var    array    $filters    Maintains the filters list.
	 */
	protected $filters = [];

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
	 * @param   int     $profile    The profile of collected metrics (500, 550 or 600).
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $filters    The filter to exclude metrics.
	 * @since    3.0.0
	 */
	public function __construct( $uuid, $profile, $sampling, $filters = '' ) {
		$this->uuid = $uuid;
		foreach ( explode ( PHP_EOL, $filters ) as $filter ) {
			if ( $filter ) {
				$this->filters[] = '/' . $filter . '/i';
			}
		}
		if ( 500 === $profile ) {
			if ( 'production' === Environment::stage() ) {
				$profile = 600;
			} else {
				$profile = 550;
			}
		}
		parent::__construct( $profile, true );
		$this->post_args = [
			'headers'    => [
				'User-Agent'     => Http::user_agent(),
				'Decalog-No-Log' => 'outbound',
			],
			'user-agent' => Http::user_agent(),
		];
		if ( ! in_array( $this->uuid, self::$running, true ) ) {
			// phpcs:ignore
			if ( $sampling >= mt_rand( 1, 1000 ) && Environment::exec_mode_for_closing_metrics() ) {
				\Decalog\Plugin\Feature\DMonitor::$active = true;
				add_action( 'shutdown', [ $this, 'close' ], AbstractListener::$monitor_priority + 2, 0 );
			}
			self::$running[] = $this->uuid;
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
		$code    = wp_remote_retrieve_response_code( $result );
		$message = wp_remote_retrieve_response_message( $result );
		if ( '' === $message ) {
			$message = 'Unknown error';
		}
		if ( $this->error_control ) {
			$message = 'Pushing metrics to ' . $this->endpoint . ' => ' . $message;
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

	/**
	 * Filters and merge profiles metrics.
	 *
	 * @param   array   $production     The production metrics.
	 * @param   array   $development    The development metrics.
	 * @since    3.5.0
	 */
	protected function filter( $production, $development ): array {
		if ( 0 === count( $this->filters ) ) {
			return array_merge( $production, $development );
		}
		$metrics = [];
		foreach ( array_merge( $production, $development ) as $metric ) {
			$add = true;
			foreach ( $this->filters as $filter ) {
				if ( preg_match( $filter, $metric->getName() ) ) {
					$add = false;
					break;
				}
			}
			if ( $add ) {
				$metrics[] = $metric;
			}
		}
		return $metrics;
	}

	/**
	 * Cache the record for future use.
	 *
	 * @since    3.0.0
	 */
	protected function set_cache(): void {
		Cache::set(
			'metrics/' . $this->uuid,
			[
				'timestamp' => time(),
				'body'      => $this->post_args['body'],
				'headers'   => $this->post_args['headers'],
			],
			'metrics',
			true
		);
	}

	/**
	 * Get the value of the cached record.
	 *
	 * @return mixed Value of record.
	 * @since    3.0.0
	 */
	protected function get_cache(): void {
		Cache::get( 'metrics/' . $this->uuid, true );
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
