<?php
/**
 * DecaLog tracer definition.
 *
 * @package SDK
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace DecaLog;

/**
 * DecaLog tracer class.
 *
 * This class defines all code necessary to trace with DecaLog.
 * If DecaLog is not installed, it will do nothing and will not throw errors.
 *
 * @package SDK
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class TracesLogger {

	/**
	 * The "true" DTracer instance.
	 *
	 * @since  1.0.0
	 * @var    \Decalog\Plugin\Feature\DTracer    $tracer    Maintains the internal DTracer instance.
	 */
	private $tracer = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $class   The class identifier, must be a value in ['plugin', 'theme'].
	 * @param string $name    Optional. The name of the component that will trigger events.
	 * @param string $version Optional. The version of the component that will trigger events.
	 * @since 1.0.0
	 */
	public function __construct( $class, $name = null, $version = null ) {
		if ( class_exists( '\Decalog\Plugin\Feature\DTracer' ) ) {
			$this->tracer = new \Decalog\Plugin\Feature\DTracer( $class, $name, $version );
		}
	}

	/**
	 * Starts a span.
	 *
	 * @param   string  $name       The name of the span.
	 * @param   string  $parent_id  Optional. The id of the parent. If none, it will be linked to WP root id.
	 * @return  string   Id of started span.
	 * @since   1.0.0
	 */
	public function startSpan( $name, $parent_id = 'xxx' ) {
		if ( $this->tracer ) {
			return $this->tracer->start_span( $name, $parent_id );
		}
		return 'xxx';
	}

	/**
	 * Ends a span.
	 *
	 * @param   string  $id  The id of the span.
	 * @since   1.0.0
	 */
	public function endSpan( $id ) {
		if ( $this->tracer ) {
			$this->tracer->end_span( $id );
		}
	}
}