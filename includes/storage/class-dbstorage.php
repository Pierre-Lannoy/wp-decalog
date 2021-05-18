<?php
/**
 * DB events storage engine for DecaLog.
 *
 * Handles all DB events storage features.
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
 * Define the DecaLog DB events storage mechanisms.
 *
 * Handles all features of DB events storage engine for DecaLog.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class DBStorage extends AbstractDBStorage {

	/**
	 * Initialize the logger.
	 *
	 * @since    3.0.0
	 */
	public function initialize() {
		global $wpdb;
		$cl = [];
		foreach ( ClassTypes::$classes as $c ) {
			$cl[] = "'" . $c . "'";
		}
		$classes = implode( ',', $cl );
		$cl      = [];
		foreach ( Http::$verbs as $c ) {
			$cl[] = "'" . $c . "'";
		}
		$verbs = implode( ',', $cl );
		if ( '' !== $this->bucket_name ) {
			$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
			$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . $this->bucket_name;
			$sql            .= ' (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,';
			$sql            .= " `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
			$sql            .= " `level` enum('emergency','alert','critical','error','warning','notice','info','debug','unknown') NOT NULL DEFAULT 'unknown',";
			$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','unknown') NOT NULL DEFAULT 'unknown',";
			$sql            .= ' `class` enum(' . $classes . ") NOT NULL DEFAULT 'unknown',";
			$sql            .= " `component` varchar(26) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `version` varchar(13) NOT NULL DEFAULT 'N/A',";
			$sql            .= " `code` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `message` text,";
			$sql            .= " `site_id` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `site_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `user_id` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 pseudonymization.
			$sql            .= " `user_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
			$sql            .= " `user_session` varchar(64),";
			$sql            .= " `remote_ip` varchar(66) NOT NULL DEFAULT '127.0.0.1',";  // Needed by SHA-256 obfuscation.
			$sql            .= " `url` varchar(2083) NOT NULL DEFAULT '-',";
			$sql            .= ' `verb` enum(' . $verbs . ") NOT NULL DEFAULT 'unknown',";
			$sql            .= " `server` varchar(250) NOT NULL DEFAULT 'unknown',";
			$sql            .= " `referrer` varchar(250) NOT NULL DEFAULT '-',";
			$sql            .= " `user_agent` varchar(1024) NOT NULL DEFAULT '-',";
			$sql            .= " `file` varchar(250) NOT NULL DEFAULT 'unknown',";
			$sql            .= " `line` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			$sql            .= " `classname` varchar(100) NOT NULL DEFAULT 'unknown',";
			$sql            .= " `function` varchar(100) NOT NULL DEFAULT 'unknown',";
			$sql            .= ' `trace` text,';
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

		// Starting from 2.4.0, WordpressHandler allows 'library' as class and session token storing..
		// We have to make a copy of the table, delete the old one, then rename the newly created table to avoid
		// potential "#1118 - Row size too large" error that may appear if we just make a "ALTER TABLE ... MODIFY COLUMN ...".
		if ( version_compare( '2.4.0', $from, '>' ) ) {
			try {
				$cl = [];
				foreach ( ClassTypes::$classes as $c ) {
					$cl[] = "'" . $c . "'";
				}
				$classes = implode( ',', $cl );
				$cl      = [];
				foreach ( Http::$verbs as $c ) {
					$cl[] = "'" . $c . "'";
				}
				$verbs = implode( ',', $cl );
				if ( '' !== $this->bucket_name ) {
					$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
					$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . $this->bucket_name . '_mig';
					$sql            .= ' (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,';
					$sql            .= " `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
					$sql            .= " `level` enum('emergency','alert','critical','error','warning','notice','info','debug','unknown') NOT NULL DEFAULT 'unknown',";
					$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','unknown') NOT NULL DEFAULT 'unknown',";
					$sql            .= ' `class` enum(' . $classes . ") NOT NULL DEFAULT 'unknown',";
					$sql            .= " `component` varchar(26) NOT NULL DEFAULT 'Unknown',";
					$sql            .= " `version` varchar(13) NOT NULL DEFAULT 'N/A',";
					$sql            .= " `code` int(11) UNSIGNED NOT NULL DEFAULT '0',";
					$sql            .= " `message` text,";
					$sql            .= " `site_id` int(11) UNSIGNED NOT NULL DEFAULT '0',";
					$sql            .= " `site_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
					$sql            .= " `user_id` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 pseudonymization.
					$sql            .= " `user_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
					$sql            .= " `user_session` varchar(64),";
					$sql            .= " `remote_ip` varchar(66) NOT NULL DEFAULT '127.0.0.1',";  // Needed by SHA-256 obfuscation.
					$sql            .= " `url` varchar(2083) NOT NULL DEFAULT '-',";
					$sql            .= ' `verb` enum(' . $verbs . ") NOT NULL DEFAULT 'unknown',";
					$sql            .= " `server` varchar(250) NOT NULL DEFAULT 'unknown',";
					$sql            .= " `referrer` varchar(250) NOT NULL DEFAULT '-',";
					$sql            .= " `user_agent` varchar(1024) NOT NULL DEFAULT '-',";
					$sql            .= " `file` varchar(250) NOT NULL DEFAULT 'unknown',";
					$sql            .= " `line` int(11) UNSIGNED NOT NULL DEFAULT '0',";
					$sql            .= " `classname` varchar(100) NOT NULL DEFAULT 'unknown',";
					$sql            .= " `function` varchar(100) NOT NULL DEFAULT 'unknown',";
					$sql            .= ' `trace` text,';
					$sql            .= ' PRIMARY KEY (`id`)';
					$sql            .= ") $charset_collate;";
					// phpcs:ignore
					$wpdb->query( $sql );
					$sql = 'INSERT INTO ' . $wpdb->base_prefix . $this->bucket_name . '_mig (`timestamp`, `level`, `channel`, `class`, `component`, `version`, `code`, `message`, `site_id`, `site_name`, `user_id`, `user_name`, `user_session`, `remote_ip`, `url`, `verb`, `server`, `referrer`, `user_agent`, `file`, `line`, `classname`, `function`, `trace`) SELECT `timestamp`, `level`, `channel`, `class`, `component`, `version`, `code`, `message`, `site_id`, `site_name`, `user_id`, `user_name`, null AS user_session, `remote_ip`, `url`, `verb`, `server`, `referrer`, `user_agent`, `file`, `line`, `classname`, `function`, `trace` FROM ' . $wpdb->base_prefix . $this->bucket_name . ';';
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
					$log->info( 'WordPress events log successfully migrated.' );
				} else {
					throw new \Exception();
				}
			} catch ( \Throwable $e ) {
				$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
				$log->alert( 'Unable to migrate a WordPress events log. To continue you have to remove all loggers having "WordPress events log" type, then recreate them.' );
			}
		}
	}

}
