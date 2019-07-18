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

use Decalog\Log;

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
	 * The table name.
	 *
	 * @since  1.0.0
	 * @var    array    $table    The table name.
	 */
	private $table = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   array $logger    The logger definition.
	 * @since    1.0.0
	 */
	public function __construct( $logger = [] ) {
		if ( [] != $logger ) {
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
		global $wpdb;
		$this->logger = $logger;
		$this->table  = $wpdb->prefix . 'decalog_' . str_replace( '-', '', $logger['uuid'] );
	}

	/**
	 * Initialize the logger.
	 *
	 * @since    1.0.0
	 */
	public function initialize() {
		global $wpdb;
		if ( '' != $this->table ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = 'CREATE TABLE IF NOT EXISTS ' . $this->table;
			$sql            .= ' (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,';
			$sql            .= " `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
			$sql            .= " `level` enum('emergency','alert','critical','error','warning','notice','info','debug','unknown') NOT NULL DEFAULT 'unknown',";
			$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','unknown') NOT NULL DEFAULT 'unknown',";
			$sql            .= " `class` enum('plugin','theme','unknown') NOT NULL DEFAULT 'unknown',";
			$sql            .= " `component` varchar(26) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `version` varchar(13) NOT NULL DEFAULT 'N/A',";
			$sql            .= " `code` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `message` varchar(1000) NOT NULL DEFAULT '-',";
			$sql            .= " `site_id` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `site_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `user_id` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 pseudonymization.
			$sql            .= " `user_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `remote_ip` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 obfuscation.
			$sql            .= " `url` varchar(2083) NOT NULL DEFAULT '-',";
			$sql            .= " `http_method` enum('get','head','post','put','delete','connect','options','trace','patch','unknown') NOT NULL DEFAULT 'unknown',";
			$sql            .= " `server` varchar(250) NOT NULL DEFAULT 'unknown',";
			$sql            .= " `referrer` varchar(250) NOT NULL DEFAULT '-',";
			$sql            .= " `file` varchar(250) NOT NULL DEFAULT 'unknown',";
			$sql            .= " `line` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `classname` varchar(250) NOT NULL DEFAULT 'unknown',";
			$sql            .= " `function` varchar(250) NOT NULL DEFAULT 'unknown',";
			$sql            .= ' `stack` varchar(10000),';
			$sql            .= ' PRIMARY KEY (`id`)';
			$sql            .= ") $charset_collate;";
			$wpdb->query( $sql );
			$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$logger->debug( sprintf( 'Table "%s" created.', $this->table ) );
		}
	}

	/**
	 * Finalize the logger.
	 *
	 * @since    1.0.0
	 */
	public function finalize() {
		global $wpdb;
		if ( '' != $this->table ) {
			$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$logger->debug( sprintf( 'Table "%s" dropped.', $this->table ) );
			$sql = 'DROP TABLE IF EXISTS ' . $this->table;
			$wpdb->query( $sql );
		}
	}

}
