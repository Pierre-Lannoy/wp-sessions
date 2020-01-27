<?php
/**
 * Analytics factory
 *
 * Handles all analytics creation and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Feature;

use POSessions\Plugin\Feature\Analytics;
use POSessions\System\Blog;
use POSessions\System\Date;
use POSessions\System\Logger;
use POSessions\System\Timezone;

/**
 * Define the analytics factory functionality.
 *
 * Handles all analytics creation and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class AnalyticsFactory {

	/**
	 * Allowed types.
	 *
	 * @since  1.0.0
	 * @var    array    $allowed_types    Maintain the allowed types.
	 */
	private static $allowed_types = [ 'browser', 'browsers', 'bot', 'bots', 'device', 'devices', 'os', 'oses', 'classes', 'types', 'clients', 'libraries', 'applications', 'feeds', 'medias' ];

	/**
	 * Ajax callback.
	 *
	 * @since    1.0.0
	 */
	public static function get_stats_callback() {
		check_ajax_referer( 'ajax_pose', 'nonce' );
		$analytics = self::get_analytics( true );
		$query     = filter_input( INPUT_POST, 'query' );
		$queried   = filter_input( INPUT_POST, 'queried' );
		exit( wp_json_encode( $analytics->query( $query, $queried ) ) );
	}

	/**
	 * Get the content of the viewer page.
	 *
	 * @param   boolean $reload  Optional. Is it a reload of an already displayed analytics.
	 * @since 1.0.0
	 */
	public static function get_analytics( $reload = false ) {
		$timezone = Timezone::network_get();
		// Analytics type.
		if ( ! ( $type = filter_input( INPUT_GET, 'type' ) ) ) {
			$type = filter_input( INPUT_POST, 'type' );
		}
		if ( empty( $type ) || ! in_array( $type, self::$allowed_types ) ) {
			$type = 'summary';
		}
		// Filters.
		if ( ! ( $id = filter_input( INPUT_GET, 'id' ) ) ) {
			$id = filter_input( INPUT_POST, 'id' );
		}
		if ( empty( $id ) ) {
			$id = '';
		}
		if ( ! ( $extended = filter_input( INPUT_GET, 'extended' ) ) ) {
			$extended = filter_input( INPUT_POST, 'extended' );
		}
		if ( empty( $extended ) ) {
			$extended = '-';
		}
		$extended = str_replace( '\'\'', '"', rawurldecode( $extended ) );
		if ( ! ( $site = filter_input( INPUT_GET, 'site' ) ) ) {
			$site = filter_input( INPUT_POST, 'site' );
		}
		if ( empty( $site ) || ! Blog::is_blog_exists( (int) $site ) ) {
			$site = 'all';
		}
		if ( ! ( $start = filter_input( INPUT_GET, 'start' ) ) ) {
			$start = filter_input( INPUT_POST, 'start' );
		}
		if ( empty( $start ) || ! Date::is_date_exists( $start, 'Y-m-d' ) ) {
			$sdatetime = new \DateTime( 'now', $timezone );
			$start     = $sdatetime->format( 'Y-m-d' );
		} else {
			$sdatetime = new \DateTime( $start, $timezone );
		}
		if ( ! ( $end = filter_input( INPUT_GET, 'end' ) ) ) {
			$end = filter_input( INPUT_POST, 'end' );
		}
		if ( empty( $end ) || ! Date::is_date_exists( $end, 'Y-m-d' ) ) {
			$edatetime = new \DateTime( 'now', $timezone );
			$end       = $edatetime->format( 'Y-m-d' );
		} else {
			$edatetime = new \DateTime( $end, $timezone );
		}
		if ( $edatetime->getTimestamp() < $sdatetime->getTimestamp() ) {
			$start = $edatetime->format( 'Y-m-d' );
			$end   = $sdatetime->format( 'Y-m-d' );
		}
		return new Analytics( $type, $id, $site, $start, $end, $extended, $reload );
	}

}
