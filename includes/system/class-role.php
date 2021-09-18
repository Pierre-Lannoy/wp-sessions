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

namespace POSessions\System;

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
	const SUPER_ADMIN = 4;

	/**
	 * The single site admin.
	 *
	 * @since  1.0.0
	 */
	const SINGLE_ADMIN = 2;

	/**
	 * The local admin (in network site).
	 *
	 * @since  1.0.0
	 */
	const LOCAL_ADMIN = 1;

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
		if ( ! $user_id ) {
			$user = wp_get_current_user();
		} else {
			$user = get_userdata( $user_id );
		}
		if ( ! $user || ! $user->exists() ) {
			return 0;
		}
		if ( is_multisite() ) {
			$super_admins = get_super_admins();
			if ( is_array( $super_admins ) && in_array( $user->user_login, $super_admins ) ) {
				return self::SUPER_ADMIN;
			} elseif ( in_array( 'administrator', $user->roles ) ) {
				return self::LOCAL_ADMIN;
			}
		} else {
			if ( in_array( 'administrator', $user->roles ) ) {
				return self::SINGLE_ADMIN;
			}
		}
		return 0;
	}

	/**
	 * Get a list of available roles.
	 *
	 * @return  array  The list of available roles.
	 * @since   1.0.0
	 */
	public static function get_all() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}
		$result = [];
		foreach ( $wp_roles->get_names() as $role => $name ) {
			$result[ $role ]              = [];
			$result[ $role ]['name']      = $name;
			$result[ $role ]['l10n_name'] = translate_user_role( $name );
		}
		return $result;
	}

	/**
	 * Get user main role.
	 *
	 * @param   integer $user_id   The user id.
	 * @return  string  The role id.
	 * @since    1.0.0
	 */
	public static function get_user_main( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		$role = '';
		foreach ( self::get_all() as $key => $detail ) {
			if ( in_array( $key, $user->roles, true ) ) {
				$role = $key;
				break;
			}
		}
		return $role;
	}

	/**
	 * Get all user roles.
	 *
	 * @param   integer $user_id   The user id.
	 * @return  array  The roles id.
	 * @since    1.0.0
	 */
	public static function get_user_all( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		$role = [];
		foreach ( self::get_all() as $key => $detail ) {
			if ( in_array( $key, $user->roles, true ) ) {
				$role[] = $key;
			}
		}
		return $role;
	}

}
