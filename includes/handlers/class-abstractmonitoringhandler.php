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

use Decalog\System\Http;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;

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
	 * Is the handler elected?.
	 *
	 * @since  3.0.0
	 * @var    boolean    $elected    Is the handler elected?.
	 */
	private $elected = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   integer $sampling   The sampling rate (0->1000).
	 * @since    3.0.0
	 */
	public function __construct( $sampling ) {
		parent::__construct( Logger::EMERGENCY, true );
		$this->post_args = [
			'headers'    => [
				'User-Agent'     => Http::user_agent(),
				'Decalog-No-Log' => 'outbound',
			],
			'user-agent' => Http::user_agent(),
		];
		// TODO: add_action( 'shutdown', [ $this, 'close' ], PHP_INT_MAX - 2, 0 );
		// TODO: sampling computation ($this->elected)
	}

	/**
	 * Post the record to the service.
	 *
	 * @since    3.0.0
	 */
	protected function send(): void {
		if ( 'POST' === $this->verb ) {
			//wp_remote_post( esc_url_raw( $this->endpoint ), $record );
		}
		if ( 'GET' === $this->verb ) {
			//wp_remote_get( esc_url_raw( $this->endpoint ), $record );
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
