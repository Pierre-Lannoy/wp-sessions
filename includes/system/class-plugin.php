<?php
/**
 * Plugin statistics handling
 *
 * Handles all user plugin statistics and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\System;

class Plugin {

	/**
	 * The details of the plugin.
	 *
	 * @since  1.0.0
	 * @var array $details The details of the plugin.
	 */
	private $details = [];

	/**
	 * The full slug of the plugin.
	 *
	 * @since  1.0.0
	 * @var string $slug The full slug of the plugin.
	 */
	private $slug = '';

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param string    $slug           The slug of the plugin.
	 * @param boolean   $skip_detection Optional. Skip header parsing.
	 * @since 1.0.0
	 */
	public function __construct( $slug, $skip_detection = true ) {
		$this->slug = $slug . '/' . $slug . '.php';
		$plugin     = WP_PLUGIN_DIR . '/' . $this->slug;
		if ( $skip_detection ) {
			$this->details = [ 'detection' => 'skipped'];
		} else {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( file_exists( $plugin ) ) {
				$this->details = \get_plugin_data( $plugin, false );
			}
		}
	}

	/**
	 * Get a value.
	 *
	 * @param string    $key   The value to retrieve.
	 * @return string   The value.
	 * @since 1.0.0
	 */
	public function get( $key ) {
		if ( is_array( $this->details ) && array_key_exists( $key, $this->details ) ) {
			return $this->details[ $key ];
		} else {
			return '';
		}
	}

	/**
	 * Verify if the plugin was correctly detected.
	 *
	 * @return  boolean True if the plugin was detected, false otherwise.
	 * @since 1.0.0
	 */
	public function is_detected() {
		return [] !== $this->details;
	}

	/**
	 * Verify the waiting update.
	 *
	 * @return  boolean|string The new version if plugin needs update, false otherwise.
	 * @since 1.0.0
	 */
	public function waiting_update() {
		if ( ! $this->is_detected() ) {
			return false;
		}
		$update_cache = get_site_transient( 'update_plugins' );
		$update_cache = is_object( $update_cache ) ? $update_cache : new \stdClass();
		if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->slug ] ) ) {
			return false;
		}
		return $update_cache->response[ $this->slug ]->new_version;
	}

	/**
	 * Verify the auto-update status.
	 *
	 * @return  boolean True if plugin is auto-updatable, false otherwise.
	 * @since 2.0.0
	 */
	public function auto_update() {
		if ( ! $this->is_detected() ) {
			return false;
		}
		return in_array( $this->slug, (array) get_site_option( 'auto_update_plugins', [] ), true );
	}

	/**
	 * Set the auto-update status.
	 *
	 * @param   boolean $status Optional. The status to set.
	 * @return  boolean True if it is successful, false otherwise.
	 * @since 2.0.0
	 */
	public function set_auto_update( $status = true ) {
		if ( ! $this->is_detected() ) {
			return false;
		}
		$auto_updates = (array) get_site_option( 'auto_update_plugins', [] );
		if ( $status ) {
			$auto_updates[] = $this->slug;
			$auto_updates   = array_unique( $auto_updates );
		} else {
			$auto_updates = array_diff( $auto_updates, [ $this->slug ] );
		}
		$all_items    = apply_filters( 'all_plugins', get_plugins() );
		$auto_updates = array_intersect( $auto_updates, array_keys( $all_items ) );
		return update_site_option( 'auto_update_plugins', $auto_updates );
	}

	/**
	 * Switch the auto-update status.
	 *
	 * @return  boolean True if it is successful, false otherwise.
	 * @since 2.0.0
	 */
	public function switch_auto_update() {
		return $this->set_auto_update( ! $this->auto_update() );
	}

	/**
	 * Verify if the WP version is ok.
	 *
	 * @return  boolean True if the WP version is ok, false otherwise.
	 * @since 1.0.0
	 */
	public function is_required_wp_ok() {
		global $wp_version;
		return ( ! version_compare( $wp_version, $this->get( 'RequiresWP' ), '<' ) );
	}

	/**
	 * Verify if the PHP version is ok.
	 *
	 * @return  boolean True if the PHP version is ok, false otherwise.
	 * @since 1.0.0
	 */
	public function is_required_php_ok() {
		return ( ! version_compare( PHP_VERSION, $this->get( 'RequiresPHP' ), '<' ) );
	}
}
