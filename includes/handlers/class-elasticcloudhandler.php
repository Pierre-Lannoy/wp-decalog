<?php
/**
 * Elastic Cloud handler for Monolog
 *
 * Handles all features of Elastic Cloud handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Decalog\Formatter\ElasticCloudFormatter;
use DLMonolog\Formatter\FormatterInterface;
use DLMonolog\Handler\ElasticsearchHandler;
use DLMonolog\Logger;
use Elastic\Elasticsearch\Common\Exceptions\RuntimeException as ElasticsearchRuntimeException;

/**
 * Define the Monolog Elastic Cloud handler.
 *
 * Handles all features of Elastic Cloud handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ElasticCloudHandler extends ElasticsearchHandler {

	/**
	 * @param string     $cloudid   The cloudID.
	 * @param string     $user      The deployment user.
	 * @param string     $pass      The deployment password.
	 * @param string     $index     The index name.
	 * @param int|string $level     The minimum logging level at which this handler will be triggered.
	 * @param bool       $bubble    Whether the messages that are handled can bubble up the stack or not.
	 * @since   1.0.0
	 */
	public function __construct( string $cloudid, string $user, string $pass, string $index = '', $level = Logger::DEBUG, bool $bubble = true ) {
		if ( '' === $index ) {
			$index = 'decalog';
		}
		$index   = strtolower( str_replace( [ ' ' ], '-', sanitize_text_field( $index ) ) );
		$client  = \Elastic\Elasticsearch\ClientBuilder::create()->setElasticCloudId( $cloudid )->setBasicAuthentication( $user, $pass )->build();
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
	 * @since   1.0.0
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
					],
				];
				unset( $record['_index'] );

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
