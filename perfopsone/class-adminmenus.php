<?php
/**
 * Standard PerfOpsOne menus handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace PerfOpsOne;

use POSessions\System\Plugin;
use POSessions\System\Conversion;

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
					case 'records':
						add_menu_page( esc_html__( 'Available Catalogues', 'sessions' ), sprintf( esc_html__( '%s Records', 'sessions' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_records_page' ], 'dashicons-book', 81 );
						add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Available Catalogues', 'sessions' ), __( 'Available Catalogues', 'sessions' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_records_page' ], 0 );
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
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the tools main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_tools_page() {
			if ( array_key_exists( 'tools', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['tools'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'tools-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]   = $i;
					}
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the insights main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_insights_page() {
			if ( array_key_exists( 'insights', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['insights'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'insights-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]   = $i;
					}
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the records main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_records_page() {
			if ( array_key_exists( 'records', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['records'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'records-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]   = $i;
					}
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the settings main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_settings_page() {
			if ( array_key_exists( 'settings', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['settings'] as $item ) {
					$i                = [];
					$d                = new Plugin( $item['plugin'] );
					$i['title']       = $d->get( 'Name' );
					$i['version']     = $d->get( 'Version' );
					$i['text']        = $d->get( 'Description' );
					$i['wp_version']  = $d->get( 'RequiresWP' );
					$i['php_version'] = $d->get( 'RequiresPHP' );
					if ( $d->waiting_update() ) {
						$i['need_update'] = sprintf( esc_html__( 'Need to be updated to %s.', 'sessions' ), $d->waiting_update() );
					} else {
						$i['need_update'] = '';
					}
					if ( $d->is_required_wp_ok() ) {
						$i['need_wp_update'] = '';
						$i['ok_wp_update']   = esc_html__( 'OK', 'sessions' );
					} else {
						$i['need_wp_update'] = esc_html__( 'need update', 'sessions' );
						$i['ok_wp_update']   = '';
					}
					if ( $d->is_required_php_ok() ) {
						$i['need_php_update'] = '';
						$i['ok_php_update']   = esc_html__( 'OK', 'sessions' );
					} else {
						$i['need_php_update'] = esc_html__( 'need update', 'sessions' );
						$i['ok_php_update']   = '';
					}
					$i['icon'] = call_user_func( $item['icon_callback'] );
					$i['id']   = 'settings-' . $item['slug'];
					foreach ( [ 'installs', 'downloads', 'rating', 'reviews' ] as $key ) {
						$i[ $key ] = call_user_func( $item['statistics'], [ 'item' => $key ] );
					}
					if ( 0 < (int) $i['installs'] ) {
						$i['installs'] = sprintf( esc_html__( '%d+ installs', 'sessions' ), Conversion::number_shorten( (float) $i['installs'], 0 ) );
					} else {
						$i['installs'] = '';
					}
					if ( 0 < (int) $i['downloads'] ) {
						$i['downloads'] = sprintf( esc_html__( '%d+ downloads', 'sessions' ), Conversion::number_shorten( (float) $i['downloads'], 0 ) );
					} else {
						$i['downloads'] = '';
					}
					if ( 0 < (int) $i['reviews'] ) {
						$i['reviews'] = sprintf( esc_html__( '%d reviews', 'sessions' ), Conversion::number_shorten( (float) $i['reviews'], 0 ) );
						$i['rating']  = (int) $i['rating'];
					} else {
						$i['reviews'] = esc_html__( 'no review yet', 'sessions' );
						$i['rating']  = 0;
					}
					if ( $item['activated'] && $d->is_detected() ) {
						$i['url'] = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]  = $i;
					}
				}
				self::display_as_lines( $items );
			}
		}

		/**
		 * Displays items as bubbles.
		 *
		 * @param array $items  The items to display.
		 * @since 1.0.0
		 */
		private static function display_as_bubbles( $items ) {
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

		/**
		 * Displays items as lines.
		 *
		 * @param array $items  The items to display.
		 * @since 1.0.0
		 */
		private static function display_as_lines( $items ) {
			uasort(
				$items,
				function ( $a, $b ) {
					if ( $a['title'] === $b['title'] ) {
						return 0;
					} return ( $a['title'] < $b['title'] ) ? -1 : 1;
				}
			);
			$disp  = '';
			$disp .= '<div style="width:100%;text-align:center;padding:0px;margin-top:0;" class="perfopsone-admin-inside">';
			$disp .= ' <div style="display:flex;flex-direction:row;flex-wrap:wrap;justify-content:center;padding-top:10px;padding-right:20px;">';
			$disp .= '  <style>';
			$disp .= '   .perfopsone-admin-inside .po-container {width:100%;flex:none;padding:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-actionable:hover {border-radius:6px;cursor:pointer; -moz-transition: all .2s ease-in; -o-transition: all .2s ease-in; -webkit-transition: all .2s ease-in; transition: all .2s ease-in; background: #f5f5f5;border:1px solid #e0e0e0;filter: grayscale(0%) opacity(100%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable {overflow:scroll;width:100%;height:120px;border-radius:6px;cursor:pointer; -moz-transition: all .4s ease-in; -o-transition: all .4s ease-in; -webkit-transition: all .4s ease-in; transition: all .4s ease-in; background: transparent;border:1px solid transparent;filter: grayscale(80%) opacity(66%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable a {font-style:normal;text-decoration:none;color:#73879C;}';
			$disp .= '   .perfopsone-admin-inside .po-icon {display:block;width:120px;float:left;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-text {display: grid;text-align:left;padding-top:20px;padding-right:16px;}';
			$disp .= '   .perfopsone-admin-inside .po-title {font-size:1.8em;font-weight: 600;}';
			$disp .= '   .perfopsone-admin-inside .po-version {font-size:0.6em;font-weight: 500;padding-left: 10px;vertical-align: middle;}';
			$disp .= '   .perfopsone-admin-inside .po-update {font-size:1.2em;font-weight: 400;color:#9B59B6;}';
			$disp .= '   .perfopsone-admin-inside .po-description {font-size:1em;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-requires {font-size:1em;}';
			$disp .= '   .perfopsone-admin-inside .po-needupdate {vertical-align:super;font-size:0.6em;color:#9B59B6;padding-left:2px;}';
			$disp .= '   .perfopsone-admin-inside .po-okupdate {vertical-align:super;font-size:0.6em;color:#3398DB;}';
			$disp .= '  </style>';
			foreach ( $items as $item ) {
				$disp .= '<div class="po-container">';
				$disp .= ' <div class="po-actionable">';
				$disp .= '  <a href="' . $item['url'] . '"/>';
				$disp .= '   <div id="' . $item['id'] . '">';
				$disp .= '    <div class="po-icon"><img style="width:100px" src="' . $item['icon'] . '"/></div>';
				$disp .= '    <div class="po-text">';
				$disp .= '     <span class="po-title">' . $item['title'] . '<span class="po-version">' . $item['version'] . '</span></span>';
				$disp .= '     <span class="po-update">' . $item['need_update'] . '</span>';
				$disp .= '     <span class="po-description">' . $item['text'] . '</span>';
				$disp .= '     <span class="po-requires">' . sprintf ( esc_html__( 'Requires at least PHP %1$s%2$s and WordPress %3$s%4$s.', 'sessions' ), $item['php_version'], '<span class="po-needupdate">' . $item['need_php_update'] . '</span><span class="po-okupdate">' . $item['ok_php_update'] . '</span>', $item['wp_version'], '<span class="po-needupdate">' . $item['need_wp_update'] . '</span><span class="po-okupdate">' . $item['ok_wp_update'] . '</span>'  ) . '</span>';
				$disp .= '    </div>';
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

