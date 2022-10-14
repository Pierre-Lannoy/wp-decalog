<?php
/**
 * WordPress tracing handler for Monolog
 *
 * Handles all features of WordPress tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Handler;

use Decalog\Storage\APCuStorage;
use Decalog\Storage\DBTraceStorage;

/**
 * Define the WordPress Datadog tracing handler.
 *
 * Handles all features of WordPress tracing handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class WordpressTracingHandler extends AbstractTracingHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $uuid       The UUID of the logger.
	 * @param   int     $sampling   The sampling rate (0->1000).
	 * @param   string  $table    The table name.
	 * @param   string  $storage  The storage type.
	 * @since    3.0.0
	 */
	public function __construct( string $uuid, int $sampling, string $table, string $storage ) {
		parent::__construct( $uuid, 400, $sampling, null );
		$this->verb = 'STORAGE';
		switch ( $storage ) {
			case 'apcu':
				$this->storage = new APCuStorage( $table );
				break;
			default:
				$this->storage = new DBTraceStorage( $table );
		}
	}

}
