<?php
/**
 * DataBeam integration
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Integration;

use POSessions\System\Option;
use POSessions\System\Role;
use POSessions\Plugin\Core;

/**
 * Define the DataBeam integration.
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.2.0
 */
class Databeam {

	/**
	 * Init the class.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_filter( 'databeam_source_register', [ static::class, 'register_kpi' ] );
	}

	/**
	 * Register Sessions kpis endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_kpi( $integrations ) {
		$integrations[ POSE_SLUG . '::kpi' ] = [
			'name'         => POSE_PRODUCT_NAME,
			'version'      => POSE_VERSION,
			'subname'      => __( 'KPIs', 'sessions' ),
			'description'  => __( 'Allows to integrate, as a DataBeam source, all KPIs related to users\' sessions.', 'sessions' ),
			'instruction'  => __( 'Just add this and use it as source in your favorite visualizers and publishers.', 'sessions' ),
			'note'         => __( 'In multisite environments, this source is available for all network sites.', 'sessions' ),
			'legal'        =>
				[
					'author'  => 'Pierre Lannoy',
					'url'     => 'https://github.com/Pierre-Lannoy',
					'license' => 'gpl3',
				],
			'icon'         =>
				[
					'static' => [
						'class'  => '\POSessions\Plugin\Core',
						'method' => 'get_base64_logo',
					],
				],
			'type'         => 'collection::kpi',
			'restrictions' => [ 'only_network' ],
			'ttl'          => '0-3600:300',
			'caching'      => [ 'locale' ],
			'data_call'    =>
				[
					'static' => [
						'class'  => '\POSessions\Plugin\Feature\Analytics',
						'method' => 'get_status_kpi_collection',
					],
				],
			'data_args'    => [],
		];
		return $integrations;
	}

	/**
	 * Returns a base64 svg resource for the banner.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_banner() {
		$filename = __DIR__ . '/banner.svg';
		if ( file_exists( $filename ) ) {
			// phpcs:ignore
			$content = @file_get_contents( $filename );
		} else {
			$content = '';
		}
		if ( $content ) {
			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $content );
		}
		return '';
	}

	/**
	 * Register server infos endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_info( $integrations ) {
		return $integrations;
	}

}
