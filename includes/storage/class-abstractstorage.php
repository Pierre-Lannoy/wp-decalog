<?php
/**
 * Storage engine for DecaLog.
 *
 * Handles all storage features.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Storage;

/**
 * Define the DecaLog storage mechanisms.
 *
 * Handles all features of storage engine for DecaLog.
 *
 * @package Storage
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
abstract class AbstractStorage {

	/**
	 * Bucket's name.
	 *
	 * @since  3.0.0
	 * @var    string    $bucket_name    The named storage.
	 */
	protected $bucket_name = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string    $name    The named storage.
	 * @since    3.0.0
	 */
	public function __construct( $name ) {
		$this->bucket_name = $name;
	}

	/**
	 * Update bucket with current value.
	 *
	 * @param   array $value  The values to update or insert in the bucket.
	 * @return  integer The inserted id if anny.
	 * @since    3.0.0
	 */
	abstract public function insert_value( $value );

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
	 * Finalize the logger.
	 *
	 * @since    3.0.0
	 */
	abstract public function finalize();

	/**
	 * Force table purge.
	 *
	 * @since    3.0.0
	 */
	abstract public function force_purge();

	/**
	 * Rotate and purge.
	 *
	 * @var    array    $logger    The logger definition.
	 * @return  integer     The number of deleted records.
	 * @since    3.0.0
	 */
	abstract public function cron_clean( $logger );

	/**
	 * Count logged errors.
	 *
	 * @var     array $filter   Optional. The filter to apply.
	 * @return integer The count of the filtered logged errors.
	 * @since 3.0.0
	 */
	abstract public function get_count( $filter = [] );

	/**
	 * Get list of logged errors.
	 *
	 * @param array   $filter   Optional. The filter to apply.
	 * @param integer $offset The offset to record.
	 * @param integer $rowcount Optional. The number of rows to return.
	 * @return array An array containing the filtered logged errors.
	 * @since 3.0.0
	 */
	abstract public function get_list( $filter, $offset = null, $rowcount = null );

}
