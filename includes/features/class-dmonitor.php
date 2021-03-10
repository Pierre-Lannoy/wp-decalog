<?php
/**
 * DecaLog monitor definition.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Plugin\Feature;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Decalog\System\Option;
use Decalog\System\Environment;
use Decalog\Logger;
use Decalog\Plugin\Feature\ClassTypes;

/**
 * Main DecaLog monitor class.
 *
 * This class defines all code necessary to monitor metrics with DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class DMonitor {

	/**
	 * The class of the component.
	 *
	 * @since  3.0.0
	 * @var    string    $class    Maintains the class of the component.
	 */
	protected $class = 'unknwon';

	/**
	 * The name of the component.
	 *
	 * @since  3.0.0
	 * @var    string    $class    Maintains the name of the component.
	 */
	protected $name = 'unknown';

	/**
	 * The version of the component.
	 *
	 * @since  3.0.0
	 * @var    string    $version    Maintains the version of the component.
	 */
	protected $version = '-';

	/**
	 * The "production" CollectorRegistry instance.
	 *
	 * @since  3.0.0
	 * @var    \Prometheus\CollectorRegistry    $production    Maintains the internal CollectorRegistry instance.
	 */
	private static $production = null;

	/**
	 * The "development" CollectorRegistry instance.
	 *
	 * @since  3.0.0
	 * @var    \Prometheus\CollectorRegistry    $development    Maintains the internal CollectorRegistry instance.
	 */
	private static $development = null;

	/**
	 * The internal logger.
	 *
	 * @since  3.0.0
	 * @var    \Decalog\Logger    $logger    Maintains the logger.
	 */
	private static $logger = null;

	/**
	 * The metrics labels names.
	 *
	 * @since  3.0.0
	 * @var    array    $label_names    The names list.
	 */
	private $label_names = [ 'channel', 'environment', 'traceID' ];

	/**
	 * The metrics labels values.
	 *
	 * @since  3.0.0
	 * @var    array    $label_values    The values list.
	 */
	private $label_values = [];

	/**
	 * Is logger allowed to run.
	 *
	 * @since  3.0.0
	 * @var    boolean    $allowed    Maintains the allowed status of the monitor.
	 */
	private $allowed = true;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $class      The class identifier, must be in ClassTypes::$classes.
	 * @param   string  $name       Optional. The name of the component.
	 * @param   string  $version    Optional. The version of the component.
	 * @param   boolean $prom       Optional. True if this logger is a PSR-3 logger.
	 * @since   3.0.0
	 */
	public function __construct( $class, $name = null, $version = null, $prom = false ) {
		if ( $prom ) {
			if ( ! Option::network_get( 'autolisteners' ) ) {
				$this->allowed = in_array( 'prom', Option::network_get( 'listeners' ), true );
			}
		}
		if ( $this->allowed ) {
			if ( in_array( $class, ClassTypes::$classes, true ) ) {
				$this->class = $class;
			}
			if ( $name && is_string( $name ) ) {
				$this->name = $name;
			}
			if ( $version && is_string( $version ) ) {
				$this->version = $version;
			}
			if ( ! isset( self::$production ) ) {
				self::$production = new CollectorRegistry( new InMemory(), false );
			}
			if ( ! isset( self::$development ) ) {
				self::$development = new CollectorRegistry( new InMemory(), true );
			}
			if ( ! isset( self::$logger ) ) {
				self::$logger = new Logger( $class, $name, $version );
			}
			$this->label_values = [
				$this->normalize_string( $this->current_channel_tag() ),
				$this->normalize_string( Environment::stage() ),
				(string) DECALOG_TRACEID,
			];
			self::$logger->debug( 'A new instance of DecaLog monitor is initialized and operational.' );
		} else {
			self::$logger->debug( 'Skipped initialization of a DecaLog monitor.' );
		}
	}

	/**
	 * Creates and sets the named gauge, in the right profile.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The initial value to set.
	 * @param string    $help      The help string associated with this gauge.
	 * @since 3.0.0
	 */
	private function create_gauge( $prod, $name, $value, $help ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry = ( $prod ? self::$production : self::$development );
			$registry->registerGauge( $this->current_namespace(), $name, $help, $this->label_names );
			$this->set_gauge( $prod, $name, $value );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * Sets the named gauge, in the right profile.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value to set.
	 * @since 3.0.0
	 */
	private function set_gauge( $prod, $name, $value ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry = ( $prod ? self::$production : self::$development );
			$gauge    = $registry->getGauge( $this->current_namespace(), $name );
			$gauge->set( $value, $this->label_values );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Increments the named gauge, in the right profile.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value of how much to increment.
	 * @since 3.0.0
	 */
	private function inc_gauge( $prod, $name, $value ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry = ( $prod ? self::$production : self::$development );
			$gauge    = $registry->getGauge( $this->current_namespace(), $name );
			$gauge->incBy( $value, $this->label_values );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Create and set the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The initial value to set.
	 * @param string    $help      Optional. The help string associated with this gauge.
	 * @since 3.0.0
	 */
	public function create_prod_gauge( $name, $value = 0, $help = '' ) {
		$this->create_gauge( true, $name, $value, $help );
	}

	/**
	 * Sets the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value to set.
	 * @since 3.0.0
	 */
	public function set_prod_gauge( $name, $value ) {
		$this->set_gauge( true, $name, $value );
	}

	/**
	 * Increments the named gauge, in production profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_prod_gauge( $name, $value ) {
		$this->inc_gauge( true, $name, $value );
	}

	/**
	 * Create and set the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     Optional. The initial value to set.
	 * @param string    $help      Optional. The help string associated with this gauge.
	 * @since 3.0.0
	 */
	public function create_dev_gauge( $name, $value = 0, $help = '' ) {
		$this->create_gauge( false, $name, $value, $help );
	}

	/**
	 * Sets the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value to set.
	 * @since 3.0.0
	 */
	public function set_dev_gauge( $name, $value ) {
		$this->set_gauge( false, $name, $value );
	}

	/**
	 * Increments the named gauge, in development profile.
	 *
	 * @param string    $name      The unique name of the gauge.
	 * @param int|float $value     The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_dev_gauge( $name, $value ) {
		$this->inc_gauge( false, $name, $value );
	}

	/**
	 * Get the current namespace.
	 *
	 * @return  string The current namespace.
	 * @since 3.0.0
	 */
	private function current_namespace() {
		$class = strtolower( $this->class );
		$name  = strtolower( str_replace( ' ', '', $this->name ) ); // TODO : replace all non /w char
		return 'wordpress_' . $class . '_' . $name;
	}

	/**
	 * Get the current channel tag.
	 *
	 * @return  string The current channel tag.
	 * @since 3.0.0
	 */
	private function current_channel_tag() {
		return $this->channel_tag( Environment::exec_mode() );
	}

	/**
	 * Get the channel tag.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel tag.
	 * @since 3.0.0
	 */
	private function channel_tag( $id = 0 ) {
		if ( $id >= count( ChannelTypes::$channels ) ) {
			$id = 0;
		}
		return strtolower( ChannelTypes::$channels[ $id ] );
	}

	/**
	 * Normalize a string.
	 *
	 * @param string  $string The string.
	 * @return string   The normalized string.
	 * @since 1.10.0+
	 */
	private function normalize_string( $string ) {
		$string = str_replace( '"', '“', $string );
		$string = str_replace( '\'', '`', $string );
		return filter_var( $string, FILTER_SANITIZE_STRING );
	}

	/**
	 * Get the collector registry for production profile.
	 *
	 * @return  \Prometheus\CollectorRegistry   The production registry.
	 * @since 3.0.0
	 */
	public function prod_registry() {
		return self::$production;
	}

	/**
	 * Get the collector registry for development profile.
	 *
	 * @return  \Prometheus\CollectorRegistry   The development registry.
	 * @since 3.0.0
	 */
	public function dev_registry() {
		return self::$development;
	}
}
