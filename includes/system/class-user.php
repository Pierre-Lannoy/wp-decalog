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

use Decalog\System\Hash;

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
		if ( $id && is_numeric( $id ) && $id > 0 ) {
			$user_info = get_userdata( $id );
			return $user_info->display_name;

		} else {
			return $default;
		}
	}

	/**
	 * Get a user string representation.
	 *
	 * @param   integer $id         Optional. The user id.
	 * @param   boolean $pseudonymize   Optional. Has this user to be pseudonymized.
	 * @return  string  The user string representation, ready to be inserted in a log.
	 * @since   1.0.0
	 */
	public static function get_user_string( $id = null, $pseudonymize = false ) {
		if ( $id && is_numeric( $id ) && $id > 0 && ! $pseudonymize ) {
			$user_info = get_userdata( $id );
			$name      = $user_info->display_name;
		} else {
			if ( $pseudonymize ) {
				$name = 'pseudonymized user';
				$id   = Hash::simple_hash( (string) $id );
			} else {
				return 'anonymous user';
			}
		}
		return sprintf( '%s (user ID %s)', $name, $id );
	}

	/**
	 * Get the current user id.
	 *
	 * @param   mixed   $default    Optional. Default value to return if user is not detected.
	 * @param   bool    $force      Optional. Try to force authent if user is not detected at first try.
	 * @return  mixed|integer The user id if detected, null otherwise.
	 * @since   1.0.0
	 */
	public static function get_current_user_id( $default = null, $force = false ) {
		if ( $force ) {
			$user_id = ( isset( $default ) ? (int) $default : 0 );
			$id      = get_current_user_id();
			if ( $id && is_numeric( $id ) && $id > 0 ) {
				$user_id = $id;
			}
			return $user_id;
		}
		global $current_user;
		if ( ! empty( $current_user ) ) {
			if ( $current_user instanceof \WP_User ) {
				return ( isset( $current_user->ID ) ? (int) $current_user->ID : ( isset( $default ) ? (int) $default : 0 ) );
			}
			if ( is_object( $current_user ) && isset( $current_user->ID ) ) {
				return $current_user->ID;
			}
		}
		return ( isset( $default ) ? (int) $default : 0 );
	}

	/**
	 * Get the current user nice name.
	 *
	 * @param   string  $default    Optional. Default value to return if user is not detected.
	 * @return  string  The current user nice name if detected, "anonymous" otherwise.
	 * @since   1.0.0
	 */
	public static function get_current_user_name( $default = 'anonymous' ) {
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
		$table_name = $wpdb->base_prefix . 'usermeta';
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
		$table_name = $wpdb->base_prefix . 'usermeta';
		$sql        = 'DELETE FROM ' . $table_name . ' WHERE meta_key LIKE "%\_decalog-%";';
		// phpcs:ignore
		return $wpdb->query( $sql );
	}

}
