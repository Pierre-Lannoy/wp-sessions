<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Sessions
 * Plugin URI:        https://perfops.one/sessions
 * Description:       Powerful sessions manager for WordPress with sessions limiter and full analytics reporting capabilities.
 * Version:           3.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Network:           true
 * Text Domain:       sessions
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';
require_once __DIR__ . '/includes/features/class-wpcli.php';

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function pose_activate() {
	POSessions\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function pose_deactivate() {
	POSessions\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function pose_uninstall() {
	POSessions\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function pose_run() {
	\DecaLog\Engine::initPlugin( POSE_SLUG, POSE_PRODUCT_NAME, POSE_VERSION, \POSessions\Plugin\Core::get_base64_logo() );
	// It is needed to do these inits here because some plugins make very early die()
	\POSessions\System\Session::init();
	\POSessions\Plugin\Feature\Capture::init();
	\POSessions\Plugin\Feature\Schema::init();
	$plugin = new POSessions\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'pose_activate' );
register_deactivation_hook( __FILE__, 'pose_deactivate' );
register_uninstall_hook( __FILE__, 'pose_uninstall' );
pose_run();
