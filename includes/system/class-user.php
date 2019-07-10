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

use WP_User;

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
	 * Get the current user id.
	 *
	 * @return null|integer The user id if detected, null otherwise.
	 * @since  3.0.8
	 */
	public static function get_current_user_id() {
		$user_id = null;
		global $current_user;
		if ( ! empty( $current_user ) ) {
			if ( $current_user instanceof WP_User ) {
				$user_id = $current_user->ID;
			}
			if ( is_object( $current_user ) && isset( $current_user->ID ) ) {
				$user_id = $current_user->ID;
			}
		}
		return $user_id;
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
