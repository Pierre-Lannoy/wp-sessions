<?php
/**
 * DecaLog monitor definition.
 *
 * @package SDK
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace DecaLog;

/**
 * DecaLog monitor class.
 *
 * This class defines all code necessary to monitor metrics with DecaLog.
 * If DecaLog is not installed, it will do nothing and will not throw errors.
 *
 * @package SDK
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class MetricsLogger {

	/**
	 * The "true" DMonitor instance.
	 *
	 * @since  1.0.0
	 * @var    \Decalog\Plugin\Feature\DMonitor    $monitor    Maintains the internal DMonitor instance.
	 */
	private $monitor = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $class   The class identifier, must be a value in ['plugin', 'theme'].
	 * @param string $name    Optional. The name of the component that will trigger events.
	 * @param string $version Optional. The version of the component that will trigger events.
	 * @since 1.0.0
	 */
	public function __construct( $class, $name = null, $version = null ) {
		if ( class_exists( '\Decalog\Plugin\Feature\DMonitor' ) ) {
			$this->monitor = new \Decalog\Plugin\Feature\DMonitor( $class, $name, $version );
		}
	}

	/**
	 * Create the named counter, in production profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param string    $help      Optional. The help string associated with this counter.
	 * @since 1.0.0
	 */
	public function createProdCounter( $name, $help = null ) {
		if ( $this->monitor ) {
			$this->monitor->create_prod_counter( $name, $help );
		}
	}

	/**
	 * Increments the named counter, in production profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 1.0.0
	 */
	public function incProdCounter( $name, $value = 1 ) {
		if ( $this->monitor ) {
			$this->monitor->inc_prod_counter( $name, $value );
		}
	}

	/**
	 * Create the named counter, in development profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param string    $help      Optional. The help string associated with this counter.
	 * @since 1.0.0
	 */
	public function createDevCounter( $name, $help = null ) {
		if ( $this->monitor ) {
			$this->monitor->create_dev_counter( $name, $help );
		}
	}

	/**
	 * Increments the named counter, in development profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 1.0.0
	 */
	public function incDevCounter( $name, $value = 1 ) {
		if ( $this->monitor ) {
			$this->monitor->inc_dev_counter( $name, $value );
		}
	}

	/**
	 * Create and set the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The initial value to set.
	 * @param string    $help      Optional. The help string associated with this gauge.
	 * @since 1.0.0
	 */
	public function createProdGauge( $name, $value = 0, $help = null ) {
		if ( $this->monitor ) {
			$this->monitor->create_prod_gauge( $name, $value, $help );
		}
	}

	/**
	 * Sets the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value to set.
	 * @since 1.0.0
	 */
	public function setProdGauge( $name, $value ) {
		if ( $this->monitor ) {
			$this->monitor->set_prod_gauge( $name, $value );
		}
	}

	/**
	 * Increments the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 1.0.0
	 */
	public function incProdGauge( $name, $value = 1 ) {
		if ( $this->monitor ) {
			$this->monitor->inc_prod_gauge( $name, $value );
		}
	}

	/**
	 * Decrements the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to decrement.
	 * @since 1.0.0
	 */
	public function decProdGauge( $name, $value = 1 ) {
		if ( $this->monitor ) {
			$this->monitor->inc_prod_gauge( $name, - $value );
		}
	}

	/**
	 * Create and set the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The initial value to set.
	 * @param string    $help      Optional. The help string associated with this gauge.
	 * @since 1.0.0
	 */
	public function createDevGauge( $name, $value = 0, $help = null ) {
		if ( $this->monitor ) {
			$this->monitor->create_dev_gauge( $name, $value, $help );
		}
	}

	/**
	 * Sets the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value to set.
	 * @since 1.0.0
	 */
	public function setDevGauge( $name, $value ) {
		if ( $this->monitor ) {
			$this->monitor->set_dev_gauge( $name, $value );
		}
	}

	/**
	 * Increments the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 1.0.0
	 */
	public function incDevGauge( $name, $value = 1 ) {
		if ( $this->monitor ) {
			$this->monitor->inc_dev_gauge( $name, $value );
		}
	}

	/**
	 * Decrements the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to decrement.
	 * @since 1.0.0
	 */
	public function decDevGauge( $name, $value = 1 ) {
		if ( $this->monitor ) {
			$this->monitor->inc_dev_gauge( $name, - $value );
		}
	}

	/**
	 * Creates the named histogram, in production profile.
	 *
	 * @param string        $name      The unique name of the histogram.
	 * @param null|array    $buckets   Optional. The buckets.
	 * @param string        $help      Optional. The help string associated with this histogram.
	 * @since 1.0.0
	 */
	private function createProdHistogram( $name, $buckets = null, $help = '' ) {
		if ( $this->monitor ) {
			$this->monitor->create_prod_histogram( $name, $buckets, $help );
		}
	}

	/**
	 * Adds an observation to the named histogram, in production profile.
	 *
	 * @param string    $name      The unique name of the histogram.
	 * @param int|float $value     The value to add.
	 * @since 1.0.0
	 */
	public function observeProdHistogram( $name, $value ) {
		if ( $this->monitor ) {
			$this->monitor->observe_prod_histogram( $name, $value );
		}
	}

	/**
	 * Creates the named histogram, in development profile.
	 *
	 * @param string        $name      The unique name of the histogram.
	 * @param null|array    $buckets   Optional. The buckets.
	 * @param string        $help      Optional. The help string associated with this histogram.
	 * @since 1.0.0
	 */
	private function createDevHistogram( $name, $buckets = null, $help = '' ) {
		if ( $this->monitor ) {
			$this->monitor->create_dev_histogram( $name, $buckets, $help );
		}
	}

	/**
	 * Adds an observation to the named histogram, in development profile.
	 *
	 * @param string    $name      The unique name of the histogram.
	 * @param int|float $value     The value to add.
	 * @since 1.0.0
	 */
	public function observeDevHistogram( $name, $value ) {
		if ( $this->monitor ) {
			$this->monitor->observe_dev_histogram( $name, $value );
		}
	}
}