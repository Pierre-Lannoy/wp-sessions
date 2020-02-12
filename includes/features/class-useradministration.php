<?php
/**
 * User admin handling.
 *
 * Handles all user admin operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Feature;

/**
 * Define the user admin functionality.
 *
 * Handles all user admin operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class UserAdministration {

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_action( 'show_user_profile', [ self::class, 'user_profile' ], 0 );
		add_action( 'edit_user_profile', [ self::class, 'user_profile' ], 0 );
	}

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function user_profile( $user_id ) {
		include POSE_ADMIN_DIR . 'partials/sessions-admin-user-profile.php';
	}


}