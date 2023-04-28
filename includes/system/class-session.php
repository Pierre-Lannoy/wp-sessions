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

use POSessions\System\Environment;
use POSessions\System\Role;
use POSessions\System\Option;

use POSessions\System\Hash;
use POSessions\System\User;
use POSessions\System\GeoIP;
use POSessions\System\UserAgent;
use POSessions\Plugin\Feature\Schema;
use POSessions\Plugin\Feature\Capture;
use POSessions\Plugin\Feature\LimiterTypes;
use POSessions\System\IP;

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
	 * @var    integer $user_id The current user ID.
	 */
	private $user_id = 0;

	/**
	 * The current user.
	 *
	 * @since  1.0.0
	 * @var    \WP_User $user The current user.
	 */
	private $user = null;

	/**
	 * The user's sessions.
	 *
	 * @since  1.0.0
	 * @var    array $sessions The user's sessions.
	 */
	private $sessions = [];

	/**
	 * The user's distinct sessions IP.
	 *
	 * @since  1.1.0
	 * @var    array $ip The user's distinct sessions IP.
	 */
	private $ip = [];

	/**
	 * The current token.
	 *
	 * @since  1.0.0
	 * @var    string $token The current token.
	 */
	private $token = '';

	/**
	 * The class instance.
	 *
	 * @since  1.0.0
	 * @var    $object $instance    The class instance.
	 */
	private static $instance = null;

	/**
	 * Create an instance.
	 *
	 * @param mixed $user Optional, the user or user ID.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $user = null ) {
		$this->load_user( $user );
	}

	/**
	 * Create an instance.
	 *
	 * @param mixed $user Optional, the user or user ID.
	 *
	 * @since 1.0.0
	 */
	private function load_user( $user = null ) {
		if ( ! isset( $user ) ) {
			$this->user_id = get_current_user_id();
		} else {
			if ( $user instanceof \WP_User ) {
				$this->user_id = $user->ID;
			} elseif ( is_int( $user ) ) {
				$this->user_id = $user;
			} else {
				$this->user_id = 0;
			}
		}
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
	 * Get the number of active sessions.
	 *
	 * @return integer  The number of sessions.
	 * @since 1.0.0
	 */
	public function get_sessions_count() {
		if ( isset( $this->sessions ) ) {
			return count( $this->sessions );
		}

		return 0;
	}

	/**
	 * Get the user id.
	 *
	 * @return integer  The user id.
	 * @since 1.0.0
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Modifies cookies durations.
	 *
	 * @param int $expiration Duration of the expiration period in seconds.
	 * @param int $user_id User ID.
	 * @param bool $remember Whether to remember the user login. Default false.
	 *
	 * @return int New duration of the expiration period in seconds.
	 * @since 1.0.0
	 */
	public function cookie_expiration( $expiration, $user_id = null, $remember = false ) {
		if ( ! isset( $this->user ) || ( isset( $user_id ) && $user_id !== $this->user_id ) ) {
			return $expiration;
		}

		return (int) $this->get_privileges_for_user()['modes'][ $remember ? 'rttl' : 'ttl' ] * HOUR_IN_SECONDS;
	}

	/**
	 * Verify if the ip range is allowed.
	 *
	 * @param string $block The ip block ode.
	 *
	 * @return string 'allow' or 'disallow'.
	 * @since 1.0.0
	 */
	private function verify_ip_range( $block ) {
		if ( ! in_array( $block, [ 'none', 'external', 'local', 'all' ], true ) ) {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->warning( 'IP range limitation set to "Allow For All".', [ 'code' => 202 ] );

			return 'allow';
		}
		if ( 'none' === $block ) {
			return 'allow';
		}
		if ( 'external' === $block && IP::is_current_private() ) {
			return 'allow';
		}
		if ( 'local' === $block && IP::is_current_public() ) {
			return 'allow';
		}

		return 'disallow';
	}

	/**
	 * Verify if the max number of ip.
	 *
	 * @param integer $maxip The ip max number.
	 *
	 * @return string 'allow' or 'disallow'.
	 * @since 1.1.0
	 */
	private function verify_ip_max( $maxip ) {
		if ( 0 === $maxip || in_array( IP::get_current(), $this->ip, true ) ) {
			return 'allow';
		}
		if ( $maxip > count( $this->ip ) ) {
			return 'allow';
		}

		return 'disallow';
	}

	/**
	 * Verify if the maximum allowed is reached.
	 *
	 * @param integer $limit The maximum allowed.
	 *
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_user_limit( $limit ) {
		if ( 0 === $limit ) {
			return 'allow';
		}
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
				}

				return ( $a['login'] < $b['login'] ) ? - 1 : 1;
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
	 * @param integer $limit The maximum allowed.
	 *
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_ip_limit( $limit ) {
		if ( 0 === $limit ) {
			return 'allow';
		}
		if ( ! is_array( $this->sessions ) ) {
			return 'allow';
		}
		$ip      = IP::get_current();
		$compare = [];
		$buffer  = [];
		foreach ( $this->sessions as $token => $session ) {
			if ( IP::expand( $session['ip'] ) === $ip ) {
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
				}

				return ( $a['login'] < $b['login'] ) ? - 1 : 1;
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
	 * @param integer $limit The maximum allowed.
	 *
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_country_limit( $limit ) {
		if ( 0 === $limit ) {
			return 'allow';
		}
		if ( ! is_array( $this->sessions ) ) {
			return 'allow';
		}
		$ip      = IP::get_current();
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
				}

				return ( $a['login'] < $b['login'] ) ? - 1 : 1;
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
	 * @param string $ua The user agent.
	 * @param string $selector The selector ('device-class', 'device-type', 'device-client',...).
	 *
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
	 * @param string $selector The selector ('device-class', 'device-type', 'device-client',...).
	 * @param integer $limit The maximum allowed.
	 *
	 * @return string 'allow' or the token of the overridable if maximum is reached.
	 * @since 1.0.0
	 */
	private function verify_per_device_limit( $selector, $limit ) {
		if ( 0 === $limit ) {
			return 'allow';
		}
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
				}

				return ( $a['login'] < $b['login'] ) ? - 1 : 1;
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
	 * @param string $message The error message.
	 * @param integer $error The error code.
	 *
	 * @since 1.0.0
	 */
	private function die( $message, $error ) {
		Capture::login_block( $this->user_id );
		wp_die( $message, $error );
	}

	/**
	 * Enforce redirection if needed.
	 *
	 * @param string $mode The reason code.
	 *
	 * @since 2.10.0
	 */
	private function redirect( $mode ) {
		Capture::login_block( $this->user_id );
		$url = (string) Option::network_get( 'fallback' );
		if ( '' === $url ) {
			$url = get_site_url();
		}
		wp_redirect( esc_url( add_query_arg( [ 'reason' => $mode ] , $url ) ), 303 );
		exit;
	}

	/**
	 * Enforce sessions limitation if needed.
	 *
	 * @param \WP_User|false|null $user Local User information.
	 * @param object $user_data WordPress.com User Login information.
	 *
	 * @since 1.0.0
	 */
	public function jetpack_sso_handle_login( $user, $user_data ) {
		$this->load_user( $user );
		$this->init_if_needed();
		$this->limit_logins( $user, '', '', true );
	}

	/**
	 * Computes privileges for a set of roles.
	 *
	 * @param array $roles The set of roles for which the privileges must be computed.
	 *
	 * @return array    The privileges.
	 * @since 2.0.0
	 */
	private function get_privileges_for_roles( $roles ) {
		$result         = [];
		$settings       = Option::roles_get();
		$methods        = [ 'block', 'default', 'override' ];
		$block_none     = false;
		$block_external = false;
		$block_local    = false;
		$limits         = [];
		if ( 0 === (int) Option::network_get( 'rolemode' ) ) { // Cumulative privileges.
			$idle   = - 1;
			$maxip  = - 1;
			$ttl    = 0;
			$rttl   = 0;
			$method = '';
			foreach ( $roles as $role ) {
				// Blocked IP ranges
				switch ( $settings[ $role ]['block'] ) {
					case 'none':
						$block_none = true;
						break;
					case 'external':
						$block_external = true;
						break;
					case 'local':
						$block_local = true;
						break;
				}
				// Limits
				if ( 'none' === $settings[ $role ]['limit'] ) {
					$limits['none'] = true;
				} else {
					foreach (
						[
							'user',
							'country',
							'ip',
							'device-class',
							'device-type',
							'device-client',
							'device-browser',
							'device-os'
						] as $type
					) {
						if ( 0 === strpos( $settings[ $role ]['limit'], $type . '-' ) ) {
							$value = (int) substr( $settings[ $role ]['limit'], strlen( $type ) + 1 );
							if ( array_key_exists( $type, $limits ) ) {
								if ( $limits[ $type ] < $value ) {
									$limits[ $type ] = $value;
								}
							} else {
								$limits[ $type ] = $value;
							}
						}
					}
				}
				// Method
				if ( '' === $method ) {
					$method = $settings[ $role ]['method'];
				} else {
					$current = array_search( $method, $methods, true );
					$new     = array_search( $settings[ $role ]['method'], $methods, true );
					if ( false !== $new && false !== $current && $new > $current ) {
						$method = $settings[ $role ]['method'];
					}
				}
				// Max idle time
				if ( 0 !== $idle ) {
					$tidle = $settings[ $role ]['idle'];
					if ( 100 < $tidle ) {
						$tidle = $tidle - 100;
					} else {
						$tidle = $tidle * 60;
					}
					if ( ! $settings[ $role ]['idle'] ) {
						$idle = 0;
					} elseif ( $tidle > $idle ) {
						$idle = $tidle;
					}
				}
				// Max number of IPs
				if ( 0 !== $maxip ) {
					if ( 0 === $settings[ $role ]['maxip'] ) {
						$maxip = 0;
					} elseif ( $settings[ $role ]['maxip'] > $maxip ) {
						$maxip = $settings[ $role ]['maxip'];
					}
				}
				// Cookie TTL
				if ( $settings[ $role ]['cookie-ttl'] > $ttl ) {
					$ttl = $settings[ $role ]['cookie-ttl'];
				}
				// Cookie R-TTL
				if ( $settings[ $role ]['cookie-rttl'] > $rttl ) {
					$rttl = $settings[ $role ]['cookie-rttl'];
				}
			}
		} else { // Least privileges.
			$idle   = PHP_INT_MAX;
			$maxip  = PHP_INT_MAX;
			$ttl    = PHP_INT_MAX;
			$rttl   = PHP_INT_MAX;
			$method = '';
			foreach ( $roles as $role ) {
				// Blocked IP ranges
				switch ( $settings[ $role ]['block'] ) {
					case 'none':
						$block_none = true;
						break;
					case 'external':
						$block_external = true;
						break;
					case 'local':
						$block_local = true;
						break;
				}
				// Limits
				if ( 'none' === $settings[ $role ]['limit'] ) {
					$limits['none'] = true;
				} else {
					foreach (
						[
							'user',
							'country',
							'ip',
							'device-class',
							'device-type',
							'device-client',
							'device-browser',
							'device-os'
						] as $type
					) {
						if ( 0 === strpos( $settings[ $role ]['limit'], $type . '-' ) ) {
							$value = (int) substr( $settings[ $role ]['limit'], strlen( $type ) + 1 );
							if ( array_key_exists( $type, $limits ) ) {
								if ( $limits[ $type ] > $value ) {
									$limits[ $type ] = $value;
								}
							} else {
								$limits[ $type ] = $value;
							}
						}
					}
				}
				// Method
				if ( '' === $method ) {
					$method = $settings[ $role ]['method'];
				} else {
					$current = array_search( $method, $methods, true );
					$new     = array_search( $settings[ $role ]['method'], $methods, true );
					if ( false !== $new && false !== $current && $new < $current ) {
						$method = $settings[ $role ]['method'];
					}
				}
				// Max idle time
				$tidle = $settings[ $role ]['idle'];
				if ( 100 < $tidle ) {
					$tidle = $tidle - 100;
				} else {
					$tidle = $tidle * 60;
				}
				if ( $tidle < $idle && 0 !== $tidle ) {
					$idle = $tidle;
				}
				// Max number of IPs
				if ( 0 === $settings[ $role ]['maxip'] ) {
					$settings[ $role ]['maxip'] = PHP_INT_MAX;
				}
				if ( $settings[ $role ]['maxip'] < $maxip ) {
					$maxip = $settings[ $role ]['maxip'];
				}
				// Cookie TTL
				if ( $settings[ $role ]['cookie-ttl'] < $ttl ) {
					$ttl = $settings[ $role ]['cookie-ttl'];
				}
				// Cookie R-TTL
				if ( $settings[ $role ]['cookie-rttl'] < $rttl ) {
					$rttl = $settings[ $role ]['cookie-rttl'];
				}
			}
		}
		// Blocked IP range computation
		if ( 0 === (int) Option::network_get( 'rolemode' ) ) { // Cumulative privileges.
			if ( ( $block_external && $block_local ) || $block_none ) {
				$block = 'none';
			} elseif ( $block_external ) {
				$block = 'external';
			} elseif ( $block_local ) {
				$block = 'local';
			} else {
				$block = 'none';
			}
		} else { // Least privileges.
			if ( $block_external && $block_local ) {
				$block = 'all';
			} elseif ( $block_external ) {
				$block = 'external';
			} elseif ( $block_local ) {
				$block = 'local';
			} else {
				$block = 'none';
			}
		}
		// Limits computation
		if ( 0 === (int) Option::network_get( 'rolemode' ) ) { // Cumulative privileges.
			if ( array_key_exists( 'none', $limits ) && $limits['none'] ) {
				$limits = [];
			}
		} else { // Least privileges.
			if ( array_key_exists( 'none', $limits ) && $limits['none'] && 1 === count( $limits ) ) {
				$limits = [];
			}
		}
		if ( array_key_exists( 'none', $limits ) ) {
			unset( $limits['none'] );
		}
		// Max number of IPs
		if ( PHP_INT_MAX !== $maxip && - 1 !== $maxip ) {
			$limits['ip'] = $maxip;
		}
		$modes['block']  = $block;
		$modes['limits'] = $limits;
		$modes['method'] = $method;
		if ( PHP_INT_MAX === $idle ) {
			$idle = 0;
		}
		if ( ! $idle ) {
			$modes['idle'] = $idle;
		} elseif ( 60 > $idle ) {
			$modes['idle'] = (int) ( $idle + 100 );
		} else {
			$modes['idle'] = (int) ( $idle / 60 );
		}
		$modes['ttl']    = $ttl;
		$modes['rttl']   = $rttl;
		$result['roles'] = $roles;
		$result['modes'] = $modes;

		return $result;
	}

	/**
	 * Computes privileges for a user.
	 *
	 * @return array    The privileges.
	 * @since 2.0.0
	 */
	public function get_privileges_for_user() {
		if ( Role::SUPER_ADMIN === Role::admin_type( $this->user_id ) || Role::SINGLE_ADMIN === Role::admin_type( $this->user_id ) || Role::LOCAL_ADMIN === Role::admin_type( $this->user_id ) ) {
			$roles[] = 'administrator';
		} else {
			foreach ( Role::get_all() as $key => $detail ) {
				if ( in_array( $key, $this->user->roles, true ) ) {
					$roles[] = $key;
					//break;
				}
			}
		}

		return $this->get_privileges_for_roles( $roles );
	}

	/**
	 * Enforce sessions limitation if needed.
	 *
	 * @param mixed $user WP_User if the user is authenticated, WP_Error or null otherwise.
	 * @param string $username Username or email address.
	 * @param string $password User password.
	 * @param boolean $force_403 Optional. Force a 403 error if needed (in place of 'default' method).
	 *
	 * @return mixed WP_User if the user is allowed, WP_Error or null otherwise.
	 * @since 1.0.0
	 */
	public function limit_logins( $user, $username, $password, $force_403 = false ) {
		if ( - 1 === (int) Option::network_get( 'rolemode' ) ) {
			return $user;
		}
		if ( $user instanceof \WP_User ) {
			$this->user_id  = $user->ID;
			$this->user     = $user;
			$this->sessions = self::get_user_sessions( $this->user_id );
			$role           = '';
			$this->ip       = [];
			foreach ( $this->sessions as $session ) {
				$ip = IP::expand( $session['ip'] );
				if ( ! in_array( $ip, $this->ip, true ) ) {
					$this->ip[] = $ip;
				}
			}
			$privileges = $this->get_privileges_for_user()['modes'];
			$result     = $this->verify_ip_range( $privileges['block'] );
			$mode       = 'unknown';
			if ( 'allow' === $result ) {
				foreach ( $privileges['limits'] as $key => $limit ) {
					$limit = (int) $limit;
					if ( 0 < $limit ) {
						switch ( $key ) {
							case 'user':
								$result = $this->verify_per_user_limit( $limit );
								break;
							case 'ip':
								$result = $this->verify_ip_max( $limit );
								break;
							case 'country':
								$result = $this->verify_per_country_limit( $limit );
								break;
							case 'device-class':
							case 'device-type':
							case 'device-client':
							case 'device-browser':
							case 'device-os':
								$result = $this->verify_per_device_limit( $key, $limit );
								break;
						}
					}
					if ( 'allow' !== $result ) {
						$mode = $key;
						break;
					}
				}
			} else {
				\DecaLog\Engine::eventsLogger( POSE_SLUG )->warning( sprintf( 'New session not allowed on this IP range for %s.', User::get_user_string( $this->user_id ) ), [ 'code' => 403 ] );
				$this->die( __( '<strong>FORBIDDEN</strong>: ', 'sessions' ) . apply_filters( 'sessions_bad_ip_message', __( 'You\'re not allowed to initiate a new session from your current IP address.', 'sessions' ) ), 403 );
			}
			if ( 'allow' !== $result ) {
				$method = $privileges['method'];
				if ( $force_403 && 'default' === $method ) {
					$method = 'forced_403';
				}
				switch ( $method ) {
					case 'override':
						if ( '' !== $result ) {
							if ( array_key_exists( $result, $this->sessions ) ) {
								unset( $this->sessions[ $result ] );
								do_action( 'sessions_force_terminate', $this->user_id );
								self::set_user_sessions( $this->sessions, $this->user_id );
								\DecaLog\Engine::eventsLogger( POSE_SLUG )->notice( sprintf( 'Session overridden for %s. Reason: %s.', User::get_user_string( $this->user_id ), $mode ) );
							}
						}
						break;
					case 'default':
						\DecaLog\Engine::eventsLogger( POSE_SLUG )->warning( sprintf( 'New session not allowed for %s. Reason: %s.', User::get_user_string( $this->user_id ), $mode ), [ 'code' => 403 ] );
						Capture::login_block( $this->user_id, true );

						return new \WP_Error( '403', __( '<strong>ERROR</strong>: ', 'sessions' ) . apply_filters( 'sessions_blocked_message', __( 'You\'re not allowed to initiate a new session because your maximum number of active sessions has been reached.', 'sessions' ) ) );
					case 'redirect':
						\DecaLog\Engine::eventsLogger( POSE_SLUG )->warning( sprintf( 'New session not allowed for %s. Reason: %s.', User::get_user_string( $this->user_id ), $mode ), [ 'code' => 303 ] );
						$this->redirect( $mode );
					default:
						\DecaLog\Engine::eventsLogger( POSE_SLUG )->warning( sprintf( 'New session not allowed for %s. Reason: %s.', User::get_user_string( $this->user_id ), $mode ), [ 'code' => 403 ] );
						$this->die( __( '<strong>FORBIDDEN</strong>: ', 'sessions' ) . apply_filters( 'sessions_blocked_message', __( 'You\'re not allowed to initiate a new session because your maximum number of active sessions has been reached.', 'sessions' ) ), 403 );
				}
			} else {
				\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( sprintf( 'New session allowed for %s.', User::get_user_string( $this->user_id ) ), [ 'code' => 200 ] );
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
		$privileges = $this->get_privileges_for_user()['modes'];
		if ( 0 === (int) $privileges['idle'] ) {
			if ( array_key_exists( 'session_idle', $this->sessions[ $this->token ] ) ) {
				unset( $this->sessions[ $this->token ]['session_idle'] );
				self::set_user_sessions( $this->sessions, $this->user_id );
			}

			return false;
		}
		if ( 100 < (int) $privileges['idle'] ) {
			$this->sessions[ $this->token ]['session_idle'] = time() + (int) ( ( $privileges['idle'] - 100 ) * MINUTE_IN_SECONDS );
		} else {
			$this->sessions[ $this->token ]['session_idle'] = time() + (int) ( $privileges['idle'] * HOUR_IN_SECONDS );
		}
		self::set_user_sessions( $this->sessions, $this->user_id );

		return true;
	}

	/**
	 * Set the ip field if needed.
	 *
	 * @return boolean  True if the features are needed, false otherwise.
	 * @since 1.0.0
	 */
	private function set_ip() {
		if ( ! Option::network_get( 'followip' ) ) {
			return false;
		}
		if ( ! $this->is_needed() || ! isset( $this->user ) ) {
			return false;
		}
		if ( ! array_key_exists( $this->token, $this->sessions ) ) {
			return false;
		}
		$this->sessions[ $this->token ]['ip'] = IP::expand( $_SERVER['REMOTE_ADDR'] );
		self::set_user_sessions( $this->sessions, $this->user_id );

		return true;
	}

	/**
	 * Get the limits as printable text.
	 *
	 * @return string  The limits, ready to print.
	 * @since 1.0.0
	 */
	public function get_limits_as_text() {
		$privileges = $this->get_privileges_for_user()['modes'];
		$result     = '';
		$restrict   = [];
		switch ( $privileges['block'] ) {
			case 'external':
				$result .= esc_html__( 'Login allowed only from private IP ranges.', 'sessions' ) . ' ';
				break;
			case 'local':
				$result .= esc_html__( 'Login allowed only from public IP ranges.', 'sessions' ) . ' ';
				break;
			case 'all':
				return esc_html__( 'Login is not allowed.', 'sessions' );
		}
		foreach ( $privileges['limits'] as $key => $limit ) {
			$limit = (int) $limit;
			if ( 0 < $limit ) {
				switch ( $key ) {
					case 'user':
						$restrict[] = esc_html( sprintf( _n( '%d concurrent session.', '%d concurrent sessions.', $limit, 'sessions' ), $limit ) );
						break;
					case 'ip':
					case 'country':
					case 'device-class':
					case 'device-type':
					case 'device-client':
					case 'device-browser':
					case 'device-os':
						$restrict[] = esc_html( sprintf( _n( '%d concurrent session per %s.', '%d concurrent sessions per %s.', $limit, 'sessions' ), $limit, LimiterTypes::$selector_names[ $key ] ) );
						break;
				}
			}
		}
		if ( 0 < count( $restrict ) ) {
			$result .= implode( ' ', $restrict ) . ' ';
		}
		if ( 100 < (int) $privileges['idle'] ) {
			$result .= esc_html( sprintf( _n( 'Sessions expire after %d minute of inactivity.', 'Sessions expire after %d minutes of inactivity.', $privileges['idle'] - 100, 'sessions' ), $privileges['idle'] - 100 ) ) . ' ';
		} elseif ( 0 !== (int) $privileges['idle'] ) {
			$result .= esc_html( sprintf( _n( 'Sessions expire after %d hour of inactivity.', 'Sessions expire after %d hours of inactivity.', $privileges['idle'], 'sessions' ), $privileges['idle'] ) ) . ' ';
		}
		if ( '' === $result ) {
			$result = esc_html__( 'No restrictions.', 'sessions' );
		}

		return $result;
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		if ( Option::network_get( 'forceip' ) ) {
			$_SERVER['REMOTE_ADDR'] = IP::get_current();
		}
		if ( Option::network_get( 'killonreset' ) ) {
			add_action( 'after_password_reset', [ self::class, 'reset' ] );
		}
		add_action( 'init', [ self::class, 'initialize' ], PHP_INT_MAX );
		add_action( 'set_current_user', [ self::class, 'initialize' ], PHP_INT_MAX );
	}

	/**
	 * Initialize properties if needed.
	 *
	 * @since    1.0.0
	 */
	public function init_if_needed() {
		if ( $this->is_needed() ) {
			$this->token = Hash::simple_hash( wp_get_session_token(), false );
			$this->set_idle();
			$this->set_ip();
		}
	}

	/**
	 * Initialize static properties.
	 *
	 * @since    1.0.0
	 */
	public static function initialize() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static();
			self::$instance->init_if_needed();
			add_filter( 'auth_cookie_expiration', [ self::$instance, 'cookie_expiration' ], PHP_INT_MAX, 3 );
			add_filter( 'authenticate', [ self::$instance, 'limit_logins' ], PHP_INT_MAX, 3 );
			add_filter( 'jetpack_sso_handle_login', [ self::$instance, 'jetpack_sso_handle_login' ], PHP_INT_MAX, 2 );
		}
	}

	/**
	 * Delete sessions after password reset.
	 *
	 * @since    2.6.0
	 */
	public static function reset( $user = null ) {
		if ( isset( $user ) && $user instanceof \WP_User && 0 < $user->ID ) {
			self::delete_all_sessions( $user->ID, true );
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->notice( sprintf( 'All sessions deleted for %s after password reset.', User::get_user_string( $user->ID ) ) );
		}
	}

	/**
	 * Get an element in a cookie.
	 *
	 * @param string $scheme The cookie scheme to use: 'auth', 'secure_auth', or 'logged_in'.
	 * @param string $element The element to retrieve.
	 *
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
	 * @param mixed $user_id Optional. The user ID.
	 *
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
		if ( ! is_array( $result ) ) {
			$result = [];
		}

		return $result;
	}

	/**
	 * Get all sessions.
	 *
	 * @return  array  The details of sessions.
	 * @since   1.0.0
	 */
	public static function get_all_sessions() {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->usermeta . " WHERE meta_key = 'session_tokens' ORDER BY user_id DESC LIMIT " . (int) Option::network_get( 'buffer_limit' );
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $result as &$record ) {
			if ( ! is_array( $record['meta_value'] ) && is_string( $record['meta_value'] ) ) {
				$record['meta_value'] = maybe_unserialize( $record['meta_value'] );
			}
		}

		return $result;
	}

	/**
	 * Set sessions.
	 *
	 * @param array $sessions The sessions records.
	 * @param mixed $user_id Optional. The user ID.
	 *
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
	 * @param array $sessions The sessions records.
	 * @param integer $user_id The user ID.
	 *
	 * @return  integer   Number of terminated sessions.
	 * @since   1.0.0
	 */
	public static function auto_terminate_session( $sessions, $user_id ) {
		$span = \DecaLog\Engine::tracesLogger( POSE_SLUG )->startSpan( 'Sessions auto-terminating', DECALOG_SPAN_SHUTDOWN );
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
		\DecaLog\Engine::tracesLogger( POSE_SLUG )->endSpan( $span );

		return count( $idle ) + count( $exp );
	}


	/**
	 * Delete all sessions.
	 *
	 * @param integer $user_id Optional. Delete only for this user.
	 *
	 * @return int|bool False if it was not possible, otherwise the number of deleted meta.
	 * @since    1.0.0
	 */
	public static function delete_all_sessions( $user_id = null, $force = false ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || 1 === Environment::exec_mode() || $force ) {
			$id = get_current_user_id();
			if ( ( isset( $id ) && is_integer( $id ) && 0 < $id ) || 1 === Environment::exec_mode() || $force ) {
				$span = \DecaLog\Engine::tracesLogger( POSE_SLUG )->startSpan( 'Sessions deleting', DECALOG_SPAN_MAIN_RUN );
				if ( isset( $user_id ) && is_integer( $user_id ) && 0 < $user_id ) {
					$criteria = " AND user_id = '" . $user_id . "'";
				} else {
					$criteria = '';
				}
				$users    = 0;
				$sessions = 0;
				global $wpdb;
				$sql = "SELECT COUNT(*) AS users, SUM( CAST( SUBSTRING(`meta_value`,3,POSITION('{' IN `meta_value`) - 4) AS UNSIGNED)) AS sessions FROM " . $wpdb->usermeta . " WHERE `meta_key`='session_tokens' AND `meta_value`<>'' AND `meta_value`<>'a:0:{}' AND user_id <> '" . $id . "'" . $criteria;
				// phpcs:ignore
				$query = $wpdb->get_results( $sql, ARRAY_A );
				if ( is_array( $query ) && 0 < count( $query ) ) {
					$users    = $query[0]['users'];
					$sessions = $query[0]['sessions'];
				}
				$count = $wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key='session_tokens' AND user_id <> '" . $id . "'" . $criteria );
				if ( false === $count ) {
					\DecaLog\Engine::eventsLogger( POSE_SLUG )->warning( 'Unable to delete all sessions.' );
					\DecaLog\Engine::tracesLogger( POSE_SLUG )->endSpan( $span );

					return $count;
				} else {
					if ( isset( $user_id ) && is_integer( $user_id ) && 0 < $user_id ) {
						$cpt = 0;
					} else {
						$cpt = self::delete_remaining_sessions();
					}
					$sessions += $cpt;
					if ( 0 === $sessions ) {
						\DecaLog\Engine::eventsLogger( POSE_SLUG )->notice( 'No sessions to delete.' );
					} else {
						do_action( 'sessions_force_admin_terminate', $sessions );
						\DecaLog\Engine::eventsLogger( POSE_SLUG )->notice( sprintf( 'All sessions have been deleted (%d deleted meta).', $sessions ) );
					}
					\DecaLog\Engine::tracesLogger( POSE_SLUG )->endSpan( $span );

					return $sessions;
				}
			} else {
				\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( 'An unknown user attempted to delete all active sessions.' );

				return false;
			}
		} else {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( 'A non authorized user attempted to delete all active sessions.' );

			return false;
		}
	}

	/**
	 * Delete remaining sessions.
	 *
	 * @return int|bool False if it was not possible, otherwise the number of deleted sessions.
	 * @since    1.0.0
	 */
	public static function delete_remaining_sessions() {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$user_id   = get_current_user_id();
			$selftoken = Hash::simple_hash( wp_get_session_token(), false );
			if ( isset( $user_id ) && is_integer( $user_id ) && 0 < $user_id ) {
				$sessions = self::get_user_sessions( $user_id );
				$cpt      = count( $sessions ) - 1;
				if ( is_array( $sessions ) ) {
					foreach ( array_diff_key( array_keys( $sessions ), [ $selftoken ] ) as $key ) {
						unset( $sessions[ $key ] );
					}
					self::set_user_sessions( $sessions, $user_id );

					return $cpt;
				} else {
					return 0;
				}
			} else {
				\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( 'An unknown user attempted to delete all active sessions.' );

				return false;
			}
		} else {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( 'A non authorized user attempted to delete all active sessions.' );

			return false;
		}
	}

	/**
	 * Delete selected sessions.
	 *
	 * @param array $bulk The sessions to delete.
	 *
	 * @return int|bool False if it was not possible, otherwise the number of deleted meta.
	 * @since    1.0.0
	 */
	public static function delete_selected_sessions( $bulk ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$span      = \DecaLog\Engine::tracesLogger( POSE_SLUG )->startSpan( 'Sessions deleting', DECALOG_SPAN_MAIN_RUN );
			$selftoken = Hash::simple_hash( wp_get_session_token(), false );
			$count     = 0;
			foreach ( $bulk as $id ) {
				$val = explode( ':', $id );
				if ( 2 === count( $val ) ) {
					$token    = (string) $val[1];
					$user_id  = (int) $val[0];
					$sessions = self::get_user_sessions( $user_id );
					if ( $selftoken !== $token ) {
						unset( $sessions[ $token ] );
						if ( self::set_user_sessions( $sessions, $user_id ) ) {
							++ $count;
						}
					}
				}
			}
			if ( 0 === $count ) {
				\DecaLog\Engine::eventsLogger( POSE_SLUG )->notice( 'No sessions to delete.' );
			} else {
				do_action( 'sessions_force_admin_terminate', $count );
				\DecaLog\Engine::eventsLogger( POSE_SLUG )->notice( sprintf( 'All selected sessions have been deleted (%d deleted sessions).', $count ) );
			}
			\DecaLog\Engine::tracesLogger( POSE_SLUG )->endSpan( $span );

			return $count;
		} else {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( 'A non authorized user attempted to delete some active sessions.' );

			return false;
		}
	}

}
