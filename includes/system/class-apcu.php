<?php
/**
 * APCu handling
 *
 * Handles all APCu operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\System;


use POSessions\System\Option;
use POSessions\System\File;

/**
 * Define the APCu functionality.
 *
 * Handles all APCu operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class APCu {

	/**
	 * The list of status.
	 *
	 * @since  1.0.0
	 * @var    array    $status    Maintains the status list.
	 */
	public static $status = [ 'disabled', 'enabled', 'recycle_in_progress' ];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Sets APCu identification hook.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_filter( 'perfopsone_apcu_info', [ self::class, 'perfopsone_apcu_info' ] );
	}

	/**
	 * Adds APCu identification.
	 *
	 * @param array $apcu The already set identifiers.
	 * @return array The extended identifiers if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_apcu_info( $apcu ) {
		$apcu[ POSE_SLUG ] = [
			'name' => POSE_PRODUCT_NAME,
		];
		return $apcu;
	}

	/**
	 * Get name and version.
	 *
	 * @return string The name and version of the product.
	 * @since   1.0.0
	 */
	public static function name() {
		$result = '';
		if ( function_exists( 'apcu_cache_info' ) ) {
			$result = 'APCu';
			$info   = apcu_cache_info( false );
			if ( array_key_exists( 'memory_type', $info ) ) {
				$result .= ' (' . $info['memory_type'] . ')';
			}
			$result .= ' ' . phpversion( 'apcu' );
		}
		return $result;
	}

	/**
	 * Delete cached objects.
	 *
	 * @param   array $objects List of objects to delete.
	 * @return integer The number of deleted objects.
	 * @since   1.0.0
	 */
	public static function delete( $objects ) {
		$cpt = 0;
		if ( function_exists( 'apcu_delete' ) ) {
			$prefix = md5( ABSPATH ) . '_';
			foreach ( $objects as $object ) {
				if ( false !== apcu_delete( $prefix . $object ) ) {
					$cpt++;
				}
			}
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( sprintf( '%d object(s) deleted.', $cpt ) );
		}
		return $cpt;
	}

	/**
	 * Clear the cache.
	 *
	 * @since   1.0.0
	 */
	public static function reset() {
		if ( function_exists( 'apcu_cache_info' ) && function_exists( 'apcu_delete' ) ) {
			$prefix = md5( ABSPATH ) . '_';
			$infos = apcu_cache_info( false );
			if ( array_key_exists( 'cache_list', $infos ) && is_array( $infos['cache_list'] ) ) {
				foreach ( $infos['cache_list'] as $script ) {
					if ( 0 === strpos( $script['info'], $prefix ) ) {
						apcu_delete( $script['info'] );
						$result++;
					}
				}
			}
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->notice( 'Cache cleared.' );
		}
	}

}
