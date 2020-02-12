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
		\POSessions\System\Logger::init();
		\POSessions\System\Cache::init();
		\POSessions\System\Sitehealth::init();
		\POSessions\Plugin\Feature\Schema::init();
		\POSessions\System\APCu::init();
		\POSessions\System\Session::init();
		//\POSessions\Plugin\Feature\Administration::init();
		\POSessions\Plugin\Feature\Capture::init();
		\POSessions\Plugin\Feature\ZooKeeper::init();
	}

}
