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
class APCuStorage extends AbstractMemoryStorage {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string    $name    The named storage.
	 * @since    3.0.0
	 */
	public function __construct( $name ) {
		Cache::init();
		parent::__construct( $name );
		$this->bucket_name = str_replace( $this->pool_name . '_', 'storage_', $this->bucket_name );
	}

	/**
	 * Update bucket with current value.
	 *
	 * @return  boolean The availability of this storage.
	 * @since    3.4.0
	 */
	protected function available() {
		return Cache::$apcu_available;
	}

	/**
	 * Get the value of a global apcu cache item.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  3.4.0
	 */
	protected function get_global( $item_name ) {
		return Cache::get_global_apcu( $item_name );
	}

	/**
	 * Set the value of a global apcu cache item.
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
	protected function set_global( $item_name, $value, $ttl = 'default' ) {
		return Cache::set_global_apcu( $item_name, $value, $ttl );
	}

	/**
	 * Delete the value of a global apcu cache item.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  3.4.0
	 */
	protected static function delete_global( $item_name ) {
		return Cache::delete_global_apcu( $item_name );
	}

}
