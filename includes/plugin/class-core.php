<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin;

use POSessions\System\Environment;
use POSessions\System\Loader;
use POSessions\System\I18n;
use POSessions\System\Assets;
use POSessions\Library\Libraries;
use POSessions\System\Cache;
use POSessions\System\Nag;
use POSessions\System\Session;
use POSessions\Plugin\Feature\Analytics;
use POSessions\System\Option;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->loader = new Loader();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_custom_messages();
		if ( \DecaLog\Engine::isDecalogActivated() && Option::network_get( 'metrics' ) && Environment::exec_mode_for_metrics() && Option::network_get( 'analytics' ) ) {
			$this->define_metrics();
		}
	}


	/**
	 * Register all of the hooks related to the features of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_global_hooks() {
		$bootstrap = new Initializer();
		$assets    = new Assets();
		$updater   = new Updater();
		$libraries = new Libraries();
		$this->loader->add_action( 'init', 'POSessions\Plugin\Integration\Databeam', 'init' );
		$this->loader->add_filter( 'perfopsone_plugin_info', self::class, 'perfopsone_plugin_info' );
		$this->loader->add_action( 'init', $bootstrap, 'initialize' );
		$this->loader->add_action( 'init', $bootstrap, 'late_initialize', PHP_INT_MAX );
		$this->loader->add_action( 'wp_head', $assets, 'prefetch' );
		add_shortcode( 'pose-changelog', [ $updater, 'sc_get_changelog' ] );
		add_shortcode( 'pose-libraries', [ $libraries, 'sc_get_list' ] );
		add_shortcode( 'pose-statistics', [ 'POSessions\System\Statistics', 'sc_get_raw' ] );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Sessions_Admin();
		$nag          = new Nag();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'init_admin_menus' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'finalize_admin_menus', 100 );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'normalize_admin_menus', 110 );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings_sections' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( POSE_PLUGIN_DIR . POSE_SLUG . '.php' ), $plugin_admin, 'add_actions_links', 10, 4 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_row_meta', 10, 2 );
		$this->loader->add_action( 'admin_notices', $nag, 'display' );
		$this->loader->add_action( 'wp_ajax_hide_pose_nag', $nag, 'hide_callback' );
		$this->loader->add_action( 'wp_ajax_pose_get_stats', 'POSessions\Plugin\Feature\AnalyticsFactory', 'get_stats_callback' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new Sessions_Public();
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Register all of the filters related to messages customization.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_custom_messages() {
		if ( '' !== Option::network_get( 'blocked_message' ) ) {
			add_filter(
				'sessions_blocked_message',
				function( $message ) {
					return Option::network_get( 'blocked_message' );
				},
				0,
				1
			);
		}
		if ( '' !== Option::network_get( 'bad_ip_message' ) ) {
			add_filter(
				'sessions_bad_ip_message',
				function( $message ) {
					return Option::network_get( 'bad_ip_message' );
				},
				0,
				1
			);
		}
	}

	/**
	 * Register all metrics of the plugin.
	 *
	 * @since  1.2.0
	 * @access private
	 */
	private function define_metrics() {
		$span      = \DecaLog\Engine::tracesLogger( POSE_SLUG )->startSpan( 'Metrics collation', DECALOG_SPAN_PLUGINS_LOAD );
		$cache_id  = 'metrics/lastcheck';
		$analytics = Cache::get_global( $cache_id );
		if ( ! isset( $analytics ) ) {
			$analytics = Analytics::get_status_kpi_collection( [ 'site_id' => 0 ] );
			Cache::set_global( $cache_id, $analytics, 'metrics' );
		}
		if ( isset( $analytics ) ) {
			$metrics = \DecaLog\Engine::metricsLogger( POSE_SLUG );
			if ( array_key_exists( 'data', $analytics ) ) {
				foreach ( $analytics['data'] as $kpi ) {
					$m = $kpi['metrics'] ?? null;
					if ( isset( $m ) ) {
						switch ( $m['type'] ) {
							case 'gauge':
								$metrics->createProdGauge( $m['name'], $m['value'], $m['desc'] );
								break;
							case 'counter':
								$metrics->createProdCounter( $m['name'], $m['desc'] );
								$metrics->incProdCounter( $m['name'], $m['value'] );
								break;
						}
					}
				}
			}
		}
		\DecaLog\Engine::tracesLogger( POSE_SLUG )->endSpan( $span );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Adds full plugin identification.
	 *
	 * @param array $plugin The already set identification information.
	 * @return array The extended identification information.
	 * @since 1.0.0
	 */
	public static function perfopsone_plugin_info( $plugin ) {
		$plugin[ POSE_SLUG ] = [
			'name'    => POSE_PRODUCT_NAME,
			'code'    => POSE_CODENAME,
			'version' => POSE_VERSION,
			'url'     => POSE_PRODUCT_URL,
			'icon'    => self::get_base64_logo(),
		];
		return $plugin;
	}

	/**
	 * Returns a base64 svg resource for the plugin logo.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_logo() {

		$source  = '<svg width="100%" height="100%" viewBox="0 0 1001 1001" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">';
		$source .= '<g id="Sessions" transform="matrix(10.0067,0,0,10.0067,0,0)">';
		$source .= '<rect x="0" y="0" width="100" height="100" style="fill:none;"/>';
		$source .= '<g id="Windows" transform="matrix(2.01699,0,0,2.01699,-0.581152,-27.7051)">';
		$source .= '<g transform="matrix(0,-39.9201,-39.9201,0,25,48.6977)">';
		$source .= '<path d="M0.769,0.391C0.769,0.424 0.742,0.451 0.709,0.451L0.078,0.451C0.044,0.451 0.017,0.424 0.017,0.391L0.017,-0.391C0.017,-0.424 0.044,-0.451 0.078,-0.451L0.709,-0.451C0.742,-0.451 0.769,-0.424 0.769,-0.391L0.769,0.391Z" style="fill:url(#_Linear1);fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '<g transform="matrix(0,-38.5844,-38.5844,0,25,29)">';
		$source .= '<path d="M0.285,0.403C0.285,0.438 0.257,0.467 0.221,0.467L0.13,0.467L0.13,-0.467L0.221,-0.467C0.257,-0.467 0.285,-0.438 0.285,-0.403L0.285,0.403Z" style="fill:url(#_Linear2);fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '<g transform="matrix(0,-1,-1,0,10.3814,19.9044)">';
		$source .= '<path d="M-1.017,-1.018C-1.579,-1.018 -2.036,-0.562 -2.036,0C-2.036,0.562 -1.579,1.017 -1.017,1.017C-0.455,1.017 0.001,0.562 0.001,0C0.001,-0.562 -0.455,-1.018 -1.017,-1.018" style="fill:white;fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '<g transform="matrix(0,-1,-1,0,14.4678,19.9039)">';
		$source .= '<path d="M-1.018,-1.019C-1.58,-1.019 -2.037,-0.563 -2.037,0C-2.037,0.562 -1.58,1.018 -1.018,1.018C-0.456,1.018 0,0.562 0,0C0,-0.563 -0.456,-1.019 -1.018,-1.019" style="fill:white;fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '<g transform="matrix(0,-1,-1,0,18.5542,19.9039)">';
		$source .= '<path d="M-1.018,-1.019C-1.58,-1.019 -2.037,-0.563 -2.037,-0.001C-2.037,0.562 -1.58,1.018 -1.018,1.018C-0.456,1.018 0,0.562 0,-0.001C0,-0.563 -0.456,-1.019 -1.018,-1.019" style="fill:white;fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '<g opacity="0.5">';
		$source .= '<g transform="matrix(1,0,0,1,0,21)">';
		$source .= '<rect x="10" y="5" width="30" height="19" style="fill:white;"/>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<g transform="matrix(1,0,0,1,35.4966,33)">';
		$source .= '<path d="M0,6L-20.993,6C-22.375,6 -23.497,4.879 -23.497,3.497L-23.497,2.503C-23.497,1.121 -22.375,0 -20.993,0L0,0C1.382,0 2.503,1.121 2.503,2.503L2.503,3.497C2.503,4.879 1.382,6 0,6" style="fill:white;fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<g id="Humans" transform="matrix(0.179833,0,0,0.183262,32.0785,32.4315)">';
		$source .= '<path d="M90.258,170.26L91.519,169.08L92.786,170.26C103.868,180.694 116.154,186.447 127.326,186.447C139.062,186.447 151.183,181.343 162.379,171.699L163.316,170.906L165.775,172.104C167.859,174.004 171.597,176.731 173.284,177.553L175.59,178.676L175.338,178.928L176.22,179.456C178.337,180.705 180.595,181.919 183.171,183.21C185.765,184.356 187.999,185.228 190.377,186.008C190.833,186.17 201.888,189.737 214.498,195.585L216.75,196.27C228.724,200.833 233.791,207.265 233.972,207.488C252.563,235.074 255.691,295.326 255.992,302.063C255.854,311.478 253.181,313.928 252.468,314.39C210.806,333.041 147.858,337.887 135.677,338.656L135.344,338.675L134.99,338.584C134.582,338.452 134.158,338.393 133.642,338.393L133.636,338.393L133.207,338.411C129.88,338.645 127.118,338.754 124.548,338.754L122.53,338.754C114.943,338.297 47.071,333.854 3.635,314.53C2.755,314.158 0.179,311.191 0.002,302.551C0.008,301.908 2.245,236.864 21.752,207.885C22.704,206.685 28.018,200.511 38.616,196.452C47.939,193.594 70.991,185.943 83.601,176.816C84.132,176.492 84.661,175.952 85.219,175.387C86.133,174.38 88.051,172.319 90.258,170.26ZM220.082,186.53L218.359,186.032C215.537,184.722 212.558,183.426 209.484,182.171C213.819,180.141 217.374,178.148 220.016,176.226C220.449,175.98 220.869,175.548 221.265,175.133C222.226,174.11 223.498,172.731 224.999,171.329L225.804,170.59L226.585,171.346C234.884,179.137 244.034,183.412 252.381,183.412C261.16,183.412 270.216,179.611 278.55,172.418L279.151,171.925L280.827,172.736C282.364,174.156 285.156,176.176 286.405,176.8L287.846,177.497L287.678,177.677L288.59,178.206C290.176,179.148 291.869,180.055 293.767,180.991C295.712,181.856 297.381,182.516 299.135,183.093C299.477,183.208 307.638,185.831 317.053,190.191L318.716,190.709C327.297,193.98 331.027,198.49 331.393,198.959C345.264,219.531 347.497,264.1 347.707,269.059C347.605,275.947 345.695,277.718 345.216,278.025C319.118,289.704 281.661,294.087 265.899,295.481C264.475,275.028 259.431,226.846 242.57,201.782L241.855,200.924C238.439,196.69 230.957,190.697 220.082,186.53ZM210.318,112.073L210.613,111.722C211.933,110.758 212.582,109.263 212.33,107.72C209.195,88.892 211.226,80.996 211.934,79.017C217.399,62.249 234.596,54.434 237.959,53.05C238.644,52.791 239.952,52.398 241.346,52.164L241.766,52.059L244.36,51.924L244.384,52.104L245.164,52.035C245.753,51.987 246.317,51.9 246.708,51.813L247.578,51.618C248.131,51.636 254.922,52.51 264.764,55.521L271.694,57.908C284.293,61.628 290.129,68.576 291.21,69.984C301.328,81.477 298.62,98.756 296.086,108.046C295.81,109.196 295.978,110.364 296.639,111.318L297.198,112.039C297.912,112.991 298.554,116.783 296.363,124.967C295.955,127.447 295.03,129.438 293.709,130.762C293.169,131.32 292.814,132.047 292.688,132.887C289.265,152.946 271.298,175.374 252.383,175.374C236.303,175.374 217.976,154.735 214.685,132.887C214.553,132.053 214.199,131.326 213.605,130.662C212.272,129.272 211.401,127.243 210.867,124.18C209.267,118.505 209.099,113.917 210.318,112.073ZM70.479,90.453L70.914,89.949C72.607,88.727 73.424,86.799 73.118,84.827C68.872,59.315 71.64,48.702 72.601,46.039C79.978,23.374 103.241,12.787 107.813,10.905C108.768,10.535 110.545,10.001 112.419,9.701L112.923,9.584L116.76,9.368L116.772,9.614L117.598,9.527C118.367,9.455 119.111,9.344 119.991,9.164L120.862,8.957C121.576,8.963 130.553,10.095 144.1,14.238L153.423,17.442C170.446,22.477 178.379,31.904 179.832,33.778C193.523,49.324 189.872,72.713 186.474,85.275C186.083,86.722 186.318,88.235 187.134,89.463L187.891,90.436C189.14,92.108 189.609,97.705 186.823,108.063C186.259,111.447 185.003,114.188 183.16,116.007C182.493,116.728 182.052,117.667 181.863,118.721C177.23,145.842 152.92,176.178 127.321,176.178C105.56,176.178 80.748,148.265 76.271,118.74C76.124,117.689 75.688,116.722 74.914,115.893C73.056,113.965 71.864,111.188 71.137,107.063C68.983,99.295 68.77,93.047 70.479,90.453Z" style="fill:url(#_Linear3);fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<defs>';
		$source .= '<linearGradient id="_Linear1" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(524.215,0,0,-931.414,-30.669,173.855)"><stop offset="0" style="stop-color:rgb(255,147,8);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,216,111);stop-opacity:1"/></linearGradient>';
		$source .= '</defs>';
		$source .= '</svg>';

		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}
