<?php
/**
 * Bugsnag handler for Monolog
 *
 * Handles all features of Bugsnag handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Decalog\System\Environment;
use Decalog\System\UserAgent;
use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\GAnalyticsFormatter;
use Decalog\System\Http;

/**
 * Define the Monolog Bugsnag handler.
 *
 * Handles all features of Bugsnag handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class GAnalyticsHandler extends AbstractBufferedHTTPHandler {

	/**
	 * Standard args.
	 *
	 * @since  2.4.0
	 * @var    array    $std_args    The standard args for the post request.
	 */
	private $std_args = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $key         The Bugsnag API key.
	 * @param   boolean $buffered   Optional. Has the record to be buffered?.
	 * @param   integer $level      Optional. The min level to log.
	 * @param   boolean $bubble     Optional. Has the record to bubble?.
	 * @since    2.4.0
	 */
	public function __construct( string $key, bool $buffered = true, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $buffered, $bubble );
		$this->endpoint = 'https://www.google-analytics.com/collect'; // batch
		$this->verb     = 'GET';
		$this->std_args = [ 'v=1', 't=exception', 'tid=' . $key, 'ni=1' ];
	}

	/**
	 * Post events to the service.
	 *
	 * @param   array $events    The record to post.
	 * @since    2.4.0
	 */
	protected function write( array $events ): void {
		$this->post_args['body'] = '';
		foreach ( $events as $args ) {
			$records = [];
			foreach ( $args as $key => $arg ) {
				$records[] = $key . '=' . $arg;
			}
			$this->endpoint = 'https://www.google-analytics.com/collect?' . implode( '&', array_merge( $records, $this->std_args ) );
			parent::write( $this->post_args );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new GAnalyticsFormatter();
	}

	/**
	 * {@inheritdoc}
	 */
	public function handleBatch( array $records ): void {
		$messages = [];
		foreach ( $records as $record ) {
			if ( $record['level'] < $this->level ) {
				continue;
			}
			$messages[] = $record;
		}
		if ( ! empty( $messages ) ) {
			$messages = maybe_unserialize( $this->getFormatter()->formatBatch( $messages ) );
			$this->write( $messages );
		}
	}

}
