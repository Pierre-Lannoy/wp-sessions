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
			if ( array_key_exists( 'analytics', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['analytics'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'analytics-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
					} else {
						$i['text'] = esc_html__( 'This analytics feature is currently disabled. Click here to activate it.', 'sessions' );
						$i['url']  = $item['remedy'];
					}
					$items[] = $i;
				}


				foreach ( self::$menus['analytics'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( [ \Traffic\Plugin\Core::class, 'get_base64_logo' ] );
					$i['title'] = $item['name'];
					$i['id']    = 'analytics-' . $item['slug'];
					if ( !$item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
					} else {
						$i['text'] = esc_html__( 'This analytics feature is currently disabled. Click here to activate it.', 'sessions' );
						$i['url']  = $item['remedy'];
					}
					$items[] = $i;
				}
				foreach ( self::$menus['analytics'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( [ \DecaLog\Plugin\Core::class, 'get_base64_logo' ] );
					$i['title'] = $item['name'];
					$i['id']    = 'analytics-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
					} else {
						$i['text'] = esc_html__( 'This analytics feature is currently disabled. Click here to activate it.', 'sessions' );
						$i['url']  = $item['remedy'];
					}
					$items[] = $i;
				}





				self::display_as_bubble( $items );
			}
		}

		/**
		 * Get the tools main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_tools_page() {


		}

		/**
		 * Get the tools main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_insights_page() {


		}

		/**
		 * Get the tools main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_settings_page() {


		}

		/**
		 * Displays items as bubbles.
		 *
		 * @param array $items  The items to display.
		 * @since 1.0.0
		 */
		private static function display_as_bubble( $items ) {
			uasort(
				$items,
				function ( $a, $b ) {
					if ( $a['title'] === $b['title'] ) {
						return 0;
					} return ( $a['title'] < $b['title'] ) ? -1 : 1;
				}
			);
			$disp  = '';
			$disp .= '<div style="width:100%;text-align:center;padding:0px;margin-top:10px;margin-left:-10px;" class="perfopsone-admin-inside">';
			$disp .= ' <div style="display:flex;flex-direction:row;flex-wrap:wrap;justify-content:center;">';
			$disp .= '  <style>';
			$disp .= '   .perfopsone-admin-inside .po-container {flex:none;padding:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-actionable:hover {border-radius:6px;cursor:pointer; -moz-transition: all .2s ease-in; -o-transition: all .2s ease-in; -webkit-transition: all .2s ease-in; transition: all .2s ease-in; background: #f5f5f5;border:1px solid #e0e0e0;filter: grayscale(0%) opacity(100%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable {overflow:scroll;width:340px;height:120px;border-radius:6px;cursor:pointer; -moz-transition: all .4s ease-in; -o-transition: all .4s ease-in; -webkit-transition: all .4s ease-in; transition: all .4s ease-in; background: transparent;border:1px solid transparent;filter: grayscale(80%) opacity(66%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable a {font-style:normal;text-decoration:none;color:#73879C;}';
			$disp .= '   .perfopsone-admin-inside .po-icon {display:block;width:120px;float:left;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-text {display:grid;text-align:left;padding-top:16px;padding-right:16px;}';
			$disp .= '   .perfopsone-admin-inside .po-title {font-size:1.8em;font-weight: 600;}';
			$disp .= '   .perfopsone-admin-inside .po-description {font-size:1em;padding-top:10px;}';
			$disp .= '  </style>';
			foreach ( $items as $item ) {
				$disp .= '<div class="po-container">';
				$disp .= ' <div class="po-actionable">';
				$disp .= '  <a href="' . $item['url'] . '"/>';
				$disp .= '   <div id="' . $item['id'] . '">';
				$disp .= '    <span class="po-icon"><img style="width:100px" src="' . $item['icon'] . '"/></span>';
				$disp .= '    <span class="po-text">';
				$disp .= '     <span class="po-title">' . $item['title'] . '</span>';
				$disp .= '     <span class="po-description">' . $item['text'] . '</span>';
				$disp .= '    </span>';
				$disp .= '   </div>';
				$disp .= '  </a>';
				$disp .= ' </div>';
				$disp .= '</div>';
			}
			$disp .= ' </div>';
			$disp .= '</div>';
			echo $disp;
		}
	}
}

