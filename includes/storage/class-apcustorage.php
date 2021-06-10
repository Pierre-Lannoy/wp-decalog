<?php
/**
 * DB storage engine for DecaLog.
 *
 * Handles all DB storage features.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Storage;

use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\Log;
use Decalog\System\Cache;
use Decalog\System\UUID;

/**
 * Define the DecaLog DB storage mechanisms.
 *
 * Handles all features of DB storage engine for DecaLog.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class APCuStorage extends AbstractStorage {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string    $name    The named storage.
	 * @since    3.0.0
	 */
	public function __construct( $name ) {
		Cache::init();
		parent::__construct( $name );
		$this->bucket_name = str_replace( 'decalog_', 'storage_', $this->bucket_name );
	}

	/**
	 * Update bucket with current value.
	 *
	 * @param   array $value  The values to update or insert in the bucket.
	 * @return  integer The inserted id if anny.
	 * @since    3.0.0
	 */
	public function insert_value( $value ) {
		if ( ! Cache::$apcu_available ) {
			return 0;
		}
		$log = Cache::get_global( $this->bucket_name );
		if ( ! isset( $log ) ) {
			$log = [];
		}
		if ( is_array( $log ) ) {
			$id          = UUID::generate_unique_id( 32 );
			$value['id'] = $id;
			$log[ $id ]  = $value;
			Cache::set_global( $this->bucket_name, $log, 'infinite' );
			return 1;
		}
		return 0;
	}

	/**
	 * Initialize the logger.
	 *
	 * @since    3.0.0
	 */
	public function initialize() {
		if ( Cache::$apcu_available && ! Cache::get_global( $this->bucket_name ) ) {
			$this->force_purge();
		}
	}

	/**
	 * Update the logger.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since    3.0.0
	 */
	public function update( $from ) {

	}

	/**
	 * Finalize the logger.
	 *
	 * @since    3.0.0
	 */
	public function finalize() {
		$this->force_purge();
		if ( Cache::$apcu_available ) {
			$log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
			$log->debug( sprintf( 'APCu key "%s" dropped.', $this->bucket_name ) );
			Cache::delete_global( $this->bucket_name );
		}
	}

	/**
	 * Force table purge.
	 *
	 * @since    3.0.0
	 */
	public function force_purge() {
		if ( Cache::$apcu_available ) {
			Cache::set_global( $this->bucket_name, [], 'infinite' );
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
		$count = 0;
		if ( Cache::$apcu_available ) {
			$logs = Cache::get_global( $this->bucket_name );
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
				Cache::set_global( $this->bucket_name, array_values( $logs ), 'infinite' );
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
	 * @since 3.0.0
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
	 * @since 3.0.0
	 */
	public function get_list( $filters, $offset = null, $rowcount = null ) {
		$result = [];
		if ( Cache::$apcu_available ) {
			$logs = Cache::get_global( $this->bucket_name );
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
	 * @since 3.0.0
	 */
	public function get_by_id( $id ) {
		$logs = $this->get_list( [] );
		if ( array_key_exists( $id, $logs ) ) {
			return $logs[ $id ];
		}
		return null;
	}

}
