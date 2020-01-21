<?php
/**
 * Logging handling
 *
 * Handles all logging operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace WPPluginBoilerplate\System;

/**
 * Define the logging functionality.
 *
 * Handles all logging operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Logger {

	/**
	 * The "true" Logger instance.
	 *
	 * @since  1.0.0
	 * @var    \Decalog\Logger    $logger    Maintains the internal Logger instance.
	 */
	private static $logger = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		if ( defined( 'DECALOG_VERSION' ) && class_exists( '\Decalog\Logger' ) ) {
			self::$logger = new \Decalog\Logger( 'plugin', WPPB_PRODUCT_NAME, WPPB_VERSION );
		}
	}

	/**
	 * Logs a panic condition. WordPress is unusable.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function emergency( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->emergency( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs a major operating error that undoubtedly affects the operations.
	 * It requires immediate investigation and corrective treatment.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function alert( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->alert( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs an operating error that undoubtedly affects the operations.
	 * It requires investigation and corrective treatment.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function critical( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->critical( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs a minor operating error that may affects the operations.
	 * It requires investigation and preventive treatment.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function error( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->error( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs a significant condition indicating a situation that may lead to an error if recurring or if no action is taken.
	 * Does not usually affect the operations.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function warning( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->warning( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs a normal but significant condition.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function notice( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->notice( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs a standard information.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function info( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->info( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs an information for developers and testers.
	 * Only used for events related to application/system debugging.
	 *
	 * @param  string $message The message to log.
	 * @param  int    $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function debug( $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->debug( (string) $message, [ 'code' => $code ] );
	}

	/**
	 * Logs an information with an arbitrary level.
	 *
	 * @param  LogLevel $level   The level of the message to log.
	 * @param  string   $message The message to log.
	 * @param  int      $code    Optional. The error code.
	 * @since  1.0.0
	 */
	public static function log( $level, $message, $code = 0 ) {
		if ( ! isset( self::$logger ) ) {
			return;
		}
		self::$logger->log( $level, (string) $message, [ 'code' => $code ] );
	}

}
