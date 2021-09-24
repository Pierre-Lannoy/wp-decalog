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

use Decalog\Formatter\ElasticCloudFormatter;
use DLMonolog\Logger;
use Decalog\Plugin\Feature\DLogger;
use DLMonolog\Handler\ElasticsearchHandler;
use DLMonolog\Handler\HandlerInterface;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\SematextFormatter;
use Elasticsearch\Common\Exceptions\RuntimeException as ElasticsearchRuntimeException;
use Elasticsearch\Client;

/**
 * Define the Monolog Elastic Cloud handler.
 *
 * Handles all features of Elastic Cloud handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class ElasticHandler extends ElasticsearchHandler {

	/**
	 * @param string     $url       The service url.
	 * @param string     $user      The deployment user.
	 * @param string     $pass      The deployment password.
	 * @param string     $index     The index name.
	 * @param int|string $level     The minimum logging level at which this handler will be triggered.
	 * @param bool       $bubble    Whether the messages that are handled can bubble up the stack or not.
	 *
	 * @since   2.4.0
	 */
	public function __construct( string $url, string $user, string $pass, string $index = '', $level = Logger::DEBUG, bool $bubble = true ) {
		if ( '' === $index ) {
			$index = 'decalog';
		}
		$index   = strtolower( str_replace( [ ' ' ], '-', sanitize_text_field( $index ) ) );
		$client  = \Elasticsearch\ClientBuilder::create()->setHosts( [ $url ] )->setBasicAuthentication( $user, $pass )->build();
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
	 * @since   2.4.0
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
