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
use POSessions\System\User;
use POSessions\System\Environment;
use POSessions\System\GeoIP;
use POSessions\System\UserAgent;
use POSessions\Plugin\Feature\Schema;
use POSessions\Plugin\Feature\Capture;
use POSessions\Plugin\Feature\LimiterTypes;

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
	 * The user's sessions.
	 *
	 * @since  1.0.0
	 * @var    array    $sessions    The user's sessions.
	 */
	private $sessions = [];

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
		$this->user_id  = get_current_user_id();
		$this->sessions = self::get_user_sessions( $this->user_id );
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
		// TODO debug / test / message
		if ( ! isset( $this->user ) || $user_id !== $this->user_id ) {
			return $expiration;
		}
		if ( ! array_key_exists( $this->token, $this->sessions ) ) {
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
	 * Verify if the ip range is allowed.
	 *
	 * @param string  $block The ip block ode.
	 * @return string 'allow' or 'disallow'.
	 * @since 1.0.0
	 */
	private function verify_ip_range( $block ) {
		if ( ! in_array( $block, [ 'none', 'external', 'local' ], true ) ) {
			Logger::warning( 'IP range limitation set to "Allow For All".', 202 );
			return 'allow';
		}
		if ( 'none' === $block ) {
			return 'allow';
		}
		if ( 'external' === $block && Environment::is_current_ip_private() ) {
			return 'allow';
		}
		if ( 'local' === $block && Environment::is_current_ip_public() ) {
			return 'allow';
		}
		return 'disallow';
	}

	/**
	 * Verify if the maximum allowed is reached.
	 *
	 * @param integer  $limit The maximum allowed.
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_user_limit( $limit ) {
		if ( is_array( $this->sessions ) && $limit > count( $this->sessions ) ) {
			return 'allow';
		}
		if ( ! is_array( $this->sessions ) ) {
			return 'allow';
		}
		uasort(
			$this->sessions,
			function ( $a, $b ) {
				if ( $a['login'] === $b['login'] ) {
					return 0;
				} return ( $a['login'] < $b['login'] ) ? -1 : 1;
			}
		);
		if ( $limit < count( $this->sessions ) ) {
			$this->sessions = array_slice( $this->sessions, 1 );
			do_action( 'sessions_force_terminate', $this->user_id );
			self::set_user_sessions( $this->sessions, $this->user_id );
			return $this->verify_per_user_limit( $limit );
		}
		return array_key_first( $this->sessions );
	}

	/**
	 * Verify if the maximum allowed is reached.
	 *
	 * @param integer  $limit The maximum allowed.
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_ip_limit( $limit ) {
		if ( ! is_array( $this->sessions ) ) {
			return 'allow';
		}
		$ip      = Environment::current_ip();
		$compare = [];
		$buffer  = [];
		foreach ( $this->sessions as $token => $session ) {
			if ( $ip === $session['ip'] ) {
				$compare[ $token ] = $session;
			} else {
				$buffer[ $token ] = $session;
			}
		}
		if ( $limit > count( $compare ) ) {
			return 'allow';
		}
		uasort(
			$compare,
			function ( $a, $b ) {
				if ( $a['login'] === $b['login'] ) {
					return 0;
				} return ( $a['login'] < $b['login'] ) ? -1 : 1;
			}
		);
		if ( $limit < count( $compare ) ) {
			$compare = array_slice( $compare, 1 );
			do_action( 'sessions_force_terminate', $this->user_id );
			$this->sessions = array_merge( $compare, $buffer );
			self::set_user_sessions( $this->sessions, $this->user_id );
			return $this->verify_per_user_limit( $limit );
		}
		return array_key_first( $compare );
	}

	/**
	 * Verify if the maximum allowed is reached.
	 *
	 * @param integer  $limit The maximum allowed.
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_country_limit( $limit ) {
		if ( ! is_array( $this->sessions ) ) {
			return 'allow';
		}
		$ip      = Environment::current_ip();
		$geo     = new GeoIP();
		$country = $geo->get_iso3166_alpha2( $ip );
		$compare = [];
		$buffer  = [];
		foreach ( $this->sessions as $token => $session ) {
			if ( $country === $geo->get_iso3166_alpha2( $session['ip'] ) ) {
				$compare[ $token ] = $session;
			} else {
				$buffer[ $token ] = $session;
			}
		}
		if ( $limit > count( $compare ) ) {
			return 'allow';
		}
		uasort(
			$compare,
			function ( $a, $b ) {
				if ( $a['login'] === $b['login'] ) {
					return 0;
				} return ( $a['login'] < $b['login'] ) ? -1 : 1;
			}
		);
		if ( $limit < count( $compare ) ) {
			$compare = array_slice( $compare, 1 );
			do_action( 'sessions_force_terminate', $this->user_id );
			$this->sessions = array_merge( $compare, $buffer );
			self::set_user_sessions( $this->sessions, $this->user_id );
			return $this->verify_per_user_limit( $limit );
		}
		return array_key_first( $compare );
	}

	/**
	 * Verify if the maximum allowed is reached.
	 *
	 * @param string   $ua The user agent.
	 * @param string   $selector The selector ('device-class', 'device-type', 'device-client',...).
	 * @return string The requested ID.
	 * @since 1.0.0
	 */
	private function get_device_id( $ua, $selector ) {
		$device = UserAgent::get( $ua );
		switch ( $selector ) {
			case 'device-class':
				if ( $device->class_is_bot ) {
					return 'bot';
				}
				if ( $device->class_is_mobile ) {
					return 'mobile';
				}
				if ( $device->class_is_desktop ) {
					return 'desktop';
				}
				return 'other';
			case 'device-type':
				if ( $device->device_is_smartphone ) {
					return 'smartphone';
				}
				if ( $device->device_is_featurephone ) {
					return 'featurephone';
				}
				if ( $device->device_is_tablet ) {
					return 'tablet';
				}
				if ( $device->device_is_phablet ) {
					return 'phablet';
				}
				if ( $device->device_is_console ) {
					return 'console';
				}
				if ( $device->device_is_portable_media_player ) {
					return 'portable-media-player';
				}
				if ( $device->device_is_car_browser ) {
					return 'car-browser';
				}
				if ( $device->device_is_tv ) {
					return 'tv';
				}
				if ( $device->device_is_smart_display ) {
					return 'smart-display';
				}
				if ( $device->device_is_camera ) {
					return 'camera';
				}
				return 'other';
			case 'device-client':
				if ( $device->client_is_browser ) {
					return 'browser';
				}
				if ( $device->client_is_feed_reader ) {
					return 'feed-reader';
				}
				if ( $device->client_is_mobile_app ) {
					return 'mobile-app';
				}
				if ( $device->client_is_pim ) {
					return 'pim';
				}
				if ( $device->client_is_library ) {
					return 'library';
				}
				if ( $device->client_is_media_player ) {
					return 'media-payer';
				}
				return 'other';
			case 'device-browser':
				return $device->client_short_name;
			case 'device-os':
				return $device->os_short_name;
		}
		return '';
	}

	/**
	 * Verify if the maximum allowed is reached.
	 *
	 * @param string   $selector The selector ('device-class', 'device-type', 'device-client',...).
	 * @param integer  $limit    The maximum allowed.
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_device_limit( $selector, $limit ) {
		if ( ! is_array( $this->sessions ) ) {
			return 'allow';
		}
		$device  = $this->get_device_id( '', $selector );
		$compare = [];
		$buffer  = [];
		foreach ( $this->sessions as $token => $session ) {
			if ( $device === $this->get_device_id( $session['ua'], $selector ) ) {
				$compare[ $token ] = $session;
			} else {
				$buffer[ $token ] = $session;
			}
		}
		if ( $limit > count( $compare ) ) {
			return 'allow';
		}
		uasort(
			$compare,
			function ( $a, $b ) {
				if ( $a['login'] === $b['login'] ) {
					return 0;
				} return ( $a['login'] < $b['login'] ) ? -1 : 1;
			}
		);
		if ( $limit < count( $compare ) ) {
			$compare = array_slice( $compare, 1 );
			do_action( 'sessions_force_terminate', $this->user_id );
			$this->sessions = array_merge( $compare, $buffer );
			self::set_user_sessions( $this->sessions, $this->user_id );
			return $this->verify_per_user_limit( $limit );
		}
		return array_key_first( $compare );
	}

	/**
	 * Enforce sessions limitation if needed.
	 *
	 * @param string  $message  The error message.
	 * @param integer $error    The error code.
	 * @since 1.0.0
	 */
	private function die( $message, $error ) {
		Capture::login_block( $this->user_id );
		wp_die( $message, $error );
	}

	/**
	 * Enforce sessions limitation if needed.
	 *
	 * @param mixed   $user     WP_User if the user is authenticated, WP_Error or null otherwise.
	 * @param string  $username Username or email address.
	 * @param string  $password User password.
	 * @return mixed WP_User if the user is allowed, WP_Error or null otherwise.
	 * @since 1.0.0
	 */
	public function limit_logins( $user, $username, $password ) {
		if ( -1 === (int) Option::network_get( 'rolemode' ) ) {
			return $user;
		}
		if ( $user instanceof \WP_User ) {
			$this->user_id  = $user->ID;
			$this->user     = $user;
			$this->sessions = self::get_user_sessions( $this->user_id );
			$role           = '';
			foreach ( Role::get_all() as $key => $detail ) {
				if ( in_array( $key, $this->user->roles, true ) ) {
					$role = $key;
					break;
				}
			}
			$settings = Option::roles_get();
			if ( array_key_exists( $role, $settings ) ) {
				$method = $settings[ $role ]['method'];
				$mode   = '';
				$limit  = 0;
				if ( 'none' === $settings[ $role ]['limit'] ) {
					$mode  = 'none';
					$limit = PHP_INT_MAX;
				} else {
					foreach ( LimiterTypes::$selector_names as $key => $name ) {
						if ( 0 === strpos( $settings[ $role ]['limit'], $key ) ) {
							$mode  = $key;
							$limit = (int) substr( $settings[ $role ]['limit'], strlen( $key ) + 1 );
							break;
						}
					}
				}
				if ( '' === $mode || 0 === $limit ) {
					if ( 1 === (int) Option::network_get( 'rolemode' ) ) {
						Logger::alert( sprintf( 'No session policy found for %s.', User::get_user_string( $this->user_id ) ), 500 );
						$this->die( __( '<strong>ERROR</strong>: ', 'sessions' ) . apply_filters( 'internal_error_message', __( 'Something went wrong, it is not possible to continue.', 'sessions' ) ), 500 );
					} else {
						Logger::critical( sprintf( 'No session policy found for %s.', User::get_user_string( $this->user_id ) ), 202 );
					}
				} else {
					if ( ! LimiterTypes::is_selector_available( $mode ) ) {
						Logger::critical( sprintf( 'No matching session policy for %s.', User::get_user_string( $this->user_id ) ), 202 );
						Logger::warning( sprintf( 'Session policy for %1%s downgraded from "%2$s" to "No limit".', User::get_user_string( $this->user_id ), sprintf( '%d session(s) per user and per %s', User::get_user_string( $this->user_id ), str_replace( '-', ' ', $mode ) ) ), 202 );
						$mode = 'none';
					}
					$result = $this->verify_ip_range( $settings[ $role ]['block'] );
					if ( 'allow' === $result ) {
						switch ( $mode ) {
							case 'none':
								$result = 'allow';
								break;
							case 'user':
								$result = $this->verify_per_user_limit( $limit );
								break;
							case 'ip':
								$result = $this->verify_per_ip_limit( $limit );
								break;
							case 'country':
								$result = $this->verify_per_country_limit( $limit );
								break;
							case 'device-class':
							case 'device-type':
							case 'device-client':
							case 'device-browser':
							case 'device-os':
								$result = $this->verify_per_device_limit( $mode, $limit );
								break;
							default:
								if ( 1 === (int) Option::network_get( 'rolemode' ) ) {
									Logger::alert( 'Unknown session policy.', 501 );
									$this->die( __( '<strong>ERROR</strong>: ', 'sessions' ) . apply_filters( 'internal_error_message', __( 'Something went wrong, it is not possible to continue.', 'sessions' ) ), 501 );
								} else {
									Logger::critical( 'Unknown session policy.', 202 );
									$result = 'allow';
									Logger::debug( sprintf( 'New session allowed for %s.', User::get_user_string( $this->user_id ) ), 200 );
								}
						}
					} else {
						Logger::warning( sprintf( 'New session not allowed for %s. Reason: IP range.', User::get_user_string( $this->user_id ), ), 403 );
						$this->die( __( '<strong>FORBIDDEN</strong>: ', 'sessions' ) . apply_filters( 'sessions_bad_ip_message', __( 'You\'re not allowed to initiate a new session from your current IP address.', 'sessions' ) ), 403 );
					}
					if ( 'allow' !== $result ) {
						switch ( $method ) {
							case 'override':
								if ( '' !== $result ) {
									if ( array_key_exists( $result, $this->sessions) ) {
										unset( $this->sessions[ $result ] );
										do_action( 'sessions_force_terminate', $this->user_id );
										self::set_user_sessions( $this->sessions, $this->user_id );
										Logger::notice( sprintf( 'Session overridden for %s. Reason: %s.', User::get_user_string( $this->user_id ), str_replace( 'device-', ' ', $mode ) ) );
									}
								}
								break;
							case 'default':
								Logger::warning( sprintf( 'New session not allowed for %s. Reason: %s.', User::get_user_string( $this->user_id ), str_replace( 'device-', ' ', $mode ) ), 403 );
								Capture::login_block( $this->user_id, true );
								return new \WP_Error( '403', __( '<strong>ERROR</strong>: ', 'sessions' ) . apply_filters( 'sessions_blocked_message', __( 'You\'re not allowed to initiate a new session because your maximum number of active sessions has been reached.', 'sessions' ) ) );
							default:
								Logger::warning( sprintf( 'New session not allowed for %s. Reason: %s.', User::get_user_string( $this->user_id ), str_replace( 'device-', ' ', $mode ) ), 403 );
								$this->die( __( '<strong>FORBIDDEN</strong>: ', 'sessions' ) . apply_filters( 'sessions_blocked_message', __( 'You\'re not allowed to initiate a new session because your maximum number of active sessions has been reached.', 'sessions' ) ), 403 );
						}
					} else {
						Logger::debug( sprintf( 'New session allowed for %s.', User::get_user_string( $this->user_id ) ), 200 );
					}
				}
			}
		}
		return $user;
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
		if ( ! array_key_exists( $this->token, $this->sessions ) ) {
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
			unset( $this->sessions[ $this->token ]['session_idle'] );
			return false;
		}
		$this->sessions[ $this->token ]['session_idle'] = time() + (int) $settings[ $role ]['idle'] * HOUR_IN_SECONDS;
		self::set_user_sessions( $this->sessions, $this->user_id );
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
		add_filter( 'authenticate', [ self::$instance, 'limit_logins' ], PHP_INT_MAX, 3 );
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
			if ( array_key_exists( 'session_idle', $session ) && time() > $session['session_idle'] ) {
				$idle[] = $token;
			} elseif ( array_key_exists( 'expiration', $session ) && time() > $session['expiration'] ) {
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
