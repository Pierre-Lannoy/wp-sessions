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
use POSessions\System\Session;

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
	 * Echo the 'Sessions Management' section of the user profile.
	 *
	 * @param \WP_User  $user   The user.
	 * @since    1.0.0
	 */
	public static function user_profile( $user ) {
		$session = new Session( $user );
		if ( $session->is_needed() ) {
			include POSE_ADMIN_DIR . 'partials/sessions-admin-user-profile.php';
		}
	}

}