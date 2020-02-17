<?php
/**
 * Standard PerfOpsOne menus handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace PerfOpsOne;

/**
 * Standard PerfOpsOne menus handling.
 *
 * This class defines all code necessary to initialize and handle PerfOpsOne admin menus.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

if ( ! class_exists( 'PerfOpsOne\AdminMenus' ) ) {
	class AdminMenus {

		/**
		 * The PerfOpsOne admin menus.
		 *
		 * @since  1.0.0
		 * @var    array    $menus    Maintains the PerfOpsOne admin menus.
		 */
		private static $menus = [];

		/**
		 * Initialize the admin menus.
		 *
		 * @since 1.0.0
		 */
		public static function initialize() {
			self::$menus = apply_filters( 'init_perfops_admin_menus', self::$menus );
			foreach ( self::$menus as $menu => $submenus ) {
				switch ( $menu ) {
					case 'analytics':
						add_menu_page( esc_html__( 'Dashboard', 'sessions' ), sprintf( esc_html__( '%s Analytics', 'sessions' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_analytics_page' ], 'dashicons-chart-bar', 81 );
						add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Dashboard', 'sessions' ), __( 'Dashboard', 'sessions' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_analytics_page' ], 0 );
						break;
					case 'tools':
						add_menu_page( esc_html__( 'Available Tools', 'sessions' ), sprintf( esc_html__( '%s Tools', 'sessions' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_tools_page' ], 'dashicons-admin-tools', 81 );
						add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Available Tools', 'sessions' ), __( 'Available Tools', 'sessions' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_tools_page' ], 0 );
						break;
					case 'insights':
						add_menu_page( esc_html__( 'Available Reports', 'sessions' ), sprintf( esc_html__( '%s Insights', 'sessions' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_insights_page' ], 'dashicons-lightbulb', 81 );
						add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Available Reports', 'sessions' ), __( 'Available Reports', 'sessions' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_insights_page' ], 0 );
						break;
					case 'settings':
						add_menu_page( esc_html__( 'Control Center', 'sessions' ), sprintf( esc_html__( '%s Settings', 'sessions' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_settings_page' ], 'dashicons-admin-settings', 81 );
						add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Control Center', 'sessions' ), __( 'Control Center', 'sessions' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_settings_page' ], 0 );
						break;
				}
				foreach ( $submenus as $submenu ) {
					if ( $submenu['activated'] ) {
						add_submenu_page( 'perfopsone-' . $menu, $submenu['page_title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], $submenu['callback'], $submenu['position'] );
					}
				}
			}
		}

		/**
		 * Get the analytics main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_analytics_page() {


		}

		/**
		 * Get the tools main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_tools_page() {


		}

	}
}

