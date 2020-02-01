<?php
/**
 * Session handling
 *
 * Handles all session operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\System;

use POSessions\System\Role;
use POSessions\System\Option;
use POSessions\System\Logger;
use POSessions\System\Hash;

/**
 * Define the session functionality.
 *
 * Handles all session operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Session {

	/**
	 * The current user ID.
	 *
	 * @since  1.0.0
	 * @var    integer    $user_id    The current user ID.
	 */
	private $user_id = 0;

	/**
	 * The current user.
	 *
	 * @since  1.0.0
	 * @var    \WP_User    $user    The current user.
	 */
	private $user = null;

	/**
	 * The current token.
	 *
	 * @since  1.0.0
	 * @var    string    $token    The current token.
	 */
	private $token = '';

	/**
	 * The class instance.
	 *
	 * @since  1.0.0
	 * @var    $object    $instance    The class instance.
	 */
	private static $instance = null;

	/**
	 * Create an instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->user_id = get_current_user_id();
		if ( $this->is_needed() ) {
			$this->user = get_user_by( 'id', $this->user_id );
			if ( ! $this->user ) {
				$this->user = null;
			}
		}
	}

	/**
	 * Verify if the instance is needed.
	 *
	 * @return boolean  True if the features are needed, false otherwise.
	 * @since 1.0.0
	 */
	public function is_needed() {
		return is_int( $this->user_id ) && 0 < $this->user_id;
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param boolean $full Optional. Indicates if it's a full init (from the 'init' hook).
	 * @since 1.0.0
	 */
	public function initialize( $full = true ) {
		$this->token = Hash::simple_hash( self::get_cookie_element( 'logged_in', 'token' ), false );
		$this->set_idle();
	}

	/**
	 * Modifies cookies durations.
	 *
	 * @param int  $expiration  Duration of the expiration period in seconds.
	 * @param int  $user_id     User ID.
	 * @param bool $remember    Whether to remember the user login. Default false.
	 * @return int New duration of the expiration period in seconds.
	 * @since 1.0.0
	 */
	public function cookie_expiration( $expiration, $user_id, $remember ) {
		if ( ! isset( $this->user ) || $user_id !== $this->user_id ) {
			return $expiration;
		}
		$sessions = self::get_user_sessions( $this->user_id );
		if ( ! array_key_exists( $this->token, $sessions ) ) {
			return $expiration;
		}
		$role = '';
		foreach ( Role::get_all() as $key => $detail ) {
			if ( in_array( $key, $this->user->roles, true ) ) {
				$role = $key;
				break;
			}
		}
		$settings = Option::roles_get();
		if ( ! array_key_exists( $role, $settings ) ) {
			return $expiration;
		}
		return $settings[ $role ][ $remember ? 'cookie_rttl' : 'cookie_ttl' ] * HOUR_IN_SECONDS;
	}

	/**
	 * Set the idle field if needed.
	 *
	 * @return boolean  True if the features are needed, false otherwise.
	 * @since 1.0.0
	 */
	private function set_idle() {
		if ( ! $this->is_needed() || ! isset( $this->user ) ) {
			return false;
		}
		$sessions = self::get_user_sessions( $this->user_id );
		if ( ! array_key_exists( $this->token, $sessions ) ) {
			return false;
		}
		$role = '';
		foreach ( Role::get_all() as $key => $detail ) {
			if ( in_array( $key, $this->user->roles, true ) ) {
				$role = $key;
				break;
			}
		}
		$settings = Option::roles_get();
		if ( ! array_key_exists( $role, $settings ) ) {
			return false;
		}
		if ( 0 === $settings[ $role ]['idle'] ) {
			return false;
		}
		$sessions[ $this->token ]['session_idle'] = time() + $settings[ $role ]['idle'] * HOUR_IN_SECONDS;
		self::set_user_sessions( $sessions, $this->user_id );
		return true;
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static();
		}
		if ( self::$instance->is_needed() ) {
			add_action( 'init', [ self::$instance, 'initialize' ], PHP_INT_MAX );
			add_filter( 'auth_cookie_expiration', [ self::$instance, 'cookie_expiration' ], PHP_INT_MAX, 3 );
		}
	}

	/**
	 * Get an element in a cookie.
	 *
	 * @param string $scheme  The cookie scheme to use: 'auth', 'secure_auth', or 'logged_in'.
	 * @param string $element The element to retrieve.
	 * @return  string  The element.
	 * @since   1.0.0
	 */
	public static function get_cookie_element( $scheme, $element ) {
		$cookie_elements = wp_parse_auth_cookie( '', $scheme );
		if ( ! $cookie_elements ) {
			return '';
		}
		if ( array_key_exists( $element, $cookie_elements ) ) {
			return (string) $cookie_elements[ $element ];
		}
		return '';
	}

	/**
	 * Get sessions.
	 *
	 * @param   mixed $user_id  Optional. The user ID.
	 * @return  array  The list of sessions.
	 * @since   1.0.0
	 */
	public static function get_user_sessions( $user_id = false ) {
		$result = [];
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id || ! is_int( $user_id ) ) {
			return $result;
		}
		$result = get_user_meta( $user_id, 'session_tokens', true );
		if ( ! is_array( $result ) && is_string( $result ) ) {
			$result = maybe_unserialize( $result );
		}
		return $result;
	}

	/**
	 * Set sessions.
	 *
	 * @param   array   $sessions The sessions records.
	 * @param   mixed   $user_id  Optional. The user ID.
	 * @return  boolean   True if the operation was successful, false otherwise.
	 * @since   1.0.0
	 */
	public static function set_user_sessions( $sessions, $user_id = false ) {
		$result = false;
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id || ! is_int( $user_id ) ) {
			return $result;
		}
		return (bool) update_user_meta( $user_id, 'session_tokens', $sessions );
	}

	/**
	 * Terminate sessions needing to be terminated.
	 *
	 * @param   array   $sessions The sessions records.
	 * @param   integer   $user_id  The user ID.
	 * @return  integer   Number of terminated sessions.
	 * @since   1.0.0
	 */
	public static function auto_terminate_session( $sessions, $user_id ) {
		$idle = [];
		$exp  = [];
		foreach ( $sessions as $token => $session ) {
			if ( time() > $session['idle'] ) {
				$idle[] = $token;
			} elseif ( time() > $session['expiration'] ) {
				$exp[] = $token;
			}
		}
		foreach ( $idle as $token ) {
			unset( $sessions[ $token ] );
			do_action( 'sessions_after_idle_terminate', $user_id );
		}
		foreach ( $exp as $token ) {
			unset( $sessions[ $token ] );
			do_action( 'sessions_after_expired_terminate', $user_id );
		}
		self::set_user_sessions( $sessions, $user_id );
		return count( $idle ) + count( $exp );
	}

}
