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
	 * @since 1.0.0
	 */
	public static function initTheme( $slug, $name, $version ) {
		static::init( 'theme', $slug, $name, $version );
	}

	/**
	 * Registers a new plugin logger.
	 *
	 * @param string $slug    The slug identifier.
	 * @param string $name    The name of the plugin that will trigger events.
	 * @param string $version The version of the plugin that will trigger events.
	 * @since 1.0.0
	 */
	public static function initPlugin( $slug, $name, $version ) {
		static::init( 'plugin', $slug, $name, $version );
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

}
