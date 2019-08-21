<?php
/**
 * Roles handling
 *
 * Handles all roles operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the roles functionality.
 *
 * Handles all roles operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Role {

	/**
	 * The super (network) admin.
	 *
	 * @since  1.0.0
	 */
	public const SUPER_ADMIN = 4;

	/**
	 * The single site admin.
	 *
	 * @since  1.0.0
	 */
	public const SINGLE_ADMIN = 2;

	/**
	 * The local admin (in network site).
	 *
	 * @since  1.0.0
	 */
	public const LOCAL_ADMIN = 1;

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
	 * @param   integer $user_id         Optional. The user id.
	 * @return  integer  The type of admin.
	 * @since   1.0.0
	 */
	public static function admin_type( $user_id = false ) {
		if ( ! $user_id || $user_id == get_current_user_id() ) {
			$user = wp_get_current_user();
		} else {
			$user = get_userdata( $user_id );
		}
		if ( ! $user || ! $user->exists() ) {
			return 0;
		}
		if ( is_multisite() ) {
			$super_admins = get_super_admins();
			if ( is_array( $super_admins ) && in_array( $user->user_login, $super_admins, true ) ) {
				return self::SUPER_ADMIN;
			} elseif ( in_array( 'administrator', $user->roles, true ) ) {
				return self::LOCAL_ADMIN;
			}
		} else {
			if ( in_array( 'administrator', $user->roles, true ) ) {
				return self::SINGLE_ADMIN;
			}
		}
		return 0;
	}

}
