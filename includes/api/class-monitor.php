<?php
/**
 * Prometheus monitor definition.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog;

use Decalog\Plugin\Feature\DMonitor;

/**
 * Prometheus monitor class.
 *
 * This class defines all code necessary to monitor metrics with DecaLog.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class Monitor {

	/**
	 * The "true" DMonitor instance.
	 *
	 * @since  3.0.0
	 * @var    \Decalog\Plugin\Feature\DMonitor    $monitor    Maintains the internal DMonitor instance.
	 */
	private $monitor = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $class   The class identifier, must be a value in ['plugin', 'theme'].
	 * @param string $name    Optional. The name of the component that will trigger events.
	 * @param string $version Optional. The version of the component that will trigger events.
	 * @since 3.0.0
	 */
	public function __construct( $class, $name = null, $version = null ) {
		$this->monitor = new DMonitor( $class, $name, $version, true );
	}

	/**
	 * Create the named counter, in production profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param string    $help      Optional. The help string associated with this counter.
	 * @since 3.0.0
	 */
	public function create_prod_counter( $name, $help = null ) {
		$this->monitor->create_prod_counter( $name, $help );
	}

	/**
	 * Increments the named counter, in production profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_prod_counter( $name, $value = 1 ) {
		$this->monitor->inc_prod_counter( $name, $value );
	}

	/**
	 * Create the named counter, in development profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param string    $help      Optional. The help string associated with this counter.
	 * @since 3.0.0
	 */
	public function create_dev_counter( $name, $help = null ) {
		$this->monitor->create_dev_counter( $name, $help );
	}

	/**
	 * Increments the named counter, in development profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_dev_counter( $name, $value = 1 ) {
		$this->monitor->inc_dev_counter( $name, $value );
	}

	/**
	 * Create and set the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The initial value to set.
	 * @param string    $help      Optional. The help string associated with this gauge.
	 * @since 3.0.0
	 */
	public function create_prod_gauge( $name, $value = 0, $help = null ) {
		$this->monitor->create_prod_gauge( $name, $value, $help );
	}

	/**
	 * Sets the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value to set.
	 * @since 3.0.0
	 */
	public function set_prod_gauge( $name, $value ) {
		$this->monitor->set_prod_gauge( $name, $value );
	}

	/**
	 * Increments the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_prod_gauge( $name, $value = 1 ) {
		$this->monitor->inc_prod_gauge( $name, $value );
	}

	/**
	 * Decrements the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to decrement.
	 * @since 3.0.0
	 */
	public function dec_prod_gauge( $name, $value = 1 ) {
		$this->monitor->inc_prod_gauge( $name, - $value );
	}

	/**
	 * Create and set the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The initial value to set.
	 * @param string    $help      Optional. The help string associated with this gauge.
	 * @since 3.0.0
	 */
	public function create_dev_gauge( $name, $value = 0, $help = null ) {
		$this->monitor->create_dev_gauge( $name, $value, $help );
	}

	/**
	 * Sets the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value to set.
	 * @since 3.0.0
	 */
	public function set_dev_gauge( $name, $value ) {
		$this->monitor->set_dev_gauge( $name, $value );
	}

	/**
	 * Increments the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_dev_gauge( $name, $value = 1 ) {
		$this->monitor->inc_dev_gauge( $name, $value );
	}

	/**
	 * Decrements the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The value of how much to decrement.
	 * @since 3.0.0
	 */
	public function dec_dev_gauge( $name, $value = 1 ) {
		$this->monitor->inc_dev_gauge( $name, - $value );
	}

	/**
	 * Creates the named histogram, in production profile.
	 *
	 * @param string        $name      The unique name of the histogram.
	 * @param null|array    $buckets   Optional. The buckets.
	 * @param string        $help      Optional. The help string associated with this histogram.
	 * @since 3.0.0
	 */
	private function create_prod_histogram( $name, $buckets = null, $help = '' ) {
		$this->monitor->create_prod_histogram( $name, $buckets, $help );
	}

	/**
	 * Adds an observation to the named histogram, in production profile.
	 *
	 * @param string    $name      The unique name of the histogram.
	 * @param int|float $value     The value to add.
	 * @since 3.0.0
	 */
	public function observe_prod_histogram( $name, $value ) {
		$this->monitor->observe_prod_histogram( $name, $value );
	}

	/**
	 * Creates the named histogram, in development profile.
	 *
	 * @param string        $name      The unique name of the histogram.
	 * @param null|array    $buckets   Optional. The buckets.
	 * @param string        $help      Optional. The help string associated with this histogram.
	 * @since 3.0.0
	 */
	private function create_dev_histogram( $name, $buckets = null, $help = '' ) {
		$this->monitor->create_dev_histogram( $name, $buckets, $help );
	}

	/**
	 * Adds an observation to the named histogram, in development profile.
	 *
	 * @param string    $name      The unique name of the histogram.
	 * @param int|float $value     The value to add.
	 * @since 3.0.0
	 */
	public function observe_dev_histogram( $name, $value ) {
		$this->monitor->observe_dev_histogram( $name, $value );
	}
}