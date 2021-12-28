<?php
/**
 * Memory abstract storage engine for DecaLog.
 *
 * Handles all memory abstract storage features.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */

namespace Decalog\Storage;

use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\Log;
use Decalog\System\Cache;
use Decalog\System\UUID;

/**
 * Define the DecaLog memory abstract storage mechanisms.
 *
 * Handles all features of memory abstract storage engine for DecaLog.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */
abstract class AbstractMemoryStorage extends AbstractStorage {

	/**
	 * The pool's name, specific to the calling plugin.
	 *
	 * @since  3.4.0
	 * @var    string    $pool_name    The pool's name.
	 */
	protected $pool_name = DECALOG_SLUG;

	/**
	 * Update bucket with current value.
	 *
	 * @return  boolean The availability of this storage.
	 * @since    3.4.0
	 */
	abstract protected function available();

	/**
	 * Get the value of a global cache item.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  3.4.0
	 */
	abstract protected function get_global( $item_name );

	/**
	 * Set the value of a global cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  int|string $ttl   Optional. The previously defined ttl @see Cache::init() if it's a string.
	 *                           The ttl value in seconds if it's and integer.
	 * @return bool False if value was not set and true if value was set.
	 * @since  3.4.0
	 */
	abstract protected function set_global( $item_name, $value, $ttl = 'default' );

	/**
	 * Delete the value of a global cache item.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  3.4.0
	 */
	abstract protected static function delete_global( $item_name );

	/**
	 * Update bucket with current value.
	 *
	 * @param   array $value  The values to update or insert in the bucket.
	 * @return  integer The inserted id if anny.
	 * @since    3.4.0
	 */
	public function insert_value( $value ) {
		if ( ! $this->available() ) {
			return 0;
		}
		$log = $this->get_global( $this->bucket_name );
		if ( ! isset( $log ) ) {
			$log = [];
		}
		if ( is_array( $log ) ) {
			$id          = UUID::generate_unique_id( 32 );
			$value['id'] = $id;
			$log[ $id ]  = $value;
			$this->set_global( $this->bucket_name, $log, 'infinite' );
			return 1;
		}
		return 0;
	}

	/**
	 * Initialize the logger.
	 *
	 * @since    3.4.0
	 */
	public function initialize() {
		if ( $this->available() && ! $this->get_global( $this->bucket_name ) ) {
			$this->force_purge();
		}
	}

	/**
	 * Update the logger.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since    3.4.0
	 */
	public function update( $from ) {

	}

	/**
	 * Finalize the logger.
	 *
	 * @since    3.4.0
	 */
	public function finalize() {
		$this->force_purge();
		if ( $this->available() ) {
			$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$log->debug( sprintf( 'Logger "%s" dropped.', $this->bucket_name ) );
			$this->delete_global( $this->bucket_name );
		}
	}

	/**
	 * Force table purge.
	 *
	 * @since    3.4.0
	 */
	public function force_purge() {
		if ( $this->available() ) {
			$this->set_global( $this->bucket_name, [], 'infinite' );
		}
	}

	/**
	 * Rotate and purge.
	 *
	 * @var    array    $logger    The logger definition.
	 * @return  integer     The number of deleted records.
	 * @since    3.4.0
	 */
	public function cron_clean( $logger ) {
		$count = 0;
		if ( $this->available() ) {
			$logs = $this->get_global( $this->bucket_name );
			if ( isset( $logs ) && is_array( $logs ) ) {
				if ( 0 < (int) $logger['configuration']['purge'] ) {
					$time = time() - ( MINUTE_IN_SECONDS * HOUR_IN_SECONDS * DAY_IN_SECONDS * (int) $logger['configuration']['purge'] );
					$l    = [];
					foreach ( $logs as $id => $log ) {
						if ( ! array_key_exists( 'timestamp', $log ) ) {
							$count++;
							continue;
						}
						$t = strtotime( $log['timestamp'] );
						if ( false === $t || $t < $time ) {
							$count++;
							continue;
						}
						$l[ $id ] = $log;
					}
					$logs = $l;
				}
				if ( 0 < (int) $logger['configuration']['rotate'] ) {
					$logs = array_slice( $logs, - (int) $logger['configuration']['rotate'] );
				}
				$this->set_global( $this->bucket_name, array_values( $logs ), 'infinite' );
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

	/**
	 * Count logged errors.
	 *
	 * @var     array $filters   Optional. The filter to apply.
	 * @return integer The count of the filtered logged errors.
	 * @since 3.4.0
	 */
	public function get_count( $filters = [] ) {
		return count( $this->get_list( $filters ) );
	}

	/**
	 * Get list of logged errors.
	 *
	 * @param array   $filters   Optional. The filter to apply.
	 * @param integer $offset The offset to record.
	 * @param integer $rowcount Optional. The number of rows to return.
	 * @return array An array containing the filtered logged errors.
	 * @since 3.4.0
	 */
	public function get_list( $filters, $offset = null, $rowcount = null ) {
		$result = [];
		if ( $this->available() ) {
			$logs = $this->get_global( $this->bucket_name );
			if ( isset( $logs ) && is_array( $logs ) ) {
				$levels = [];
				if ( 0 < count( $filters ) ) {
					foreach ( $filters as $key => $filter ) {
						if ( 'level' === $key ) {
							foreach ( EventTypes::$levels as $str => $val ) {
								if ( EventTypes::$levels[ $filter ] <= $val ) {
									$levels[] = $str;
								}
							}
						}
					}
				}
				$l = [];
				foreach ( $logs as $id => $log ) {
					if ( 0 < count( $filters ) ) {
						foreach ( $filters as $key => $filter ) {
							if ( 'level' === $key && array_key_exists( 'level', $log ) ) {
								if ( ! in_array( $log['level'], $levels, true ) ) {
									continue 2;
								}
							} elseif ( array_key_exists( $key, $log ) ) {
								if ( $log[ $key ] !== $filter ) {
									continue 2;
								}
							}
						}
					}
					if ( ! array_key_exists( 'instance', $log ) ) {
						$log['instance'] = 'undefined';
					}
					$l[ $id ] = $log;
				}
				$logs = array_reverse( $l );
				if ( ! is_null( $offset ) && ! is_null( $rowcount ) ) {
					$logs = array_slice( $logs, $offset, $rowcount );
				}
				$result = $logs;
			}
		}
		return $result;
	}

	/**
	 * Get a single logged error.
	 *
	 * @param   string  $id     The id to record.
	 * @return array|null   An array containing the logged error.
	 * @since 3.4.0
	 */
	public function get_by_id( $id ) {
		$logs = $this->get_list( [] );
		if ( array_key_exists( $id, $logs ) ) {
			$result = $logs[ $id ];
			if ( ! array_key_exists( 'instance', $result ) ) {
				$result['instance'] = 'undefined';
			}
			return $result;
		}
		return null;
	}

}
