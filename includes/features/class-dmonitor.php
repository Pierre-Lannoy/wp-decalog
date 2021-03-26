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
use Decalog\System\Markdown;
use Decalog\Listener\AbstractListener;

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
	 * The metrics registry.
	 *
	 * @since  3.0.0
	 * @var    array    $metrics_registry    Maintains the metrics definitions.
	 */
	private static $metrics_registry = [];

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
	 * The technical metrics labels names.
	 *
	 * @since  3.0.0
	 * @var    array    $label_names    The names list.
	 */
	private $label_names = [
		'prod' => [ 'environment' ],
		'dev'  => [ 'channel', 'environment' ],
	];

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
	 * Is the first initialization done?
	 *
	 * @since  3.0.0
	 * @var    boolean    $self_initialized    Is the first initialization done?
	 */
	private static $self_initialized = false;

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
				self::$development = new CollectorRegistry( new InMemory(), false );
			}
			if ( ! isset( self::$logger ) ) {
				self::$logger = new Logger( $class, $name, $version );
			}
			$this->label_values = [
				'prod' => [ $this->normalize_string( Environment::stage() ) ],
				'dev'  => [ $this->normalize_string( $this->current_channel_tag() ), $this->normalize_string( Environment::stage() ) ],
			];
			self::$logger->debug( 'A new instance of DecaLog monitor is initialized and operational.' );
		} else {
			self::$logger->debug( 'Skipped initialization of a DecaLog monitor.' );
		}
		if ( ! self::$self_initialized ) {
			self::$self_initialized = true;
			$this->class            = 'plugin';
			$this->name             = DECALOG_PRODUCT_NAME;
			$this->version          = DECALOG_VERSION;
			foreach ( EventTypes::$levels as $key => $level ) {
				$this->create_dev_counter( 'event_' . $key, 'Number of handled ' . $key . ' events - [count]' );
			}
			$this->create_dev_counter( 'metric_prod', 'Number of handled `production` metrics - [count]' );
			$this->create_dev_counter( 'metric_dev', 'Number of handled `development` metrics - [count]' );
			add_action( 'shutdown', [ $this, 'before_close' ], AbstractListener::$monitor_priority - 1, 0 );
			$this->class   = $class;
			$this->name    = $name;
			$this->version = $version;
		}
	}

	/**
	 * Self-metrics handling..
	 *
	 * @since   3.0.0
	 */
	public function before_close() {
		$class         = $this->class;
		$name          = $this->name;
		$version       = $this->version;
		$this->class   = 'plugin';
		$this->name    = DECALOG_PRODUCT_NAME;
		$this->version = DECALOG_VERSION;
		foreach ( EventTypes::$levels as $key => $level ) {
			$this->inc_dev_counter( 'event_' . $key, DLogger::count( $key ) );
		}
		$this->inc_dev_counter( 'metric_prod', count( self::$production->getMetricFamilySamples() ) );
		$this->inc_dev_counter( 'metric_dev', count( self::$development->getMetricFamilySamples() ) );
		$this->class   = $class;
		$this->name    = $name;
		$this->version = $version;
	}

	/**
	 * Get the metrics registry.
	 *
	 * @return  array   The registry;
	 * @since 3.0.0
	 */
	public static function registry() {
		return self::$metrics_registry;
	}

	/**
	 * Register the metrics.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $type      The type of the metrics (counter, gauge or histogram).
	 * @param string    $name      The unique name of the metrics.
	 * @param string    $help      The help string associated with this metrics.
	 * @since 3.0.0
	 */
	private function register( $prod, $type, $name, $help ) {
		$prod = $prod ? 'production' : 'development';
		if ( ! array_key_exists( $this->class, self::$metrics_registry ) ) {
			self::$metrics_registry[ $this->class ] = [];
		}
		if ( ! array_key_exists( $prod, self::$metrics_registry[ $this->class ] ) ) {
			self::$metrics_registry[ $this->class ][ $prod ] = [];
		}
		if ( ! array_key_exists( $type, self::$metrics_registry[ $this->class ][ $prod ] ) ) {
			self::$metrics_registry[ $this->class ][ $prod ][ $type ] = [];
		}
		$idx = $this->current_namespace() . '_' . $name;
		if ( ! array_key_exists( $idx, self::$metrics_registry[ $this->class ][ $prod ][ $type ] ) ) {
			self::$metrics_registry[ $this->class ][ $prod ][ $type ][ $this->current_namespace() . '_' . $name ] = [
				'name'    => $this->name,
				'version' => $this->version,
				'help'    => $help,
			];
		}
	}

	/**
	 * Creates the named counter, in the right profile.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $name      The unique name of the counter.
	 * @param string    $help      The help string associated with this counter.
	 * @since 3.0.0
	 */
	private function create_counter( $prod, $name, $help ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry = ( $prod ? self::$production : self::$development );
			$registry->registerCounter( $this->current_namespace(), $name, $help, $this->label_names[ ( $prod ? 'prod' : 'dev' ) ] );
			$this->register( $prod, 'counter', $name, $help );
			$this->init_counter( $prod, $name );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
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
			$registry->registerGauge( $this->current_namespace(), $name, $help, $this->label_names[ ( $prod ? 'prod' : 'dev' ) ] );
			$this->register( $prod, 'gauge', $name, $help );
			$this->set_gauge( $prod, $name, $value );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Creates the named histogram, in the right profile.
	 *
	 * @param boolean       $prod      True if it's production profile, false if it's development profile.
	 * @param string        $name      The unique name of the histogram.
	 * @param null|array    $buckets   The buckets.
	 * @param string        $help      The help string associated with this histogram.
	 * @since 3.0.0
	 */
	private function create_histogram( $prod, $name, $buckets, $help ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry = ( $prod ? self::$production : self::$development );
			$registry->registerHistogram( $this->current_namespace(), $name, $help, $this->label_names[ ( $prod ? 'prod' : 'dev' ) ], $buckets );
			$this->register( $prod, 'histogram', $name, $help );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Inits the named counter, in the right profile.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $name      The unique name of the counter.
	 * @since 3.0.0
	 */
	private function init_counter( $prod, $name ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry = ( $prod ? self::$production : self::$development );
			$counter  = $registry->getCounter( $this->current_namespace(), $name );
			$counter->incBy( 0, $this->label_values[ ( $prod ? 'prod' : 'dev' ) ] );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
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
			$gauge->set( $value, $this->label_values[ ( $prod ? 'prod' : 'dev' ) ] );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Increments the named counter, in the right profile.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $name      The unique name of the counter.
	 * @param int|float $value     The value of how much to increment.
	 * @since 3.0.0
	 */
	private function inc_counter( $prod, $name, $value ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry = ( $prod ? self::$production : self::$development );
			$counter  = $registry->getCounter( $this->current_namespace(), $name );
			$counter->incBy( $value, $this->label_values[ ( $prod ? 'prod' : 'dev' ) ] );
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
			$gauge->incBy( $value, $this->label_values[ ( $prod ? 'prod' : 'dev' ) ] );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Adds an observation to the named histogram, in the right profile.
	 *
	 * @param boolean   $prod      True if it's production profile, false if it's development profile.
	 * @param string    $name      The unique name of the histogram.
	 * @param int|float $value     The value to add.
	 * @since 3.0.0
	 */
	private function observe_histogram( $prod, $name, $value ) {
		if ( ! $this->allowed ) {
			return;
		}
		try {
			$registry  = ( $prod ? self::$production : self::$development );
			$histogram = $registry->getHistogram( $this->current_namespace(), $name );
			$histogram->observe( $value, $this->label_values[ ( $prod ? 'prod' : 'dev' ) ] );
		} catch ( \Throwable $e ) {
			self::$logger->error( $e->getMessage(), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Create the named counter, in production profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param string    $help      Optional. The help string associated with this counter.
	 * @since 3.0.0
	 */
	public function create_prod_counter( $name, $help = '' ) {
		$this->create_counter( true, $name, $help );
	}

	/**
	 * Increments the named counter, in production profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param int|float $value     The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_prod_counter( $name, $value ) {
		$this->inc_counter( true, $name, $value );
	}

	/**
	 * Create the named counter, in development profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param string    $help      Optional. The help string associated with this counter.
	 * @since 3.0.0
	 */
	public function create_dev_counter( $name, $help = '' ) {
		$this->create_counter( false, $name, $help );
	}

	/**
	 * Increments the named counter, in development profile.
	 *
	 * @param string    $name      The unique name of the counter.
	 * @param int|float $value     The value of how much to increment.
	 * @since 3.0.0
	 */
	public function inc_dev_counter( $name, $value ) {
		$this->inc_counter( false, $name, $value );
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
	 * Creates the named histogram, in production profile.
	 *
	 * @param string        $name      The unique name of the histogram.
	 * @param null|array    $buckets   Optional. The buckets.
	 * @param string        $help      Optional. The help string associated with this histogram.
	 * @since 3.0.0
	 */
	public function create_prod_histogram( $name, $buckets = null, $help = '' ) {
		$this->create_histogram( true, $name, $buckets, $help );
	}

	/**
	 * Adds an observation to the named histogram, in production profile.
	 *
	 * @param string    $name      The unique name of the histogram.
	 * @param int|float $value     The value to add.
	 * @since 3.0.0
	 */
	public function observe_prod_histogram( $name, $value ) {
		$this->observe_histogram( true, $name, $value );
	}

	/**
	 * Creates the named histogram, in development profile.
	 *
	 * @param string        $name      The unique name of the histogram.
	 * @param null|array    $buckets   Optional. The buckets.
	 * @param string        $help      Optional. The help string associated with this histogram.
	 * @since 3.0.0
	 */
	public function create_dev_histogram( $name, $buckets = null, $help = '' ) {
		$this->create_histogram( false, $name, $buckets, $help );
	}

	/**
	 * Adds an observation to the named histogram, in development profile.
	 *
	 * @param string    $name      The unique name of the histogram.
	 * @param int|float $value     The value to add.
	 * @since 3.0.0
	 */
	public function observe_dev_histogram( $name, $value ) {
		$this->observe_histogram( false, $name, $value );
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

	/**
	 * Get the metrics definitions.
	 *
	 * @return  array  The output of the shortcode, ready to print.
	 * @since 3.0.0
	 */
	public static function get_metrics_definition() {
		$content = [];
		foreach ( self::$metrics_registry as $class => $class_detail ) {
			foreach ( $class_detail as $env => $env_detail ) {
				foreach ( $env_detail as $type => $type_detail ) {
					foreach ( $type_detail as $metrics => $detail ) {
						$content[ $metrics ] = [
							'class'       => $class,
							'profile'     => $env,
							'type'        => $type,
							'name'        => $metrics,
							'source'      => $detail['name'],
							'version'     => $detail['version'],
							'description' => $detail['help'],
						];
					}
				}
			}
		}
		return $content;
	}

	/**
	 * Get the metrics definitions.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 3.0.0
	 */
	public static function sc_get_metrics( $attributes ) {
		$content = '<div class="markdown">';
		foreach ( self::$metrics_registry as $class => $class_detail ) {
			$content .= '<p><br/></p>';
			$content .= '<h2>' . strtoupper( $class ) . ' CLASS</h2>';
			foreach ( $class_detail as $env => $env_detail ) {
				$content .= '<h3 style="margin:0;">' . ucfirst( $env ) . ' Profile</h3>';
				foreach ( $env_detail as $type => $type_detail ) {
					$content .= '<ul>';
					foreach ( $type_detail as $metrics => $detail ) {
						$content .= '<li>' . ucfirst( $type ) . ' <code>' . $metrics . '</code> from ' . $detail['name'] . ' ' . $detail['version'] . ' - ' . $detail['help'] . '.</li>';
					}
					$content .= '</ul>';
				}
			}
		}
		$content .= '</div>';
		return $content;
	}

}

add_shortcode( 'decalog-metrics', [ 'Decalog\Plugin\Feature\DMonitor', 'sc_get_metrics' ] );
