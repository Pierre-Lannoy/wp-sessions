<?php
/**
 * Autoload for Sessions.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'POSessions\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'POSessions\System\\' ) === 0 ) {
				$filepath = POSE_INCLUDES_DIR . 'system/';
			}
			if ( strpos( $class, 'POSessions\Plugin\Feature\\' ) === 0 ) {
				$filepath = POSE_INCLUDES_DIR . 'features/';
			} elseif ( strpos( $class, 'POSessions\Plugin\Integration\\' ) === 0 ) {
				$filepath = POSE_INCLUDES_DIR . 'integrations/';
			} elseif ( strpos( $class, 'POSessions\Plugin\\' ) === 0 ) {
				$filepath = POSE_INCLUDES_DIR . 'plugin/';
			} elseif ( strpos( $class, 'POSessions\API\\' ) === 0 ) {
				$filepath = POSE_INCLUDES_DIR . 'api/';
			}
			if ( strpos( $class, 'POSessions\Library\\' ) === 0 ) {
				$filepath = POSE_VENDOR_DIR;
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = POSE_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = POSE_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
