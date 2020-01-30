<?php
/**
 * Options handling
 *
 * Handles all options operations for the plugin.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\System;

use POSessions\System\Role;

/**
 * Define the options functionality.
 *
 * Handles all options operations for the plugin.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Option {

	/**
	 * The list of defaults options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $defaults    The defaults list.
	 */
	private static $defaults = [];

	/**
	 * The list of network-wide options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $network    The network-wide list.
	 */
	private static $network = [];

	/**
	 * The list of site options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $site    The site list.
	 */
	private static $specific = [];

	/**
	 * The list of private options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $private    The private options list.
	 */
	private static $private = [];

	/**
	 * Set the defaults options.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		// Options for whole site(s).
		self::$defaults['use_cdn']           = false;
		self::$defaults['script_in_footer']  = false;
		self::$defaults['download_favicons'] = false;
		self::$defaults['display_nag']       = true;
		self::$defaults['nags']              = [];
		self::$defaults['version']           = '0.0.0';
		self::$defaults['history']           = 30;
		self::$defaults['analytics']         = true;
		self::$network                       = [ 'version', 'use_cdn', 'download_favicons', 'script_in_footer', 'display_nag', 'analytics', 'history' ];
		// Specific options.
		self::$defaults['limit']  = 'none';
		self::$defaults['method'] = 'block';
		self::$defaults['idle']   = 0;
		self::$specific           = [ 'limit', 'method', 'idle' ];
	}

	/**
	 * Get the options infos for Site Health "info" tab.
	 *
	 * @since 1.0.0
	 */
	public static function debug_info() {
		$result = [];
		$si     = '[Site Option] ';
		$nt     = $si;
		if ( Environment::is_wordpress_multisite() ) {
			$nt = '[Network Option] ';
		}
		foreach ( self::$network as $opt ) {
			$val            = self::network_get( $opt );
			$result[ $opt ] = [
				'label'   => $nt . $opt,
				'value'   => is_bool( $val ) ? $val ? 1 : 0 : $val,
				'private' => in_array( $opt, self::$private, true ),
			];
		}
		foreach ( self::$site as $opt ) {
			$val            = self::site_get( $opt );
			$result[ $opt ] = [
				'label'   => $si . $opt,
				'value'   => is_bool( $val ) ? $val ? 1 : 0 : $val,
				'private' => in_array( $opt, self::$private, true ),
			];
		}
		return $result;
	}

	/**
	 * Get an option value for a site.
	 *
	 * @param   string  $option     Option name. Expected to not be SQL-escaped.
	 * @param   boolean $default    Optional. The default value if option doesn't exists.
	 *                              This default value is used only if $option is not present
	 *                              in the $defaults array.
	 * @return  mixed   The value of the option.
	 * @since 1.0.0
	 */
	public static function site_get( $option, $default = null ) {
		if ( array_key_exists( $option, self::$defaults ) ) {
			$default = self::$defaults[ $option ];
		}
		return get_option( POSE_PRODUCT_ABBREVIATION . '_' . $option, $default );
	}

	/**
	 * Get an option value for a network.
	 *
	 * @param   string  $option     Option name. Expected to not be SQL-escaped.
	 * @param   boolean $default    Optional. The default value if option doesn't exists.
	 *                              This default value is used only if $option is not present
	 *                              in the $defaults array.
	 * @return  mixed   The value of the option.
	 * @since 1.0.0
	 */
	public static function network_get( $option, $default = null ) {
		if ( array_key_exists( $option, self::$defaults ) ) {
			$default = self::$defaults[ $option ];
		}
		return get_site_option( POSE_PRODUCT_ABBREVIATION . '_' . $option, $default );
	}

	/**
	 * Verify if an option exists.
	 *
	 * @param   string $option Option name. Expected to not be SQL-escaped.
	 * @return  boolean   True if the option exists, false otherwise.
	 * @since 1.0.0
	 */
	public static function site_exists( $option ) {
		return 'non_existent_option' !== get_option( POSE_PRODUCT_ABBREVIATION . '_' . $option, 'non_existent_option' );
	}

	/**
	 * Verify if an option exists.
	 *
	 * @param   string $option Option name. Expected to not be SQL-escaped.
	 * @return  boolean   True if the option exists, false otherwise.
	 * @since 1.0.0
	 */
	public static function network_exists( $option ) {
		return 'non_existent_option' !== get_site_option( POSE_PRODUCT_ABBREVIATION . '_' . $option, 'non_existent_option' );
	}

	/**
	 * Set an option value for a site.
	 *
	 * @param string      $option   Option name. Expected to not be SQL-escaped.
	 * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
	 * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
	 *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
	 *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
	 *                              the default value is 'yes'. Default null.
	 * @return boolean  False if value was not updated and true if value was updated.
	 * @since 1.0.0
	 */
	public static function site_set( $option, $value, $autoload = null ) {
		return update_option( POSE_PRODUCT_ABBREVIATION . '_' . $option, $value, $autoload );
	}

	/**
	 * Set an option value for a network.
	 *
	 * @param string $option   Option name. Expected to not be SQL-escaped.
	 * @param mixed  $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
	 * @return boolean  False if value was not updated and true if value was updated.
	 * @since 1.0.0
	 */
	public static function network_set( $option, $value ) {
		if ( false === $value ) {
			update_site_option( POSE_PRODUCT_ABBREVIATION . '_' . $option, true );
		}
		return update_site_option( POSE_PRODUCT_ABBREVIATION . '_' . $option, $value );
	}

	/**
	 * Delete all options for a site.
	 *
	 * @return integer Number of deleted items.
	 * @since 1.0.0
	 */
	public static function site_delete_all() {
		global $wpdb;
		$result = 0;
		// phpcs:ignore
		$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '" . POSE_PRODUCT_ABBREVIATION . '_%' . "';" );
		foreach ( $delete as $option ) {
			if ( delete_option( $option ) ) {
				++$result;
			}
		}
		return $result;
	}

	/**
	 * Reset some options to their defaults.
	 *
	 * @since 1.0.0
	 */
	public static function reset_to_defaults() {
		self::network_set( 'use_cdn', self::$defaults['use_cdn'] );
		self::network_set( 'download_favicons', self::$defaults['download_favicons'] );
		self::network_set( 'script_in_footer', self::$defaults['script_in_footer'] );
		self::network_set( 'display_nag', self::$defaults['display_nag'] );
		self::network_set( 'analytics', self::$defaults['analytics'] );
		self::network_set( 'history', self::$defaults['history'] );
	}

	/**
	 * Normalize the role settings.
	 *
	 * @param array $settings   The settings to normalize;
	 * @return  array   The normalized settings.
	 * @since 1.0.0
	 */
	public static function roles_normalize( $settings ) {
		foreach ( Role::get_all() as $role => $detail ) {
			if ( array_key_exists( $role, $settings ) ) {
				foreach ( self::$specific as $spec ) {
					if ( ! array_key_exists( $spec, $settings[ $role ] ) ) {
						$settings[ $role ][ $spec ] = self::$defaults[ $spec ];
					}
				}
			} else {
				foreach ( self::$specific as $spec ) {
					$settings[ $role ][ $spec ] = self::$defaults[ $spec ];
				}
			}
		}
		return $settings;
	}

	/**
	 * Get the role settings.
	 *
	 * @return  array   The value of role settings.
	 * @since 1.0.0
	 */
	public static function roles_get() {
		return self::roles_normalize( self::network_get( 'roles', [] ) );
	}

	/**
	 * Set the role settings.
	 *
	 * @param array $settings   The settings to normalize;
	 * @return boolean  False if value was not updated and true if value was updated.
	 * @since 1.0.0
	 */
	public static function roles_set( $settings ) {
		return self::network_set( 'roles', self::roles_normalize( $settings ) );
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}
}

Option::init();
