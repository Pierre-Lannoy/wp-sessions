<?php
/**
 * DecaLog SDK main engine.
 *
 * @package SDK
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace DecaLog;

/**
 * DecaLog engine class.
 *
 * This class defines all code necessary to manage DecaLog operations.
 *
 * @package SDK
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Engine {

	/**
	 * The engine version.
	 *
	 * @since  1.0.0
	 * @var    string    $version    Maintains the engine version.
	 */
	private static $version = '2.0.0';

	/**
	 * The logger instances and parameters.
	 *
	 * @since  1.0.0
	 * @var    array    $loggers    Maintains the loggers list.
	 */
	private static $loggers = [];

	/**
	 * Registers a new logger.
	 *
	 * @param string $class   The class identifier, must be a value in ['plugin', 'theme'].
	 * @param string $slug    The slug identifier.
	 * @param string $name    The name of the component that will trigger events.
	 * @param string $version The version of the component that will trigger events.
	 * @param string $icon    Optional. The base64-encoded image for the plugin logo. Preferably an SVG image.
	 * @since 1.0.0
	 */
	private static function init( $class, $slug, $name, $version, $icon = '' ) {
		if ( is_string( $slug ) && '' !== $slug ) {
			static::$loggers[ $slug ] = [
				'logging'    => null,
				'monitoring' => null,
				'tracing'    => null,
				'class'      => $class,
				'name'       => (string) $name,
				'version'    => (string) $version,
				'icon'       => (string) $icon,
			];
		} else {
			throw new \DecaLog\Exception\InvalidSlugException( 'The slug is not a valid, non-empty string.' );
		}
	}

	/**
	 * Verify if DecaLog is activated.
	 *
	 * @return  boolean  True if DecaLog is activated, false otherwise.
	 * @since 1.0.0
	 */
	public static function isDecalogActivated() {
		return class_exists( '\Decalog\Plugin\Feature\DLogger' );
	}

	/**
	 * Get the version of DecaLog SDK.
	 *
	 * @return  string  The (SemVer) SDK version.
	 * @since 1.0.0
	 */
	public static function getSdkVersion() {
		return self::$version;
	}

	/**
	 * Get the version of DecaLog.
	 *
	 * @return  string  The (SemVer) DecaLog version.
	 * @since 1.0.0
	 */
	public static function getDecalogVersion() {
		if ( defined( 'DECALOG_VERSION' ) ) {
			return DECALOG_VERSION;
		}
		return '';
	}

	/**
	 * Get the full version string.
	 *
	 * @return  string  The full version string.
	 * @since 1.0.0
	 */
	public static function getVersionString() {
		if ( self::isDecalogActivated() ) {
			if ( ! defined( 'DECALOG_PRODUCT_NAME' ) ) {
				define( 'DECALOG_PRODUCT_NAME', 'DecaLog' );
			}
			return DECALOG_PRODUCT_NAME . ' ' . self::getDecalogVersion() . ' (SDK ' . self::getSdkVersion() . ')';
		}
		return '';
	}

	/**
	 * Get the loggers list.
	 *
	 * @return  array  The loggers list.
	 * @since 1.0.0
	 */
	public static function getLoggers() {
		$result = [];
		foreach ( static::$loggers as $slug => $logger ) {
			$result[ $slug ]['name']    = $logger['name'];
			$result[ $slug ]['version'] = $logger['version'];
			$result[ $slug ]['icon']    = $logger['icon'];
		}
		return $result;
	}

	/**
	 * Registers a new theme logger.
	 *
	 * @param string $slug    The slug identifier.
	 * @param string $name    The name of the theme that will trigger events.
	 * @param string $version The version of the theme that will trigger events.
	 * @param string $icon    Optional. The base64-encoded image for the plugin logo. Preferably an SVG image.
	 * @since 1.0.0
	 */
	public static function initTheme( $slug, $name, $version, $icon = '' ) {
		static::init( 'theme', $slug, $name, $version, $icon );
	}

	/**
	 * Registers a new plugin logger.
	 *
	 * @param string $slug    The slug identifier.
	 * @param string $name    The name of the plugin that will trigger events.
	 * @param string $version The version of the plugin that will trigger events.
	 * @param string $icon    Optional. The base64-encoded image for the plugin logo. Preferably an SVG image.
	 * @since 1.0.0
	 */
	public static function initPlugin( $slug, $name, $version, $icon = '' ) {
		static::init( 'plugin', $slug, $name, $version, $icon );
	}

	/**
	 * Registers a new logger.
	 *
	 * @param string $slug    The slug identifier.
	 * @return  string  The string index if logger is registered, empty string otherwise.
	 * @since 1.0.0
	 */
	private static function getLoggerSlug( $slug ) {
		if ( is_string( $slug ) && '' !== $slug && array_key_exists( $slug, static::$loggers ) ) {
			return $slug;
		}
		return '';
	}

	/**
	 * Get a registered events logger.
	 *
	 * @param string $slug    The slug identifier.
	 * @return  \DecaLog\EventsLogger    The corresponding events logger.
	 * @throws \DecaLog\Exception\InvalidSlugException
	 * @since 1.0.0
	 */
	public static function eventsLogger( $slug ) {
		$slug = static::getLoggerSlug( $slug );
		if ( '' === $slug ) {
			throw new \DecaLog\Exception\InvalidSlugException( 'No registered logger with this slug.' );
		}
		if ( ! static::$loggers[ $slug ]['logging'] ) {
			static::$loggers[ $slug ]['logging'] = new \DecaLog\EventsLogger( static::$loggers[ $slug ]['class'], static::$loggers[ $slug ]['name'], static::$loggers[ $slug ]['version'] );
		}
		return static::$loggers[ $slug ]['logging'];
	}

	/**
	 * Get a registered metrics logger.
	 *
	 * @param string $slug    The slug identifier.
	 * @return  \DecaLog\MetricsLogger    The corresponding metrics logger.
	 * @throws \DecaLog\Exception\InvalidSlugException
	 * @since 1.0.0
	 */
	public static function metricsLogger( $slug ) {
		$slug = static::getLoggerSlug( $slug );
		if ( '' === $slug ) {
			throw new \DecaLog\Exception\InvalidSlugException( 'No registered logger with this slug.' );
		}
		if ( ! static::$loggers[ $slug ]['monitoring'] ) {
			static::$loggers[ $slug ]['monitoring'] = new \DecaLog\MetricsLogger( static::$loggers[ $slug ]['class'], static::$loggers[ $slug ]['name'], static::$loggers[ $slug ]['version'] );
		}
		return static::$loggers[ $slug ]['monitoring'];
	}

	/**
	 * Get a registered traces logger.
	 *
	 * @param string $slug    The slug identifier.
	 * @return  \DecaLog\TracesLogger    The corresponding traces logger.
	 * @throws \DecaLog\Exception\InvalidSlugException
	 * @since 1.0.0
	 */
	public static function tracesLogger( $slug ) {
		$slug = static::getLoggerSlug( $slug );
		if ( '' === $slug ) {
			throw new \DecaLog\Exception\InvalidSlugException( 'No registered logger with this slug.' );
		}
		if ( ! static::$loggers[ $slug ]['tracing'] ) {
			static::$loggers[ $slug ]['tracing'] = new \DecaLog\TracesLogger( static::$loggers[ $slug ]['class'], static::$loggers[ $slug ]['name'], static::$loggers[ $slug ]['version'] );
		}
		return static::$loggers[ $slug ]['tracing'];
	}

	/**
	 * Generates a v4 UUID.
	 *
	 * @since  1.2.0
	 * @return string      A v4 UUID.
	 */
	private static function generate_v4() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// phpcs:disable
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		// phpcs:enabled
		);
	}

	/**
	 * Generates a (pseudo) unique ID.
	 * This function does not generate cryptographically secure values, and should not be used for cryptographic purposes.
	 *
	 * @param   integer $length     The length of the ID.
	 * @return  string  The unique ID.
	 * @since  1.2.0
	 */
	public static function generate_unique_id( $length = 8 ) {
		$result = '';
		do {
			$s       = self::generate_v4();
			$s       = str_replace( '-', date( 'his' ), $s );
			$result .= $s;
			$l       = strlen( $result );
		} while ( $l < $length );
		return substr( str_shuffle( $result ), 0, $length );
	}

}

if ( ! defined( 'DECALOG_MAX_SHUTDOWN_PRIORITY' ) ) {
	define( 'DECALOG_MAX_SHUTDOWN_PRIORITY', PHP_INT_MAX - 1000 );
}
if ( ! defined( 'DECALOG_SPAN_MUPLUGINS_LOAD' ) ) {
	define( 'DECALOG_SPAN_MUPLUGINS_LOAD', \DecaLog\Engine::generate_unique_id() );
}
if ( ! defined( 'DECALOG_SPAN_PLUGINS_LOAD' ) ) {
	define( 'DECALOG_SPAN_PLUGINS_LOAD', \DecaLog\Engine::generate_unique_id() );
}
if ( ! defined( 'DECALOG_SPAN_THEME_SETUP' ) ) {
	define( 'DECALOG_SPAN_THEME_SETUP', \DecaLog\Engine::generate_unique_id() );
}
if ( ! defined( 'DECALOG_SPAN_USER_AUTHENTICATION' ) ) {
	define( 'DECALOG_SPAN_USER_AUTHENTICATION', \DecaLog\Engine::generate_unique_id() );
}
if ( ! defined( 'DECALOG_SPAN_PLUGINS_INITIALIZATION' ) ) {
	define( 'DECALOG_SPAN_PLUGINS_INITIALIZATION', \DecaLog\Engine::generate_unique_id() );
}
if ( ! defined( 'DECALOG_SPAN_MAIN_RUN' ) ) {
	define( 'DECALOG_SPAN_MAIN_RUN', \DecaLog\Engine::generate_unique_id() );
}
if ( ! defined( 'DECALOG_SPAN_SHUTDOWN' ) ) {
	define( 'DECALOG_SPAN_SHUTDOWN', \DecaLog\Engine::generate_unique_id() );
}
