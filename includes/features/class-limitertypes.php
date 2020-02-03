<?php
/**
 * Limiter types handling
 *
 * Handles all available limiter types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Feature;

use POSessions\System\GeoIP;

/**
 * Define the limiter types functionality.
 *
 * Handles all available limiter types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class LimiterTypes {

	/**
	 * The list of available selectors.
	 *
	 * @since  1.0.0
	 * @var    array    $channels    Maintains the selectors definitions.
	 */
	public static $selectors = [ 'user', 'country', 'ip', 'device-class', 'device-type', 'device-client', 'device-browser', 'device-os' ];

	/**
	 * The list of available selector names.
	 *
	 * @since  1.0.0
	 * @var    array    $channel_names    Maintains the selector names.
	 */
	public static $selector_names = [];

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$selector_names['user']           = '';
		self::$selector_names['country']        = esc_html__( 'country', 'sessions' );
		self::$selector_names['ip']             = esc_html__( 'IP address', 'sessions' );
		self::$selector_names['device-class']   = esc_html__( 'device class', 'sessions' );
		self::$selector_names['device-type']    = esc_html__( 'device type', 'sessions' );
		self::$selector_names['device-client']  = esc_html__( 'client type', 'sessions' );
		self::$selector_names['device-browser'] = esc_html__( 'browser', 'sessions' );
		self::$selector_names['device-os']      = esc_html__( 'operating system', 'sessions' );
	}

	/**
	 * Verify if a limiter selector is available.
	 *
	 * @param   string  $selector   The selector ID.
	 * @return  boolean True if the selector is available, false otherwise.
	 * @since    1.0.0
	 */
	public static function is_selector_available( $selector ) {
		switch ( $selector ) {
			case 'none':
			case 'user':
			case 'ip':
				$result = true;
				break;
			case 'country':
				$geo_ip = new GeoIP();
				$result = $geo_ip->is_installed();
				break;
			case 'device-class':
			case 'device-type':
			case 'device-client':
			case 'device-browser':
			case 'device-os':
				$result = class_exists( 'PODeviceDetector\API\Device' );
				break;
			default:
				$result = false;
		}
		return $result;
	}

}

LimiterTypes::init();