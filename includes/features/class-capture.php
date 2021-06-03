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


use POSessions\System\User;
use POSessions\Plugin\Feature\Schema;

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
	 * The number of forced sessions.
	 *
	 * @since  1.0.0
	 * @var    integer    $forced    The number of forced sessions.
	 */
	private static $forced = 0;

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
		add_action( 'auth_cookie_expired', [ self::class, 'auth_cookie_expired' ], 10, 1 );
		add_action( 'sessions_force_terminate', [ self::class, 'sessions_force_terminate' ], 10, 1 );
		add_action( 'sessions_force_admin_terminate', [ self::class, 'sessions_force_admin_terminate' ], 10, 1 );
		add_action( 'delete_user', [ self::class, 'delete_user' ], 10, 2 );
		add_action( 'user_register', [ self::class, 'user_register' ], 10, 1 );
		add_action( 'password_reset', [ self::class, 'password_reset' ], 10, 2 );
		add_action( 'wp_logout', [ self::class, 'wp_logout' ], 10, 0 );
		add_action( 'wp_login_failed', [ self::class, 'wp_login_failed' ], 10, 1 );
		add_action( 'wp_login', [ self::class, 'wp_login' ], 10, 2 );
		add_action( 'jpp_kill_login', [ self::class, 'jpp_kill_login' ], 10, 1 );
	}

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function late_init() {
		add_action( 'wordfence_security_event', [ self::class, 'wordfence_security_event' ], 10, 1 );
	}

	/**
	 * Get the statistics.
	 *
	 * @return array The current statistics.
	 * @since    1.0.0
	 */
	public static function get_stats() {
		$result = [];
		if ( 0 < self::$expired ) {
			$result['expired'] = self::$expired;
		}
		if ( 0 < self::$idle ) {
			$result['idle'] = self::$idle;
		}
		if ( 0 < self::$forced ) {
			$result['forced'] = self::$forced;
		}
		if ( 0 < self::$registration ) {
			$result['registration'] = self::$registration;
		}
		if ( 0 < self::$delete ) {
			$result['delete'] = self::$delete;
		}
		if ( 0 < self::$reset ) {
			$result['reset'] = self::$reset;
		}
		if ( 0 < self::$logout ) {
			$result['logout'] = self::$logout;
		}
		if ( 0 < self::$login_success ) {
			$result['login_success'] = self::$login_success;
		}
		if ( 0 < self::$login_fail ) {
			$result['login_fail'] = self::$login_fail;
		}
		if ( 0 < self::$login_block ) {
			$result['login_block'] = self::$login_block;
		}
		return $result;
	}

	/**
	 * Post actions for idle session terminated.
	 *
	 * @param   integer   $user_id  The user ID.
	 * @since    1.0.0
	 */
	public static function sessions_after_idle_terminate( $user_id ) {
		self::$idle ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Idle session terminated for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * Post actions for cookie expiration.
	 *
	 * @param   array   $cookie_elements  The cookies elements.
	 * @since    1.0.0
	 */
	public static function auth_cookie_expired( $cookie_elements ) {
		// Don't allow too much iterations in case Decalog WordPress processor try to use get_current_user_id() and then restarts auth_cookie_expired hook.
		try {
			$cpt = 0;
			// phpcs:ignore
			foreach ( debug_backtrace( 0, 256 ) as $t ) {
				if ( array_key_exists( 'function', $t ) && ( false !== strpos( $t['function'], 'auth_cookie_expired' ) ) ) {
					$cpt++;
				}
				if ( 1 < $cpt ++ ) {
					return;
				}
			}
		} catch ( \Throwable $t ) {
			return;
		}
		self::$forced ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( 'Session cookie is expired.' );
	}

	/**
	 * Post actions for force session terminated.
	 *
	 * @param   integer   $user_id  The user ID.
	 * @since    1.0.0
	 */
	public static function sessions_force_terminate( $user_id ) {
		self::$forced ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Old session terminated for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * Post actions for force session terminated.
	 *
	 * @param   integer   $count  The number of terminated sessions.
	 * @since    1.0.0
	 */
	public static function sessions_force_admin_terminate( $count ) {
		self::$forced = self::$forced + $count;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( sprintf( 'Batch termination for %d sessions.', $count ) );
	}

	/**
	 * Post actions for expired session terminated.
	 *
	 * @param   integer   $user_id  The user ID.
	 * @since    1.0.0
	 */
	public static function sessions_after_expired_terminate( $user_id ) {
		self::$expired ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Expired session terminated for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * "delete_user" event.
	 *
	 * @since    1.0.0
	 */
	public static function delete_user( $user_id, $reassign ) {
		self::$delete ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Deleted account for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * "user_register" and "wpmu_new_user" events.
	 *
	 * @since    1.0.0
	 */
	public static function user_register( $user_id ) {
		self::$registration ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Created account for %s.', User::get_user_string( $user_id ) ) );
	}

	/**
	 * "password_reset" event.
	 *
	 * @since    1.0.0
	 */
	public static function password_reset( $user, $new_pass ) {
		self::$reset ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Password reset for %s.', User::get_user_string( $user instanceof \WP_User ? $user->ID : 0 ) ) );
	}

	/**
	 * "wp_logout" event.
	 *
	 * @since    1.0.0
	 */
	public static function wp_logout() {
		self::$logout ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( 'A user has logged out.' );
	}

	/**
	 * "wp_login_failed" event.
	 *
	 * @since    1.0.0
	 */
	public static function wp_login_failed( $username ) {
		self::$login_fail ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Login failed for "%s" username.', $username ) );
	}

	/**
	 * "wp_login" event.
	 *
	 * @since    1.0.0
	 */
	public static function wp_login( $user_login, $user = null ) {
		self::$login_success ++;
		if ( ! $user ) {
			$user = get_user_by( 'login', $user_login );
		}
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Login success for %s.', User::get_user_string( $user instanceof \WP_User ? $user->ID : 0 ) ) );
	}

	/**
	 * "jpp_kill_login" event.
	 *
	 * @since    1.0.0
	 */
	public static function jpp_kill_login( $ip ) {
		self::$login_block ++;
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Login blocked for "%s".', $ip ) );
	}

	/**
	 * "wordfence_security_event" filter.
	 *
	 * @since    1.6.0
	 */
	public static function wordfence_security_event( $event, $details = null, $a = null ) {
		if ( 'loginLockout' === $event || 'breachLogin' === $event ) {
			self::$login_block ++;
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( 'Login blocked.' );
		}
	}

	/**
	 * "login_block" pseudo event.
	 *
	 * @param   integer   $user_id  The user ID.
	 * @param   boolean   $dec      Optional. Decrements login_failed.
	 * @since    1.0.0
	 */
	public static function login_block( $user_id, $dec = false ) {
		self::$login_block ++;
		if ( $dec ) {
			self::$login_fail --;
		}
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( 'Login blocked for %s.', User::get_user_string( $user_id ) ) );
	}

}
