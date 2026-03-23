<?php
/**
 * Elastic Cloud handler for Monolog
 *
 * Handles all features of Elastic Cloud handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Handler;

use DLMonolog\Formatter\FormatterInterface;
use DLMonolog\Handler\AbstractProcessingHandler;
use DLMonolog\Logger;
use OpenSearch\Client;

/**
 * Define the Monolog OpenSearch handler.
 *
 * Handles all features of OpenSearch handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   4.6.0
 */
class OpenSearchHandler extends AbstractProcessingHandler {

	/**
	 * @var Client|Client8
	 */
	protected $client;

	/**
	 * @var mixed[] Handler config options
	 */
	protected $options = [];

	/**
	 * Normalized extended fields.
	 *
	 * @since  4.6.0
	 * @var    array $extended The normalized extended fields, ready to be added to the event.
	 */
	private $extended = [];

	/**
	 * @param string $url The service url.
	 * @param string $user The deployment user.
	 * @param string $pass The deployment password.
	 * @param string $index Optional. The index name.
	 * @param string $extended Optional. Extended fields.
	 * @param int|string $level Optional. The minimum logging level at which this handler will be triggered.
	 * @param bool $bubble Optional. Whether the messages that are handled can bubble up the stack or not.
	 *
	 * @since   4.6.0
	 */
	public function __construct( string $url, string $user, string $pass, string $index = '', string $extended = '', $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		if ( '' === $index ) {
			$index = 'decalog';
		}
		$index          = strtolower( str_replace( [ ' ' ], '-', sanitize_text_field( $index ) ) );

		$this->client = (new \OpenSearch\GuzzleClientFactory())->create([
			'base_uri' => $url,
			'auth' => [$user, $pass],
			'verify' => false,
		]);



		//$this->client   = \OpenSearch\ClientBuilder::create()->setHosts( [ $url ] )->setBasicAuthentication( $user, $pass )->build();
		$this->options  = [
			'index'        => $index,
			'type'         => '_doc',
			'ignore_error' => false,
		];
		$this->extended = decalog_normalize_extended_fields( $extended );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function write( array $record ): void {
		$this->bulkSend( [ $record['formatted'] ] );
	}

	/**
	 * Use Elasticsearch bulk API to send list of documents
	 *
	 * @param array $records
	 *
	 * @throws \RuntimeException
	 * @since   4.6.0
	 */
	protected function bulkSend( array $records ): void {
		try {
			$params = [
				'index' => $this->options['index'],
				'body' => [],
			];

			foreach ( $records as $record ) {
				$params['body'][] = [
					'index' => [
						'_index' => $record['_index']
					],
				];
				unset( $record['_index'] );
				if ( ! empty( $this->extended ) ) {
					$record['extended'] = $this->extended;
				}

				$params['body'][] = $record;
			}

			$responses = $this->client->bulk( $params );
			if ( $responses['errors'] === true ) {
				throw new \Exception( print_r($responses, true), 0 );
			}
		} catch ( \Throwable $e ) {
			if ( 'Waiting did not resolve future' !== $e->getMessage() && ! $this->options['ignore_error'] ) {
				throw new \RuntimeException( 'Error sending messages to OpenSearch', 0, $e );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new \Decalog\Formatter\ElasticCloudFormatter( $this->options['index'], $this->options['type'] );
	}
}
