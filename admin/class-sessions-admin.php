<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin;

use POSessions\Plugin\Feature\Analytics;
use POSessions\Plugin\Feature\AnalyticsFactory;
use POSessions\System\Assets;
use POSessions\System\Environment;
use POSessions\System\Logger;
use POSessions\System\Role;
use POSessions\System\Option;
use POSessions\System\Form;
use POSessions\System\Blog;
use POSessions\System\Date;
use POSessions\System\Timezone;
use POSessions\System\GeoIP;
use POSessions\Plugin\Feature\LimiterTypes;
use PerfOpsOne\AdminMenus;
use POSessions\System\Statistics;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Sessions_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( POSE_ASSETS_ID, POSE_ADMIN_URL, 'css/sessions.min.css' );
		$this->assets->register_style( 'pose-daterangepicker', POSE_ADMIN_URL, 'css/daterangepicker.min.css' );
		$this->assets->register_style( 'pose-switchery', POSE_ADMIN_URL, 'css/switchery.min.css' );
		$this->assets->register_style( 'pose-tooltip', POSE_ADMIN_URL, 'css/tooltip.min.css' );
		$this->assets->register_style( 'pose-chartist', POSE_ADMIN_URL, 'css/chartist.min.css' );
		$this->assets->register_style( 'pose-chartist-tooltip', POSE_ADMIN_URL, 'css/chartist-plugin-tooltip.min.css' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( POSE_ASSETS_ID, POSE_ADMIN_URL, 'js/sessions.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'pose-moment-with-locale', POSE_ADMIN_URL, 'js/moment-with-locales.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'pose-daterangepicker', POSE_ADMIN_URL, 'js/daterangepicker.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'pose-switchery', POSE_ADMIN_URL, 'js/switchery.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'pose-chartist', POSE_ADMIN_URL, 'js/chartist.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'pose-chartist-tooltip', POSE_ADMIN_URL, 'js/chartist-plugin-tooltip.min.js', [ 'pose-chartist' ] );
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfops_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['analytics'][] = [
				'name'          => esc_html_x( 'Sessions', 'Common name - not the name of the plugin.', 'sessions' ),
				/* translators: as in the sentence "View sessions and accounts activity on your network." or "View sessions and accounts activity on your website." */
				'description'   => sprintf( esc_html__( 'View sessions and accounts activity on your %s.', 'sessions' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'sessions' ) : esc_html__( 'website', 'sessions' ) ),
				'icon_callback' => [ \POSessions\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'pose-viewer',
				'page_title'    => esc_html__( 'Sessions Analytics', 'sessions' ),
				'menu_title'    => esc_html_x( 'Sessions', 'Common name - not the name of the plugin.', 'sessions' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_viewer_page' ],
				'position'      => 50,
				'plugin'        => POSE_SLUG,
				'activated'     => Option::network_get( 'analytics' ),
				'remedy'        => esc_url( admin_url( 'admin.php?page=pose-settings&tab=misc' ) ),
			];
			$perfops['tools'][]     = [
				'name'          => esc_html_x( 'Sessions', 'Common name - not the name of the plugin.', 'sessions' ),
				/* translators: as in the sentence "Browse and manage active sessions on your network." or "Browse and manage active sessions on your website." */
				'description'   => sprintf( esc_html__( 'Browse and manage active sessions on your %s.', 'sessions' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'sessions' ) : esc_html__( 'website', 'sessions' ) ),
				'icon_callback' => [ \POSessions\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'pose-manager',
				'page_title'    => esc_html__( 'Active Sessions Management', 'sessions' ),
				'menu_title'    => esc_html_x( 'Sessions', 'Common name - not the name of the plugin.', 'sessions' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_manager_page' ],
				'position'      => 50,
				'plugin'        => POSE_SLUG,
				'activated'     => true,
				'remedy'        => '',
			];
			$perfops['settings'][]  = [
				'name'          => POSE_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \POSessions\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'pose-settings',
				/* translators: as in the sentence "Sessions Settings" or "WordPress Settings" */
				'page_title'    => sprintf( esc_html__( '%s Settings', 'sessions' ), POSE_PRODUCT_NAME ),
				'menu_title'    => POSE_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'position'      => 50,
				'plugin'        => POSE_SLUG,
				'version'       => POSE_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\POSessions\System\Statistics', 'sc_get_raw' ],
			];
		}
		return $perfops;
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfops_admin_menus', [ $this, 'init_perfops_admin_menus' ] );
		AdminMenus::initialize();
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'pose_plugin_features_section', esc_html__( 'Plugin Features', 'sessions' ), [ $this, 'plugin_features_section_callback' ], 'pose_plugin_features_section' );
		add_settings_section( 'pose_plugin_options_section', esc_html__( 'Plugin options', 'sessions' ), [ $this, 'plugin_options_section_callback' ], 'pose_plugin_options_section' );
		add_settings_section( 'pose_plugin_roles_section', '', [ $this, 'plugin_roles_section_callback' ], 'pose_plugin_roles_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=pose-settings' ) ), esc_html__( 'Settings', 'sessions' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=pose-viewer' ) ), esc_html__( 'Statistics', 'sessions' ) );
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, POSE_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . POSE_SLUG . '/">' . __( 'Support', 'sessions' ) . '</a>';
			$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-sessions">' . __( 'GitHub repository', 'sessions' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the viewer page.
	 *
	 * @since 1.0.0
	 */
	public function get_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics();
		include POSE_ADMIN_DIR . 'partials/sessions-admin-view-analytics.php';
	}

	/**
	 * Get the content of the manager page.
	 *
	 * @since 1.0.0
	 */
	public function get_manager_page() {
		include POSE_ADMIN_DIR . 'partials/sessions-admin-tools.php';
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
					}
					break;
				case 'roles':
					switch ( $action ) {
						case 'do-save':
							$this->save_roles_options();
							break;
					}
					break;
			}
		}
		include POSE_ADMIN_DIR . 'partials/sessions-admin-settings-main.php';
	}

	/**
	 * Save the core plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_roles_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'pose-plugin-options' ) ) {
				$settings = Option::roles_get();
				foreach ( Role::get_all() as $role => $detail ) {
					foreach ( Option::$specific as $spec ) {
						if ( array_key_exists( 'pose_plugin_roles_' . $spec . '_' . $role, $_POST ) ) {
							$settings[ $role ][ $spec ] = filter_input( INPUT_POST, 'pose_plugin_roles_' . $spec . '_' . $role );
						}
					}
				}
				Option::roles_set( $settings );
				$message  = esc_html__( 'Plugin settings have been saved.', 'sessions' );
				$message .= '<br/>' . esc_html__( 'Note these settings will only affect new sessions.', 'sessions' );
				$message .= ' ' . esc_html__( 'For immediate implementation for all accounts, you must terminate all current sessions.', 'sessions' );
				$code     = 0;
				add_settings_error( 'pose_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings updated.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'sessions' );
				$code    = 2;
				add_settings_error( 'pose_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'pose-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'pose_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'pose_plugin_options_usecdn' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'pose_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'pose_plugin_options_nag' ) : false );
				Option::network_set( 'analytics', array_key_exists( 'pose_plugin_features_analytics', $_POST ) ? (bool) filter_input( INPUT_POST, 'pose_plugin_features_analytics' ) : false );
				Option::network_set( 'history', array_key_exists( 'pose_plugin_features_history', $_POST ) ? (string) filter_input( INPUT_POST, 'pose_plugin_features_history', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'history' ) );
				Option::network_set( 'rolemode', array_key_exists( 'pose_plugin_features_rolemode', $_POST ) ? (string) filter_input( INPUT_POST, 'pose_plugin_features_rolemode', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'rolemode' ) );
				$message = esc_html__( 'Plugin settings have been saved.', 'sessions' );
				$code    = 0;
				add_settings_error( 'pose_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings updated.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'sessions' );
				$code    = 2;
				add_settings_error( 'pose_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'pose-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'sessions' );
				$code    = 0;
				add_settings_error( 'pose_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings reset to defaults.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'sessions' );
				$code    = 2;
				add_settings_error( 'pose_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		if ( defined( 'DECALOG_VERSION' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site is currently using %s.', 'sessions' ), '<em>DecaLog v' . DECALOG_VERSION . '</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site does not use any logging plugin. To log all events triggered in Sessions, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'sessions' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'pose_plugin_options_logger',
			esc_html__( 'Logging', 'sessions' ),
			[ $form, 'echo_field_simple_text' ],
			'pose_plugin_options_section',
			'pose_plugin_options_section',
			[
				'text' => $help,
			]
		);
		register_setting( 'pose_plugin_options_section', 'pose_plugin_options_logger' );
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site is currently using %s.', 'sessions' ), '<em>Device Detector v' . PODD_VERSION . '</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site does not use any device detection mechanism. To allow device differentiation in Sessions, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'sessions' ), '<a href="https://wordpress.org/plugins/device-detector/">Device Detector</a>' );
		}
		add_settings_field(
			'pose_plugin_options_podd',
			__( 'Device detection', 'sessions' ),
			[ $form, 'echo_field_simple_text' ],
			'pose_plugin_options_section',
			'pose_plugin_options_section',
			[
				'text' => $help,
			]
		);
		register_setting( 'pose_plugin_options_section', 'pose_plugin_options_podd' );
		$geo_ip = new GeoIP();
		if ( $geo_ip->is_installed() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site is currently using %s.', 'sessions' ), '<em>' . $geo_ip->get_full_name() . '</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site does not use any IP geographic information plugin. To allow country differentiation in Sessions, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'sessions' ), '<a href="https://wordpress.org/plugins/geoip-detect/">GeoIP Detection</a>' );
		}
		add_settings_field(
			'pose_plugin_options_geoip',
			__( 'IP information', 'sessions' ),
			[ $form, 'echo_field_simple_text' ],
			'pose_plugin_options_section',
			'pose_plugin_options_section',
			[
				'text' => $help,
			]
		);
		register_setting( 'pose_plugin_options_section', 'pose_plugin_options_geoip' );
		add_settings_field(
			'pose_plugin_options_usecdn',
			esc_html__( 'Resources', 'sessions' ),
			[ $form, 'echo_field_checkbox' ],
			'pose_plugin_options_section',
			'pose_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'sessions' ),
				'id'          => 'pose_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, Sessions will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'sessions' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'pose_plugin_options_section', 'pose_plugin_options_usecdn' );
		add_settings_field(
			'pose_plugin_options_nag',
			esc_html__( 'Admin notices', 'sessions' ),
			[ $form, 'echo_field_checkbox' ],
			'pose_plugin_options_section',
			'pose_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'sessions' ),
				'id'          => 'pose_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows Sessions to display admin notices throughout the admin dashboard.', 'sessions' ) . '<br/>' . esc_html__( 'Note: Sessions respects DISABLE_NAG_NOTICES flag.', 'sessions' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'pose_plugin_options_section', 'pose_plugin_options_nag' );
	}

	/**
	 * Get the available history retentions.
	 *
	 * @return array An array containing the history modes.
	 * @since  1.0.0
	 */
	protected function get_retentions_array() {
		$result = [];
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 30 * $i ), esc_html( sprintf( _n( '%d month', '%d months', $i, 'sessions' ), $i ) ) ];
		}
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 365 * $i ), esc_html( sprintf( _n( '%d year', '%d years', $i, 'sessions' ), $i ) ) ];
		}
		return $result;
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$form   = new Form();
		$mode   = [];
		$mode[] = [ -1, esc_html__( 'Disabled (don\'t limit sessions by roles)', 'sessions' ) ];
		$mode[] = [ 0, esc_html__( 'Enabled - permissive mode (useful when adjusting settings)', 'sessions' ) ];
		$mode[] = [ 1, esc_html__( 'Enabled - strict mode (useful in production, when all settings are ok)', 'sessions' ) ];
		add_settings_field(
			'pose_plugin_features_rolemode',
			esc_html__( 'Settings by roles', 'sessions' ),
			[ $form, 'echo_field_select' ],
			'pose_plugin_features_section',
			'pose_plugin_features_section',
			[
				'list'        => $mode,
				'id'          => 'pose_plugin_features_rolemode',
				'value'       => Option::network_get( 'rolemode' ),
				'description' => esc_html__( 'Operation mode of this feature.', 'sessions' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'pose_plugin_features_section', 'pose_plugin_features_rolemode' );
		add_settings_field(
			'pose_plugin_features_analytics',
			esc_html__( 'Analytics', 'sessions' ),
			[ $form, 'echo_field_checkbox' ],
			'pose_plugin_features_section',
			'pose_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'sessions' ),
				'id'          => 'pose_plugin_features_analytics',
				'checked'     => Option::network_get( 'analytics' ),
				'description' => esc_html__( 'If checked, Sessions will store statistics about accounts and sessions.', 'sessions' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'pose_plugin_features_section', 'pose_plugin_features_analytics' );
		add_settings_field(
			'pose_plugin_features_history',
			esc_html__( 'Historical data', 'sessions' ),
			[ $form, 'echo_field_select' ],
			'pose_plugin_features_section',
			'pose_plugin_features_section',
			[
				'list'        => $this->get_retentions_array(),
				'id'          => 'pose_plugin_features_history',
				'value'       => Option::network_get( 'history' ),
				'description' => esc_html__( 'Maximum age of data to keep for statistics.', 'sessions' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'pose_plugin_features_section', 'pose_plugin_features_history' );
	}

	/**
	 * Get the available history retentions.
	 *
	 * @return array An array containing the history modes.
	 * @since  1.0.0
	 */
	protected function get_session_count_array() {
		$result   = [];
		$result[] = [ 'none', esc_html__( 'No limit', 'sessions' ) ];
		foreach ( LimiterTypes::$selector_names as $key => $name ) {
			for ( $i = 1; $i <= 3; $i++ ) {
				if ( '' === $name ) {
					$result[] = [ $key . '-' . $i, esc_html( sprintf( _n( '%d session per user', '%d sessions per user', $i, 'sessions' ), $i ) ), LimiterTypes::is_selector_available( $key ) ];
				} else {
					// phpcs:ignore
					$result[] = [ $key . '-' . $i, esc_html( sprintf( _n( '%d session per user and per %s', '%d sessions per user and per %s', $i, 'sessions' ), $i, $name ) ), LimiterTypes::is_selector_available( $key ) ];
				}
			}
		}
		return $result;
	}

	/**
	 * Callback for plugin roles modification section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_roles_section_callback() {
		$settings  = Option::roles_get();
		$blocks    = [];
		$blocks[]  = [ 'none', esc_html__( 'Allow from everywhere', 'sessions' ) ];
		$blocks[]  = [ 'external', esc_html__( 'Allow only from private IP ranges', 'sessions' ) ];
		$blocks[]  = [ 'local', esc_html__( 'Allow only from public IP ranges', 'sessions' ) ];
		$methods   = [];
		$methods[] = [ 'override', esc_html__( 'Override oldest session', 'sessions' ) ];
		/* translators: please, do not translate the string [HTTP 403 / Forbidden] as it is a standard HTTP header. */
		$methods[] = [ 'block', esc_html__( 'Block and send a "HTTP 403 / Forbidden" error', 'sessions' ) ];
		$methods[] = [ 'default', esc_html__( 'Block and send a WordPress error', 'sessions' ) ];
		$idle      = [];
		$idle[]    = [ 0, esc_html__( 'Never terminate an idle session', 'sessions' ) ];
		foreach ( [ 1, 2, 3, 4, 5, 6, 12, 24 ]  as $h ) {
			// phpcs:ignore
			$idle[] = [ $h, esc_html( sprintf( _n( 'Terminate a session when idle for more than %d hour', 'Terminate a session when idle for more than %d hours', $h, 'sessions' ), $h ) ) ];
		}
		$ttl = [];
		foreach ( [ 1, 2, 3, 4 ]  as $h ) {
			// phpcs:ignore
			$ttl[] = [ 24 * $h, esc_html( sprintf( _n( '%d day', '%d days', $h, 'sessions' ), $h ) ) ];
		}
		foreach ( [ 1, 2, 3, 4 ]  as $h ) {
			// phpcs:ignore
			$ttl[] = [ 24 * 7 * $h, esc_html( sprintf( _n( '%d week', '%d weeks', $h, 'sessions' ), $h ) ) ];
		}
		$form = new Form();
		foreach ( Role::get_all() as $role => $detail ) {
			add_settings_field(
				'pose_plugin_roles_block_' . $role,
				$detail['l10n_name'],
				[ $form, 'echo_field_select' ],
				'pose_plugin_roles_section',
				'pose_plugin_roles_section',
				[
					'list'        => $blocks,
					'id'          => 'pose_plugin_roles_block_' . $role,
					'value'       => $settings[ $role ]['block'],
					'description' => esc_html__( 'Allowed logins.', 'sessions' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'pose_plugin_roles_section', 'pose_plugin_roles_block_' . $role );
			add_settings_field(
				'pose_plugin_roles_limit_' . $role,
				'',
				[ $form, 'echo_field_select' ],
				'pose_plugin_roles_section',
				'pose_plugin_roles_section',
				[
					'list'        => $this->get_session_count_array(),
					'id'          => 'pose_plugin_roles_limit_' . $role,
					'value'       => $settings[ $role ]['limit'],
					'description' => esc_html__( 'Maximal number of sessions for users.', 'sessions' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'pose_plugin_roles_section', 'pose_plugin_roles_limit_' . $role );
			add_settings_field(
				'pose_plugin_roles_method_' . $role,
				'',
				[ $form, 'echo_field_select' ],
				'pose_plugin_roles_section',
				'pose_plugin_roles_section',
				[
					'list'        => $methods,
					'id'          => 'pose_plugin_roles_method_' . $role,
					'value'       => $settings[ $role ]['method'],
					'description' => esc_html__( 'Method to be used when the maximal number of sessions is reached.', 'sessions' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'pose_plugin_roles_section', 'pose_plugin_roles_method_' . $role );
			add_settings_field(
				'pose_plugin_roles_idle_' . $role,
				'',
				[ $form, 'echo_field_select' ],
				'pose_plugin_roles_section',
				'pose_plugin_roles_section',
				[
					'list'        => $idle,
					'id'          => 'pose_plugin_roles_idle_' . $role,
					'value'       => $settings[ $role ]['idle'],
					'description' => esc_html__( 'Idle sessions supervision.', 'sessions' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'pose_plugin_roles_section', 'pose_plugin_roles_idle_' . $role );
			add_settings_field(
				'pose_plugin_roles_cookie-ttl_' . $role,
				'',
				[ $form, 'echo_field_select' ],
				'pose_plugin_roles_section',
				'pose_plugin_roles_section',
				[
					'list'        => $ttl,
					'id'          => 'pose_plugin_roles_cookie-ttl_' . $role,
					'value'       => $settings[ $role ]['cookie-ttl'],
					'description' => esc_html__( 'Standard cookie duration.', 'sessions' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'pose_plugin_roles_section', 'pose_plugin_roles_cookie-ttl_' . $role );
			add_settings_field(
				'pose_plugin_roles_cookie-rttl_' . $role,
				'',
				[ $form, 'echo_field_select' ],
				'pose_plugin_roles_section',
				'pose_plugin_roles_section',
				[
					'list'        => $ttl,
					'id'          => 'pose_plugin_roles_cookie-rttl_' . $role,
					'value'       => $settings[ $role ]['cookie-rttl'],
					'description' => esc_html__( '"Remember Me" cookie duration.', 'sessions' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'pose_plugin_roles_section', 'pose_plugin_roles_cookie-rttl_' . $role );
		}
	}

}
