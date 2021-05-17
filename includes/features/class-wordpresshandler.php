<?php
/**
 * WordPress handler utility
 *
 * Handles all features of WordPress handler.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Storage\AbstractStorage;
use Decalog\System\Database;
use Decalog\System\Http;
use Decalog\Storage\DBStorage;
use Decalog\Storage\APCuStorage;

/**
 * Define the WordPress handler functionality.
 *
 * Handles all features of WordPress handler.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class WordpressHandler {

	/**
	 * The logger definition.
	 *
	 * @since  1.0.0
	 * @var    array    $logger    The logger definition.
	 */
	private $logger = [];

	/**
	 * The bucket name.
	 *
	 * @since  1.0.0
	 * @var    string    $bucket_name    The bucket name.
	 */
	private $bucket_name = '';

	/**
	 * The storage engine.
	 *
	 * @since  3.0.0
	 * @var    \Decalog\Storage\AbstractStorage    $storage    The storage engine.
	 */
	private $storage = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   array $logger    The logger definition.
	 * @since    1.0.0
	 */
	public function __construct( $logger = [] ) {
		if ( [] !== $logger ) {
			$this->set_logger( $logger );
		}
	}

	/**
	 * Set the internal logger.
	 *
	 * @param   array $logger    The logger definition.
	 * @since    1.0.0
	 */
	public function set_logger( $logger ) {
		$this->logger      = $logger;
		$this->bucket_name = 'decalog_' . str_replace( '-', '', $logger['uuid'] );
		switch ( $logger['configuration']['constant-storage'] ) {
			case 'apcu':
				$this->storage = new APCuStorage( $this->bucket_name );
				break;
			default:
				global $wpdb;
				$this->storage = new DBStorage( $wpdb->prefix . $this->bucket_name );
		}
	}

	/**
	 * Initialize the logger.
	 *
	 * @since    1.0.0
	 */
	public function initialize() {
		$this->storage->initialize();
	}

	/**
	 * Update the logger.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since    1.0.0
	 */
	public function update( $from ) {
		$this->storage->update( $from );
	}

	/**
	 * Finalize the logger.
	 *
	 * @since    1.0.0
	 */
	public function finalize() {
		$this->storage->finalize();
	}

	/**
	 * Force bucket purge.
	 *
	 * @since    2.0.0
	 */
	public function force_purge() {
		$this->storage->force_purge();
	}

	/**
	 * Rotate and purge.
	 *
	 * @return  integer     The number of deleted records.
	 * @since    1.0.0
	 */
	public function cron_clean() {
		$this->storage->cron_clean( $this->logger );
	}

}
