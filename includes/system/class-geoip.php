<?php
/**
 * GeoIP handling
 *
 * Handles all GeoIP operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\System;

use POSessions\System\EmojiFlag;

/**
 * Define the GeoIP functionality.
 *
 * Handles all GeoIP operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class GeoIP {

	/**
	 * Is IPv4 supported.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ipv4    Is IPv4 supported.
	 */
	private $ipv4 = false;

	/**
	 * Is IPv6 supported.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ipv6    Is IPv6 supported.
	 */
	private $ipv6 = false;

	/**
	 * The version of the provider.
	 *
	 * @since  1.0.0
	 * @var    string    $provider_version    The version of the provider.
	 */
	private $provider_version = '';

	/**
	 * The name of the provider.
	 *
	 * @since  1.0.0
	 * @var    string    $provider_name    The name of the provider.
	 */
	private $provider_name = '';

	/**
	 * The id of the provider.
	 *
	 * @since  1.0.0
	 * @var    string    $provider_id    The id of the provider.
	 */
	private $provider_id = '';

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->detect();
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	private function detect() {
		if ( defined( 'GEOIP_DETECT_VERSION' ) && function_exists( 'geoip_detect2_get_info_from_ip' ) ) {
			$this->provider_id      = 'geoip-detect';
			$this->provider_name    = 'Geolocation IP Detection';
			$this->provider_version = GEOIP_DETECT_VERSION;
			$this->ipv4             = true;
			if ( defined( 'GEOIP_DETECT_IPV6_SUPPORTED' ) ) {
				$this->ipv6 = GEOIP_DETECT_IPV6_SUPPORTED;
			}
		}
		if ( defined( 'IPLOCATOR_VERSION' ) && class_exists( '\IPLocator\API\Country' ) ) {
			$this->provider_id      = 'ip-locator';
			$this->provider_name    = 'IP Locator';
			$this->provider_version = IPLOCATOR_VERSION;
			$this->ipv4             = true;
			$this->ipv6             = true;
		}
	}

	/**
	 * Verify if geoip is installed..
	 *
	 * @return  boolean True if geoip is installed, false otherwise.
	 * @since 1.0.0
	 */
	public function is_installed() {
		return '' !== $this->provider_id;
	}

	/**
	 * Get the installed plugin name.
	 *
	 * @return  string The installed geoip plugin name.
	 * @since 1.0.0
	 */
	public function get_full_name() {
		if ( ! $this->is_installed() ) {
			return '';
		}
		return $this->provider_name . ' v' . $this->provider_version;
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param   string $host The host name. May be an IP or an url.
	 * @return  null|string The ISO 3166-1 / Alpha 2 country code if detected, null otherwise.
	 * @since 1.0.0
	 */
	public function get_iso3166_alpha2( $host ) {
		if ( '' === $this->provider_id ) {
			return null;
		}
		$ip      = '';
		$country = null;
		if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$url_parts = wp_parse_url( 'http://' . $host );
			$host      = $url_parts['host'];
		}
		if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 ) ) {
			$host = gethostbyname( $host );
		}
		if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$ip = $host;
		}
		if ( '' === $ip && $this->ipv6 && filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$ip = $host;
		}
		// IP Locator.
		if ( '' !== $ip && 'ip-locator' === $this->provider_id ) {
			$country = iplocator_get_country_name( $ip );
			if ( empty( $country ) || 2 !== strlen( $country ) ) {
				$country = null;
			}
		}
		// GeoIP Detect.
		elseif ( '' !== $ip && 'geoip-detect' === $this->provider_id ) {
			$info    = geoip_detect2_get_info_from_ip( $ip );
			$country = strtoupper( $info->country->isoCode );
			if ( empty( $country ) || 2 !== strlen( $country ) ) {
				$country = strtoupper( $info->registeredCountry->isoCode );
			}
			if ( empty( $country ) || 2 !== strlen( $country ) ) {
				$country = strtoupper( $info->representedCountry->isoCode );
			}
			if ( empty( $country ) || 2 !== strlen( $country ) ) {
				$country = null;
			}
		}
		return $country;
	}

	/**
	 * Get the image flag.
	 *
	 * @param   string    $ip         Optional. The ip to detect from.
	 *                                If not specified, get the ip of the current request.
	 * @param   string    $class      Optional. The class(es) name(s).
	 * @param   string    $style      Optional. The style.
	 * @param   string    $id         Optional. The ID.
	 * @param   string    $alt        Optional. The alt text.
	 * @param   boolean   $squared    Optional. The flag must be squared.
	 * @return  string                The svg flag base 64 encoded.
	 * @since 1.0.0
	 */
	public function get_flag( $ip = null, $class = '', $style = '', $id = '', $alt = '', $squared = false ) {
		// IP Locator.
		if ( '' !== $ip && 'ip-locator' === $this->provider_id ) {
			return iplocator_get_flag_image( $ip, $class, $style, $id, $alt, $squared );
		}
		// GeoIP Detect.
		elseif ( '' !== $ip && 'geoip-detect' === $this->provider_id ) {
			return EmojiFlag::get( $this->get_iso3166_alpha2( $ip ) ) . '&nbsp;';
		}
		return '';
	}

}
