<?php
/**
 * Sessions event capture
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Feature;

use POSessions\System\Logger;
use POSessions\System\User;

/**
 * Define the captures functionality.
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Capture {

	/**
	 * The number of expired sessions.
	 *
	 * @since  1.0.0
	 * @var    integer    $expired    The number of expired sessions.
	 */
	private static $expired = 0;

	/**
	 * The number of idle sessions.
	 *
	 * @since  1.0.0
	 * @var    integer    $idle    The number of idle sessions.
	 */
	private static $idle = 0;

	/**
	 * The number of registrations.
	 *
	 * @since  1.0.0
	 * @var    integer    $registration    The number of registrations.
	 */
	private static $registration = 0;

	/**
	 * The number of deleted accounts.
	 *
	 * @since  1.0.0
	 * @var    integer    $delete    The number of deleted accounts.
	 */
	private static $delete = 0;

	/**
	 * The number of password resets.
	 *
	 * @since  1.0.0
	 * @var    integer    $reset    The number of password resets.
	 */
	private static $reset = 0;

	/**
	 * The number of logouts.
	 *
	 * @since  1.0.0
	 * @var    integer    $logout    The number of logouts.
	 */
	private static $logout = 0;

	/**
	 * The number of successful logins.
	 *
	 * @since  1.0.0
	 * @var    integer    $login_success    The number of successful logins.
	 */
	private static $login_success = 0;

	/**
	 * The number of failed logins.
	 *
	 * @since  1.0.0
	 * @var    integer    $login_fail    The number of failed logins.
	 */
	private static $login_fail = 0;

	/**
	 * The number of blocked logins.
	 *
	 * @since  1.0.0
	 * @var    integer    $login_block    The number of blocked logins.
	 */
	private static $login_block = 0;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_action( 'sessions_after_idle_terminate', [ self::class, 'sessions_after_idle_terminate' ], 10, 1 );
		add_action( 'sessions_after_expired_terminate', [ self::class, 'sessions_after_expired_terminate' ], 10, 1 );
		add_action( 'delete_user', [ self::class, 'delete_user' ], 10, 2 );
		add_action( 'user_register', [ self::class, 'user_register' ], 10, 1 );
		add_action( 'password_reset', [ self::class, 'password_reset' ], 10, 2 );
		add_action( 'wp_logout', [ self::class, 'wp_logout' ], 10, 0 );
		add_action( 'wp_login_failed', [ self::class, 'wp_login_failed' ], 10, 1 );
		add_action( 'wp_login', [ self::class, 'wp_login' ], 10, 2 );
		add_action( 'jpp_kill_login', [ self::class, 'jpp_kill_login' ], 10, 1 );
	}

	/**
	 * Post actions for idle session terminated.
	 *
	 * @param   integer   $user_id  The user ID.
	 * @since    1.0.0
	 */
	public static function sessions_after_idle_terminate( $user_id ) {
		self::$idle ++;
		Logger::info( sprintf( 'Idle session terminated for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * Post actions for expired session terminated.
	 *
	 * @param   integer   $user_id  The user ID.
	 * @since    1.0.0
	 */
	public static function sessions_after_expired_terminate( $user_id ) {
		self::$expired ++;
		Logger::info( sprintf( 'Expired session terminated for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * "delete_user" event.
	 *
	 * @since    1.0.0
	 */
	public static function delete_user( $user_id, $reassign ) {
		self::$delete ++;
		Logger::info( sprintf( 'Deleted account for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * "user_register" and "wpmu_new_user" events.
	 *
	 * @since    1.0.0
	 */
	public static function user_register( $user_id ) {
		self::$registration ++;
		Logger::info( sprintf( 'Created account for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * "password_reset" event.
	 *
	 * @since    1.0.0
	 */
	public static function password_reset( $user, $new_pass ) {
		self::$reset ++;
		Logger::info( sprintf( 'Password reset for %s.', User::get_user_string( $user instanceof \WP_User ? $user->ID : 0 ) ) );
	}

	/**
	 * "wp_logout" event.
	 *
	 * @since    1.0.0
	 */
	public static function wp_logout() {
		self::$logout ++;
		Logger::info( 'A user has logged out.' );
	}

	/**
	 * "wp_login_failed" event.
	 *
	 * @since    1.0.0
	 */
	public static function wp_login_failed( $username ) {
		self::$login_fail ++;
		Logger::info( sprintf( 'Login failed for "%s" username.', $username ) );
	}

	/**
	 * "wp_login" event.
	 *
	 * @since    1.0.0
	 */
	public static function wp_login( $user_login, $user ) {
		self::$login_success ++;
		Logger::info( sprintf( 'Login success for %s.', User::get_user_string( $user instanceof \WP_User ? $user->ID : 0 ) ) );
	}

	/**
	 * "jpp_kill_login" event.
	 *
	 * @since    1.6.0
	 */
	public static function jpp_kill_login( $ip ) {
		self::$login_block ++;
		Logger::info( sprintf( 'Login blocked for "%s".', $ip ) );
	}

}
