<?php
/**
 * DB traces storage engine for DecaLog.
 *
 * Handles all DB traces storage features.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Storage;

use Decalog\Plugin\Feature\ClassTypes;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\System\Database;
use Decalog\System\Http;
use Decalog\Plugin\Feature\Log;

/**
 * Define the DecaLog DB traces storage mechanisms.
 *
 * Handles all features of DB traces storage engine for DecaLog.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class DBTraceStorage extends AbstractDBStorage {

	/**
	 * Initialize the logger.
	 *
	 * @since    3.0.0
	 */
	public function initialize() {
		global $wpdb;
		if ( '' !== $this->bucket_name ) {
			$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
			$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . $this->bucket_name;
			$sql            .= ' (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,';
			$sql            .= " `trace_id` varchar(64),";
			$sql            .= " `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
			$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','unknown') NOT NULL DEFAULT 'unknown',";
			$sql            .= " `instance` varchar(66) NOT NULL DEFAULT 'undefined',";
			$sql            .= " `duration` mediumint UNSIGNED NOT NULL DEFAULT '0',";  // In ms.
			$sql            .= " `scount` smallint UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `site_id` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `site_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `user_id` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 pseudonymization.
			$sql            .= " `user_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `user_session` varchar(64),";
			$sql            .= ' `spans` text,';
			$sql            .= ' PRIMARY KEY (`id`)';
			$sql            .= ") $charset_collate;";
			// phpcs:ignore
			$wpdb->query( $sql );
			$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$log->debug( sprintf( 'Table "%s" updated or created.', $this->bucket_name ) );
		}
	}

	/**
	 * Update the logger.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since    3.0.0
	 */
	public function update( $from ) {
		global $wpdb;

		// Starting from 3.3.0, WordpressHandler allows to store instance name.
		// We have to make a copy of the table, delete the old one, then rename the newly created table to avoid
		// potential "#1118 - Row size too large" error that may appear if we just make a "ALTER TABLE ... MODIFY COLUMN ...".
		if ( version_compare( '3.3.0', $from, '>' ) ) {
			try {
				if ( '' !== $this->bucket_name ) {
					$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
					$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . $this->bucket_name . '_mig';
					$sql            .= ' (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,';
					$sql            .= " `trace_id` varchar(64),";
					$sql            .= " `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
					$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','unknown') NOT NULL DEFAULT 'unknown',";
					$sql            .= " `instance` varchar(66) NOT NULL DEFAULT 'undefined',";
					$sql            .= " `duration` mediumint UNSIGNED NOT NULL DEFAULT '0',";  // In ms.
					$sql            .= " `scount` smallint UNSIGNED NOT NULL DEFAULT '0',";
					$sql            .= " `site_id` int(11) UNSIGNED NOT NULL DEFAULT '0',";
					$sql            .= " `site_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
					$sql            .= " `user_id` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 pseudonymization.
					$sql            .= " `user_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
					$sql            .= " `user_session` varchar(64),";
					$sql            .= ' `spans` text,';
					$sql            .= ' PRIMARY KEY (`id`)';
					$sql            .= ") $charset_collate;";
					// phpcs:ignore
					$wpdb->query( $sql );

					$sql = 'INSERT INTO ' . $wpdb->base_prefix . $this->bucket_name . '_mig (`trace_id`, `timestamp`, `channel`, `duration`, `scount`, `site_id`, `site_name`, `user_id`, `user_name`, `user_session`, `spans`) SELECT `trace_id`, `timestamp`, `channel`, `duration`, `scount`, `site_id`, `site_name`, `user_id`, `user_name`, `user_session`, `spans` FROM ' . $wpdb->base_prefix . $this->bucket_name . ';';
					// phpcs:ignore
					if ( false === $wpdb->query( $sql ) ) {
						throw new \Exception();
					}
					$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . $this->bucket_name;
					// phpcs:ignore
					if ( false === $wpdb->query( $sql ) ) {
						throw new \Exception();
					}
					$sql = 'RENAME TABLE ' . $wpdb->base_prefix . $this->bucket_name . '_mig TO ' . $wpdb->base_prefix . $this->bucket_name . ';';
					// phpcs:ignore
					if ( false === $wpdb->query( $sql ) ) {
						throw new \Exception();
					}
					$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
					$log->info( 'WordPress traces log successfully migrated.' );

				} else {
					throw new \Exception();
				}
			} catch ( \Throwable $e ) {
				$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
				$log->alert( 'Unable to migrate a WordPress traces log. To continue you have to remove all loggers having "WordPress traces log" type, then recreate them.' );
			}
		}

	}

}
