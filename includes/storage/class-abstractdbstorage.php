<?php
/**
 * DB abstract storage engine for DecaLog.
 *
 * Handles all DB abstract storage features.
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
 * Define the DecaLog DB abstract storage mechanisms.
 *
 * Handles all features of DB abstract storage engine for DecaLog.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
abstract class AbstractDBStorage extends AbstractStorage {

	/**
	 * Initialize the logger.
	 *
	 * @since    3.0.0
	 */
	abstract public function initialize();

	/**
	 * Update the logger.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since    3.0.0
	 */
	abstract public function update( $from );

	/**
	 * Update bucket with current value.
	 *
	 * @param   array $value  The values to update or insert in the bucket.
	 * @return  integer The inserted id if anny.
	 * @since    3.0.0
	 */
	public function insert_value( $value ) {
		global $wpdb;
		// phpcs:ignore
		if ( $wpdb->insert( $wpdb->base_prefix . $this->bucket_name, $value, '%s' ) ) {
			return $wpdb->insert_id;
		}
		return 0;
	}

	/**
	 * Finalize the logger.
	 *
	 * @since    3.0.0
	 */
	public function finalize() {
		global $wpdb;
		if ( '' !== $this->bucket_name ) {
			$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$log->debug( sprintf( 'Table "%s" dropped.', $this->bucket_name ) );
			$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . $this->bucket_name;
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Force table purge.
	 *
	 * @since    3.0.0
	 */
	public function force_purge() {
		global $wpdb;
		if ( '' !== $this->bucket_name ) {
			$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$log->debug( sprintf( 'Table "%s" purged.', $this->bucket_name ) );
			$sql = 'TRUNCATE TABLE ' . $wpdb->base_prefix . $this->bucket_name;
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Rotate and purge.
	 *
	 * @var    array    $logger    The logger definition.
	 * @return  integer     The number of deleted records.
	 * @since    3.0.0
	 */
	public function cron_clean( $logger ) {
		if ( '' !== $this->bucket_name ) {
			global $wpdb;
			$count    = 0;
			$database = new Database();
			if ( 0 < (int) $logger['configuration']['purge'] ) {
				if ( $hour_done = $database->purge( $this->bucket_name, 'timestamp', 24 * (int) $logger['configuration']['purge'] ) ) {
					$count += $hour_done;
				}
			}
			if ( 0 < (int) $logger['configuration']['rotate'] ) {
				$limit = $database->count_lines( $this->bucket_name ) - (int) $logger['configuration']['rotate'];
				if ( $limit > 0 ) {
					if ( $max_done = $database->rotate( $this->bucket_name, 'id', $limit ) ) {
						$count += $max_done;
					}
				}
			}
			$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			if ( 0 === $count ) {
				$log->info( sprintf( 'No old records to delete for logger "%s".', $logger['name'] ) );
			} elseif ( 1 === $count ) {
				$log->info( sprintf( '1 old record deleted for logger "%s".', $logger['name'] ) );
			} else {
				$log->info( sprintf( '%1$s old records deleted for logger "%2$s".', $count, $logger['name'] ) );
			}
			return $count;
		}
		return 0;
	}

	/**
	 * Count logged errors.
	 *
	 * @var     array $filter   Optional. The filter to apply.
	 * @return integer The count of the filtered logged errors.
	 * @since 3.0.0
	 */
	public function get_count( $filter = [] ) {
		$result = 0;
		global $wpdb;
		$sql = 'SELECT COUNT(*) as CNT FROM ' . $wpdb->base_prefix . $this->bucket_name . ' ' . $this->get_where_clause( $filter );
		// phpcs:ignore
		$cnt = $wpdb->get_results( $sql, ARRAY_A );
		if ( count( $cnt ) > 0 ) {
			if ( array_key_exists( 'CNT', $cnt[0] ) ) {
				$result = $cnt[0]['CNT'];
			}
		}
		return $result;
	}

	/**
	 * Get list of logged errors.
	 *
	 * @param array   $filters   Optional. The filters to apply.
	 * @param integer $offset The offset to record.
	 * @param integer $rowcount Optional. The number of rows to return.
	 * @return array An array containing the filtered logged errors.
	 * @since 3.0.0
	 */
	public function get_list( $filters, $offset = null, $rowcount = null ) {
		$result = [];
		$limit  = '';
		if ( ! is_null( $offset ) && ! is_null( $rowcount ) ) {
			$limit = 'LIMIT ' . $offset . ',' . $rowcount;
		}
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->base_prefix . $this->bucket_name . ' ' . $this->get_where_clause( $filters ) . ' ORDER BY id DESC ' . $limit;
		// phpcs:ignore
		$query = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $query as $val ) {
			$result[] = (array) $val;
		}
		return $result;
	}

	/**
	 * Get "where" clause for log table.
	 *
	 * @var     array $filters   Optional. The filters to apply.
	 * @return string The "where" clause.
	 * @since 1.0.0
	 */
	private function get_where_clause( $filters ) {
		$result = '';
		$w      = [];
		foreach ( $filters as $key => $filter ) {
			if ( $filter ) {
				if ( 'level' === $key ) {
					$l = [];
					foreach ( EventTypes::$levels as $str => $val ) {
						if ( EventTypes::$levels[ $filter ] <= $val ) {
							$l[] = "'" . $str . "'";
						}
					}
					$w[] = $key . ' IN (' . implode( ',', $l ) . ')';
				} else {
					$w[] = $key . '="' . $filter . '"';
				}
			}
		}
		if ( count( $w ) > 0 ) {
			$result = 'WHERE (' . implode( ' AND ', $w ) . ')';
		}
		return $result;
	}

	/**
	 * Get a single logged error.
	 *
	 * @param   string  $id     The id to record.
	 * @return array|null   An array containing the logged error.
	 * @since 3.0.0
	 */
	public function get_by_id( $id ) {
		global $wpdb;
		$sql = $wpdb->prepare('SELECT * FROM ' . $wpdb->base_prefix . $this->bucket_name . ' WHERE  id=%s;', [$id] );
		// phpcs:ignore
		$logs = $wpdb->get_results( $sql, ARRAY_A );
		if ( 1 === count( $logs ) ) {
			return $logs[0];
		}
		return null;
	}
}
