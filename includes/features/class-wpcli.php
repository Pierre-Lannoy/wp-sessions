<?php
/**
 * WP-CLI for Sessions.
 *
 * Adds WP-CLI commands to Sessions
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Feature;

use POSessions\System\Environment;

use POSessions\System\Option;
use POSessions\System\Markdown;
use POSessions\Plugin\Feature\Analytics;
use POSessions\Plugin\Feature\Schema;
use POSessions\System\Session;
use POSessions\System\Timezone;
use POSessions\System\GeoIP;
use POSessions\System\User;
use POSessions\System\IP;
use POSessions\System\UserAgent;
use POSessions\System\Date;
use POSessions\System\EmojiFlag;
use Spyc;

/**
 * Manages users' sessions and get details about their use.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Wpcli {

	/**
	 * List of exit codes.
	 *
	 * @since    1.0.0
	 * @var array $exit_codes Exit codes.
	 */
	private $exit_codes = [
		0   => 'operation successful.',
		1   => 'unrecognized setting.',
		2   => 'unrecognized action.',
		3   => 'analytics are disabled.',
		4   => 'unrecognized mode.',
		5   => 'user doesn\'t exist.',
		255 => 'unknown error.',
	];

	/**
	 * Write ids as clean stdout.
	 *
	 * @param   array   $ids   The ids.
	 * @param   string  $field  Optional. The field to output.
	 * @since   1.0.0
	 */
	private function write_ids( $ids, $field = '' ) {
		$result = '';
		$last   = end( $ids );
		foreach ( $ids as $key => $id ) {
			if ( '' === $field ) {
				$result .= $key;
			} else {
				$result .= $id[$field];
			}
			if ( $id !== $last ) {
				$result .= ' ';
			}
		}
		// phpcs:ignore
		fwrite( STDOUT, $result );
	}

	/**
	 * Write an error.
	 *
	 * @param   integer  $code      Optional. The error code.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function error( $code = 255, $stdout = false ) {
		$msg = '[' . POSE_PRODUCT_NAME . '] ' . ucfirst( $this->exit_codes[ $code ] );
		if ( \WP_CLI\Utils\isPiped() ) {
			// phpcs:ignore
			fwrite( STDOUT, '' );
			// phpcs:ignore
			exit( $code );
		} elseif ( $stdout ) {
			// phpcs:ignore
			fwrite( STDERR, $msg );
			// phpcs:ignore
			exit( $code );
		} else {
			\WP_CLI::error( $msg );
		}
	}

	/**
	 * Write a warning.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function warning( $msg, $result = '', $stdout = false ) {
		$msg = '[' . POSE_PRODUCT_NAME . '] ' . ucfirst( $msg );
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::warning( $msg );
		}
	}

	/**
	 * Write a success.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function success( $msg, $result = '', $stdout = false ) {
		$msg = '[' . POSE_PRODUCT_NAME . '] ' . ucfirst( $msg );
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::success( $msg );
		}
	}

	/**
	 * Write an error from a WP_Error object.
	 *
	 * @param   \WP_Error  $err     The error object.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function error_from_object( $err, $stdout = false ) {
		$msg = $this->exit_codes[255];
		if ( is_wp_error( $err ) ) {
			$msg = $err->get_error_message();
		}
		$msg = '[' . POSE_PRODUCT_NAME . '] ' . ucfirst( $msg );
		if ( \WP_CLI\Utils\isPiped() ) {
			// phpcs:ignore
			fwrite( STDOUT, '' );
			// phpcs:ignore
			exit( 255 );
		} elseif ( $stdout ) {
			// phpcs:ignore
			fwrite( STDERR, ucfirst( $msg ) );
			// phpcs:ignore
			exit( 255 );
		} else {
			\WP_CLI::error( $msg );
		}
	}

	/**
	 * Write a wimple line.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function line( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::line( $msg );
		}
	}

	/**
	 * Write a wimple log line.
	 *
	 * @param   string   $msg       The message.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function log( $msg, $stdout = false ) {
		if ( ! \WP_CLI\Utils\isPiped() && ! $stdout ) {
			\WP_CLI::log( $msg );
		}
	}

	/**
	 * Get params from command line.
	 *
	 * @param   array   $args   The command line parameters.
	 * @return  array The true parameters.
	 * @since   1.0.0
	 */
	private function get_params( $args ) {
		$result = '';
		if ( array_key_exists( 'settings', $args ) ) {
			$result = \json_decode( $args['settings'], true );
		}
		if ( ! $result || ! is_array( $result ) ) {
			$result = [];
		}
		return $result;
	}

	/**
	 * Get Sessions details and operation modes.
	 *
	 * ## EXAMPLES
	 *
	 * wp sessions status
	 *
	 *
	 *     === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-sessions/blob/master/WP-CLI.md ===
	 *
	 */
	public function status( $args, $assoc_args ) {
		\WP_CLI::line( sprintf( '%s is running.', Environment::plugin_version_text() ) );
		switch ( Option::network_get( 'rolemode' ) ) {
			case -1:
				\WP_CLI::line( 'Operation mode: no role limitation.' );
				break;
			case 0:
				\WP_CLI::line( 'Operation mode: role limitation with cumulative privileges.' );
				break;
			case 1:
				\WP_CLI::line( 'Operation mode: role limitation with least privileges.' );
				break;
				
		}
		if ( Option::network_get( 'forceip' ) ) {
			\WP_CLI::line( 'IP override: enabled.' );
		} else {
			\WP_CLI::line( 'IP override: disabled.' );
		}
		if ( Option::network_get( 'followip' ) ) {
			\WP_CLI::line( 'IP follow-up: enabled.' );
		} else {
			\WP_CLI::line( 'IP follow-up: disabled.' );
		}
		if ( Option::network_get( 'analytics' ) ) {
			\WP_CLI::line( 'Analytics: enabled.' );
		} else {
			\WP_CLI::line( 'Analytics: disabled.' );
		}
		if ( Option::network_get( 'metrics' ) ) {
			\WP_CLI::line( 'Metrics collation: enabled.' );
		} else {
			\WP_CLI::line( 'Metrics collation: disabled.' );
		}
		if ( \DecaLog\Engine::isDecalogActivated() ) {
			\WP_CLI::line( 'Logging support: ' . \DecaLog\Engine::getVersionString() . '.');
		} else {
			\WP_CLI::line( 'Logging support: no.' );
		}
		$geo = new GeoIP();
		if ( $geo->is_installed() ) {
			\WP_CLI::line( 'IP information support: yes (' . $geo->get_full_name() . ').');
		} else {
			\WP_CLI::line( 'IP information support: no.' );
		}
		if ( defined( 'PODD_VERSION' ) ) {
			\WP_CLI::line( 'Device detection support: yes (Device Detector v' . PODD_VERSION . ').');
		} else {
			\WP_CLI::line( 'Device detection support: no.' );
		}
	}

	/**
	 * Modify Sessions main settings.
	 *
	 * ## OPTIONS
	 *
	 * <enable|disable>
	 * : The action to take.
	 *
	 * <analytics|ip-override|ip-follow|metrics|kill-on-reset>
	 * : The setting to change.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by Sessions.
	 *
	 * ## EXAMPLES
	 *
	 * wp sessions settings disable analytics --yes
	 *
	 *
	 *     === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-sessions/blob/master/WP-CLI.md ===
	 *
	 */
	public function settings( $args, $assoc_args ) {
		$stdout  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$action  = isset( $args[0] ) ? (string) $args[0] : '';
		$setting = isset( $args[1] ) ? (string) $args[1] : '';
		switch ( $action ) {
			case 'enable':
				switch ( $setting ) {
					case 'analytics':
						Option::network_set( 'analytics', true );
						$this->success( 'analytics are now activated.', '', $stdout );
						break;
					case 'ip-follow':
						Option::network_set( 'followip', true );
						$this->success( 'IP follow-up is now activated.', '', $stdout );
						break;
					case 'ip-override':
						Option::network_set( 'forceip', true );
						$this->success( 'IP override is now activated.', '', $stdout );
						break;
					case 'metrics':
						Option::network_set( 'metrics', true );
						$this->success( 'metrics collation is now activated.', '', $stdout );
						break;
					case 'kill-on-reset':
						Option::network_set( 'killonreset', true );
						$this->success( 'users\' sessions will now be deleted after password resets.', '', $stdout );
						break;
					default:
						$this->error( 1, $stdout );
				}
				break;
			case 'disable':
				switch ( $setting ) {
					case 'analytics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate analytics?', $assoc_args );
						Option::network_set( 'analytics', false );
						$this->success( 'analytics are now deactivated.', '', $stdout );
						break;
					case 'ip-follow':
						\WP_CLI::confirm( 'Are you sure you want to deactivate IP follow-up?', $assoc_args );
						Option::network_set( 'followip', false );
						$this->success( 'IP follow-up is now deactivated.', '', $stdout );
						break;
					case 'ip-override':
						\WP_CLI::confirm( 'Are you sure you want to deactivate IP override?', $assoc_args );
						Option::network_set( 'forceip', false );
						$this->success( 'IP override is now deactivated.', '', $stdout );
						break;
					case 'metrics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate metrics collation?', $assoc_args );
						Option::network_set( 'metrics', false );
						$this->success( 'metrics collation is now deactivated.', '', $stdout );
						break;
					case 'kill-on-reset':
						\WP_CLI::confirm( 'Are you sure you want to deactivate sessions deletion after password resets?', $assoc_args );
						Option::network_set( 'killonreset', false );
						$this->success( 'users\' sessions will now be left untouched after password resets.', '', $stdout );
						break;
					default:
						$this->error( 1, $stdout );
				}
				break;
			default:
				$this->error( 2, $stdout );
		}
	}

	/**
	 * Modify Sessions operation mode.
	 *
	 * ## OPTIONS
	 *
	 * <set>
	 * : The action to take.
	 *
	 * <none|cumulative|least>
	 * : The mode to set.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by Sessions.
	 *
	 * ## EXAMPLES
	 *
	 * wp sessions mode set none --yes
	 *
	 *
	 *     === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-sessions/blob/master/WP-CLI.md ===
	 *
	 */
	public function mode( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$action = isset( $args[0] ) ? (string) $args[0] : '';
		$mode   = isset( $args[1] ) ? (string) $args[1] : '';
		switch ( $action ) {
			case 'set':
				switch ( $mode ) {
					case 'none':
						\WP_CLI::confirm( 'Are you sure you want to deactivate role-mode?', $assoc_args );
						Option::network_set( 'rolemode', -1 );
						$this->success( 'operation mode is now "no role limitation".', '', $stdout );
						break;
					case 'cumulative':
						Option::network_set( 'rolemode', 0 );
						$this->success( 'operation mode is now "role limitation with cumulative privileges".', '', $stdout );
						break;
					case 'least':
						Option::network_set( 'rolemode', 1 );
						$this->success( 'operation mode is now "role limitation with least privileges".', '', $stdout );
						break;
					default:
						$this->error( 4, $stdout );
						break;
				}
				break;
			default:
				$this->error( 2, $stdout );
		}
	}

	/**
	 * Get sessions analytics for today.
	 *
	 * ## OPTIONS
	 *
	 * [--site=<site_id>]
	 * : The site for which to display analytics. May be 0 (all network) or an integer site id. Only useful with multisite environments.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--format=<format>]
	 * : Set the output format. Note if json is chosen: full metadata is outputted too.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - count
	 * ---
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by Sessions.
	 *
	 * ## EXAMPLES
	 *
	 * wp sessions analytics
	 *
	 *
	 *    === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-sessions/blob/master/WP-CLI.md ===
	 *
	 */
	public function analytics( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$site   = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'site', 0 );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		if ( ! Option::network_get( 'analytics' ) ) {
			$this->error( 3, $stdout );
		}
		$analytics = Analytics::get_status_kpi_collection( [ 'site_id' => $site ] );
		$result    = [];
		if ( array_key_exists( 'data', $analytics ) ) {
			foreach ( $analytics['data'] as $kpi ) {
				$item                = [];
				$item['kpi']         = $kpi['name'];
				$item['description'] = $kpi['description'];
				$item['value']       = $kpi['value']['human'];
				if ( array_key_exists( 'ratio', $kpi ) && isset( $kpi['ratio'] ) ) {
					$item['ratio'] = $kpi['ratio']['percent'] . '%';
				} else {
					$item['ratio'] = '-';
				}
				$item['variation'] = ( 0 < $kpi['variation']['percent'] ? '+' : '' ) . (string) $kpi['variation']['percent'] . '%';
				$result[]          = $item;
			}
		}
		if ( 'json' === $format ) {
			$detail = wp_json_encode( $analytics );
			$this->line( $detail, $detail, $stdout );
		} elseif ( 'yaml' === $format ) {
			unset( $analytics['assets'] );
			$detail = Spyc::YAMLDump( $analytics, true, true, true );
			$this->line( $detail, $detail, $stdout );
		} else {
			\WP_CLI\Utils\format_items( $assoc_args['format'], $result, [ 'kpi', 'description', 'value', 'ratio', 'variation' ] );
		}
	}

	/**
	 * Get information on exit codes.
	 *
	 * ## OPTIONS
	 *
	 * <list>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing exit codes.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * Lists available exit codes:
	 * + wp sessions exitcode list
	 * + wp sessions exitcode list --format=json
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-sessions/blob/master/WP-CLI.md ===
	 *
	 */
	public function exitcode( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$action = isset( $args[0] ) ? $args[0] : 'list';
		$codes  = [];
		foreach ( $this->exit_codes as $key => $msg ) {
			$codes[ $key ] = [ 'code' => $key, 'meaning' => ucfirst( $msg ) ];
		}
		switch ( $action ) {
			case 'list':
				if ( 'ids' === $format ) {
					$this->write_ids( $codes );
				} else {
					\WP_CLI\Utils\format_items( $format, $codes, [ 'code', 'meaning' ] );
				}
				break;
		}
	}

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode(  'WP-CLI.md', $attributes  );
	}

	/**
	 * Manage active sessions.
	 *
	 * ## OPTIONS
	 *
	 * <list|kill>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 *  - kill
	 * ---
	 *
	 * [<user_id>]
	 * : The id of the user to perform an action/search on. If nothing is specified, it's on all users.
	 *
	 * [--detail=<detail>]
	 * : The details of the output when listing application passwords.
	 * ---
	 * default: short
	 * options:
	 *  - short
	 *  - full
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing application passwords. Note if json or yaml is chosen: full metadata is outputted too.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by Sessions.
	 *
	 * ## EXAMPLES
	 *
	 * List active sessions:
	 * + wp sessions active list
	 * + wp sessions active list 1
	 *
	 * Kill active sessions:
	 * + wp sessions active kill
	 * + wp sessions active kill 1 --yes
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-sessions/blob/master/WP-CLI.md ===
	 *
	 */
	public function active( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$detail = \WP_CLI\Utils\get_flag_value( $assoc_args, 'detail', 'short' );
		$id     = '';
		$action = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$id = (int) $args[1];
			if ( false === get_userdata( $id ) ) {
				$this->error( 5, $stdout );
			}
		}
		switch ( $action ) {
			case 'list':
				$sessions = [];
				$list     = [];
				$tz       = Timezone::network_get();
				if ( '' !== $id ) {
					$list[] = [
						'user_id'    => $id,
						'meta_value' => Session::get_user_sessions( $id ),
					];
				} else {
					$list = Session::get_all_sessions();
				}
				foreach ( $list as $user ) {
					if ( array_key_exists( 'meta_value', $user ) && is_array( $user['meta_value'] ) ) {
						foreach ( $user['meta_value'] as $token => $session ) {
							$sessions[ $token ]['token']   = $token;
							$sessions[ $token ]['user-id'] = (int) $user['user_id'];
							$sessions[ $token ]['user']    = User::get_user_string( $user['user_id'] );
							if ( array_key_exists( 'expiration', $session ) ) {
								$datetime = new \DateTime( date( 'Y-m-d H:i:s', $session['expiration'] ) );
								$datetime->setTimezone( $tz );
								$sessions[ $token ]['standard exp'] = $datetime->format( 'Y-m-d H:i' );
							} else {
								$sessions[ $token ]['standard exp'] = 'never';
							}
							if ( array_key_exists( 'login', $session ) ) {
								$datetime = new \DateTime( date( 'Y-m-d H:i:s', $session['login'] ) );
								$datetime->setTimezone( $tz );
								$sessions[ $token ]['login'] = $datetime->format( 'Y-m-d H:i' );
							} else {
								$sessions[ $token ]['login'] = 'never';
							}
							if ( array_key_exists( 'session_idle', $session ) ) {
								$value = $session['session_idle'] - time();
								if ( 0 === $value ) {
									$sessions[ $token ]['idle exp'] = 'now';
								} else {
									$value = 0 - $value;
									if ( $value < 72 * HOUR_IN_SECONDS ) {
										$sessions[ $token ]['idle exp'] = implode( ', ', Date::get_age_array_from_seconds( $value, true, true ) );
									} else {
										$sessions[ $token ]['idle exp'] = (int) round( $value / ( 24 * HOUR_IN_SECONDS ), 0 ) . 'days';
									}
								}
							} else {
								$sessions[ $token ]['idle exp'] = '-';
							}
							$sessions[ $token ]['ip'] = $session['ip'];
							if ( 'full' === $detail ) {
								$sessions[ $token ]['device'] = '-';
								$sessions[ $token ]['os']     = '-';
								$sessions[ $token ]['client'] = '-';
								if ( array_key_exists( 'ua', $session ) ) {
									$device                       = UserAgent::get( $session['ua'] );
									$sessions[ $token ]['device'] = ( '' !== $device->brand_name ? $device->brand_name : esc_html__( 'Generic', 'sessions' ) ) . ( '' !== $device->model_name ? ' ' . $device->model_name : '' );
									$sessions[ $token ]['os']     = $device->os_name . ( '' !== $device->os_version ? ' ' . $device->os_version : '' );
									$sessions[ $token ]['client'] = ( '' !== $device->client_name ? $device->client_name : $device->client_full_type );
									if ( $device->client_is_browser ) {
										$sessions[ $token ]['client'] = $device->client_name . ( '' !== $device->client_version ? ' ' . $device->client_version : '' );
									}
								}
							}
						}
					}
				}
				usort(
					$sessions,
					function ( $a, $b ) {
						return strcmp( strtolower( $a[ 'user' ] ), strtolower( $b[ 'user' ] ) );
					}
				);
				if ( 'full' === $detail ) {
					$detail = [ 'user', 'ip', 'device', 'os', 'client', 'login', 'idle exp', 'standard exp' ];
				} else {
					$detail = [ 'user', 'ip', 'login', 'idle exp', 'standard exp' ];
				}
				if ( 'ids' === $format ) {
					$this->write_ids( $sessions, 'uuid' );
				} elseif ( 'yaml' === $format ) {
					$details = Spyc::YAMLDump( $sessions, true, true, true );
					$this->line( $details, $details, $stdout );
				}  elseif ( 'json' === $format ) {
					$details = wp_json_encode( $sessions );
					$this->line( $details, $details, $stdout );
				} else {
					\WP_CLI\Utils\format_items( $format, $sessions, $detail );
				}
				break;
			case 'kill':
				if ( '' === $id ) {
					\WP_CLI::confirm( 'Are you sure you want to kill all sessions for all users?', $assoc_args );
					$cnt = Session::delete_all_sessions();
				} else {
					\WP_CLI::confirm( 'Are you sure you want to kill all sessions for this user?', $assoc_args );
					$cnt = Session::delete_all_sessions( $id );
				}
				if ( false === $cnt ) {
					$this->error( 255, $stdout );
				} else {
					if ( 0 === (int) $cnt ) {
						$this->success( 'no session to kill.', 0, $stdout );
					} else {
						$this->success( $cnt . ' session(s) killed.', $cnt, $stdout );
					}
				}
				break;
		}
	}

}

add_shortcode( 'pose-wpcli', [ 'POSessions\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'sessions', 'POSessions\Plugin\Feature\Wpcli' );
}