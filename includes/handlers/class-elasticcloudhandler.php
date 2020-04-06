<?php
/**
 * Fluentd handler for Monolog
 *
 * Handles all features of Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Decalog\Formatter\ElasticCloudFormatter;
use Monolog\Logger;
use Decalog\Plugin\Feature\DLogger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\SematextFormatter;
use Elasticsearch\Common\Exceptions\RuntimeException as ElasticsearchRuntimeException;
use Elasticsearch\Client;

/**
 * Define the Monolog Fluentd handler.
 *
 * Handles all features of Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ElasticCloudHandler extends ElasticsearchHandler {

	/**
	 * @param string     $cloudid   The logsene host (for location selection).
	 * @param string     $user      The logs app token.
	 * @param string     $pass      The logsene host (for location selection).
	 * @param string     $index     The logs app token.
	 * @param int|string $level     The minimum logging level at which this handler will be triggered.
	 * @param bool       $bubble    Whether the messages that are handled can bubble up the stack or not.
	 */
	public function __construct( string $cloudid, string $user, string $pass, string $index = '', $level = Logger::DEBUG, bool $bubble = true ) {
		if ( '' === $index ) {
			$index = 'decalog';
		}
		$index   = strtolower( str_replace( [ ' ' ], '-', sanitize_text_field( $index ) ) );
		$client  = \Elasticsearch\ClientBuilder::create()->setElasticCloudId( $cloudid )->setBasicAuthentication( $user, $pass )->build();
		$options = [
			'index' => $index,
			'type'  => 'wordpress_decalog',
		];
		parent::__construct( $client, $options, $level, $bubble );
	}

	/**
	 * Use Elasticsearch bulk API to send list of documents
	 *
	 * @param  array             $records
	 * @throws \RuntimeException
	 */
	protected function bulkSend( array $records ): void {
		try {
			$params = [
				'body' => [],
			];

			foreach ( $records as $record ) {
				$params['body'][] = [
					'index' => [
						'_index' => $record['_index'],
						'_type'  => $record['_type'],
					],
				];
				unset( $record['_index'], $record['_type'] );

				$params['body'][] = $record;
			}

			$responses = $this->client->bulk( $params );

			if ( $responses['errors'] === true ) {
				throw $this->createExceptionFromResponses( $responses );
			}
		} catch ( \Throwable $e ) {
			if ( 'Waiting did not resolve future' !== $e->getMessage() && ! $this->options['ignore_error'] ) {
				throw new \RuntimeException( 'Error sending messages to Elasticsearch', 0, $e );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new ElasticCloudFormatter( $this->options['index'], $this->options['type'] );
	}
}
