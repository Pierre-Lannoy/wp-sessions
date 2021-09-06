<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin;

/**
 * Fired after 'plugins_loaded' hook.
 *
 * This class defines all code necessary to run during the plugin's initialization.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Initializer {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		\POSessions\System\Cache::init();
		\POSessions\System\Sitehealth::init();
		\POSessions\System\APCu::init();
		\POSessions\Plugin\Feature\UserAdministration::init();
		\POSessions\Plugin\Feature\ZooKeeper::init();
		//if ( 'en_US' !== determine_locale() ) {
			unload_textdomain( POSE_SLUG );
			load_plugin_textdomain( POSE_SLUG );
		//}
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function late_initialize() {
		\POSessions\Plugin\Feature\Capture::late_init();
		require_once POSE_PLUGIN_DIR . 'perfopsone/init.php';
	}

}
