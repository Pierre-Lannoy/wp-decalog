<?php
/**
 * Users handling
 *
 * Handles all user operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the user functionality.
 *
 * Handles all user operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class User {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a user nice name.
	 *
	 * @param   integer $id         Optional. The user id.
	 * @param   string  $default    Optional. Default value to return if user is not detected.
	 * @return  string  The user nice name if detected, $default otherwise.
	 * @since   1.0.0
	 */
	public static function get_user_name( $id = null, $default = 'anonymous' ) {
		if ( $id && is_numeric($id) && $id >0) {
			$user_info = get_userdata( $id );
			return $user_info->display_name;

		} else {
			return $default;
		}
	}

	/**
	 * Get the current user id.
	 *
	 * @param   mixed   $default    Optional. Default value to return if user is not detected.
	 * @return  mixed|integer The user id if detected, null otherwise.
	 * @since   1.0.0
	 */
	public static function get_current_user_id($default = null) {
		$user_id = $default;
		$id = get_current_user_id();
		if ( $id && is_numeric($id) && $id > 0 ) {
			$user_id = $id;
		}
		return $user_id;
	}

	/**
	 * Get the current user nice name.
	 *
	 * @param   string  $default    Optional. Default value to return if user is not detected.
	 * @return  string  The current user nice name if detected, "anonymous" otherwise.
	 * @since   1.0.0
	 */
	public static function get_current_user_name($default = 'anonymous' ) {
		return self::get_user_name( self::get_current_user_id(), $default );
	}

	/**
	 * Delete some usermeta values.
	 *
	 * @param string  $key The end of meta_key field.
	 * @param integer $userid   Optional. The user id. If not specified,
	 *                          current user id is used.
	 * @return int|false The number of rows deleted, or false on error.
	 * @since 1.0.0
	 */
	public static function delete_meta( $key, $userid = null ) {
		if ( ! isset( $userid ) ) {
			$userid = self::get_current_user_id();
		}
		global $wpdb;
		$table_name = $wpdb->prefix . 'usermeta';
		$sql        = 'DELETE FROM ' . $table_name . ' WHERE meta_key LIKE "%\_' . $key . '%" AND user_id=' . $userid . ';';
		// phpcs:ignore
		return $wpdb->query( $sql );
	}

	/**
	 * Delete all usermeta values for all users.
	 *
	 * @return int|false The number of rows deleted, or false on error.
	 * @since 1.0.0
	 */
	public static function delete_all_meta() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'usermeta';
		$sql        = 'DELETE FROM ' . $table_name . ' WHERE meta_key LIKE "%\_decalog-%";';
		// phpcs:ignore
		return $wpdb->query( $sql );
	}

}
