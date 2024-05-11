<?php
/**
 * DecaLog tracer definition.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\System\Blog;
use Decalog\System\Hash;
use Decalog\System\Option;
use Decalog\System\Environment;
use Decalog\Logger;
use Decalog\Plugin\Feature\ClassTypes;
use Decalog\System\Markdown;
use Decalog\Listener\AbstractListener;
use Decalog\System\User;
use Decalog\System\UUID;
use Decalog\System\IP;

/**
 * Main DecaLog tracer class.
 *
 * This class defines all code necessary to trace with DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class DTracer {

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
	 * The user ID.
	 *
	 * @since  3.0.0
	 * @var    integer    $siteid    Maintains the user ID.
	 */
	protected $siteid = -1;

	/**
	 * The site ID.
	 *
	 * @since  3.0.0
	 * @var    integer    $userid    Maintains the site ID.
	 */
	protected $userid = -1;

	/**
	 * The session ID.
	 *
	 * @since  3.0.0
	 * @var    string    $sessionid    Maintains the session ID.
	 */
	protected $sessionid = '';

	/**
	 * The remote IP.
	 *
	 * @since  3.0.0
	 * @var    string    $ip    Maintains the remote IP.
	 */
	protected $ip = '';

	/**
	 * Is logger allowed to run.
	 *
	 * @since  3.0.0
	 * @var    boolean    $allowed    Maintains the allowed status of the monitor.
	 */
	private $allowed = true;

	/**
	 * Classes to exclude.
	 *
	 * @since  3.0.0
	 * @var    array    $skip_classes    List of class partials.
	 */
	private $skip_classes = [
		'DLMonolog\\',
		'Decalog\\',
		'DecaLog\\',
		'System\\Logger',
		'Feature\\DecaLog',
		'Feature\\Capture',
	];

	/**
	 * Functions to exclude.
	 *
	 * @since  2.4.0
	 * @var    array    $skip_functions    List of functions.
	 */
	private $skip_functions = [
		'call_user_func',
		'call_user_func_array',
	];

	/**
	 * The traces registry.
	 *
	 * @since  3.0.0
	 * @var    array    $traces_registry    Maintains the traces definitions.
	 */
	private static $traces_registry = [];

	/**
	 * The traces ready to use.
	 *
	 * @since  3.0.0
	 * @var    array    $traces    Maintains the spans list.
	 */
	private static $traces = [];

	/**
	 * The internal logger.
	 *
	 * @since  3.0.0
	 * @var    \Decalog\Logger    $logger    Maintains the logger.
	 */
	private static $logger = null;

	/**
	 * Is the first initialization done?
	 *
	 * @since  3.0.0
	 * @var    boolean    $self_initialized    Is the first initialization done?
	 */
	private static $self_initialized = false;

	/**
	 * Is the closing done?
	 *
	 * @since  3.0.0
	 * @var    boolean    $self_closed    Is the closing done?
	 */
	private static $self_closed = false;

	/**
	 * WP root ID.
	 *
	 * @since  3.0.0
	 * @var    string    $wp_root_id    WP root ID.
	 */
	private static $wp_root_id = null;

	/**
	 * IDs stack.
	 *
	 * @since  3.6.0
	 * @var    string    $stack    The IDs stack.
	 */
	private static $stack = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $class      The class identifier, must be in ClassTypes::$classes.
	 * @param   string  $name       Optional. The name of the component.
	 * @param   string  $version    Optional. The version of the component.
	 * @since   3.0.0
	 */
	public function __construct( $class, $name = null, $version = null ) {
		if ( ! isset( self::$logger ) && class_exists( '\Decalog\Logger' ) ) {
			self::$logger = new \Decalog\Logger( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		}
		if ( ! isset( self::$logger ) && ! class_exists( '\Decalog\Logger' ) ) {
			self::$logger = new \Psr\Log\NullLogger();
		}
		if ( ! Option::network_get( 'autolisteners' ) ) {
			$this->allowed = in_array( 'trace', Option::network_get( 'listeners' ), true );
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
			$this->ip = IP::get_current();
			self::$logger->debug( 'A new instance of DecaLog tracing is initialized and operational.' );
		} else {
			self::$logger->debug( 'Skipped initialization of a DecaLog tracing.' );
		}
		if ( ! self::$self_initialized ) {
			self::$self_initialized = true;
			if ( ! defined( 'DECALOG_TRACEID' ) ) {
				define( 'DECALOG_TRACEID', UUID::generate_unique_id( 32 ) );
			}
			$this->init_root();
		}
	}

	/**
	 * Starts a span.
	 *
	 * @param   string  $name       The name of the span.
	 * @param   string  $parent_id  Optional. The id of the parent. If none, it will be linked to WP root id.
	 * @return  string   Id of started span.
	 * @since   3.0.0
	 */
	public function start_span( $name, $parent_id = 'xxx' ) {
		return $this->start_span_with_id( $name, null, $parent_id );
	}

	/**
	 * Starts a span.
	 *
	 * @param   string  $name       The name of the span.
	 * @param   string  $id         Optional. The id of the span.
	 * @param   string  $parent_id  Optional. The id of the parent. If none, it will be linked to WP root id.
	 * @param   int     $timestamp  Optional. The microsecond timestamp at which the span started.
	 * @return  string   Id of started span.
	 * @since   3.0.0
	 */
	public function start_span_with_id( $name, $id, $parent_id = 'xxx', $timestamp = 0 ) {
		if ( 'auto' === $parent_id ) {
			$parent_id = $this->get_auto_parent_id( $timestamp );
		}
		if ( ! array_key_exists( $parent_id, self::$traces_registry ) ) {
			$parent_id = self::$wp_root_id;
		} else {
			$parent_id = self::$traces_registry[ $parent_id ]['id'];
		}
		$span                                 = $this->init_span( $id );
		$span['parentId']                     = $parent_id;
		$span['name']                         = $span['name'] . $name;
		$span['tags']                         = array_merge( $span['tags'], $this->introspection_data() );
		self::$traces_registry[ $span['id'] ] = $span;
		self::$stack[]                        = $span['id'];
		return $span['id'];
	}

	/**
	 * Try to get parent Id.
	 *
	 * @param   int     $timestamp  The microsecond timestamp at which the span started.
	 * @return  string   Id of the parent span.
	 * @since   3.6.0
	 */
	protected function get_auto_parent_id( $timestamp ) {
		if ( 0 < count( self::$stack ) ) {
			return self::$stack[ array_key_last( self::$stack ) ];
		}
		$mark        = 0;
		$probable_id = 'unknown';
		foreach ( self::$traces_registry as $key => $span ) {
			if ( 'Core' === $span['localEndpoint']['serviceName'] && $timestamp >= $span['timestamp'] && $timestamp > $mark ) {
				if ( 0 === $span['duration'] ) {
					$mark        = $span['timestamp'];
					$probable_id = $key;
				} else {
					if ( $timestamp < $span['timestamp'] + $span['duration'] ) {
						$mark        = $span['timestamp'];
						$probable_id = $key;
					}
				}
			}
		}
		return $probable_id;
	}

	/**
	 * Ends a span.
	 *
	 * @param   string  $id  The id of the span.
	 * @since   3.0.0
	 */
	public function end_span( $id ) {
		if ( array_key_exists( $id, self::$traces_registry ) ) {
			self::$traces_registry[ $id ]['duration'] = (int) ( ( 1000000 * microtime( true ) ) - self::$traces_registry[ $id ]['timestamp'] );
		}
		if ( in_array( $id, self::$stack, true ) ) {
			do {
				$key = array_pop( self::$stack );
			} while ( $id !== $key );
		}
	}

	/**
	 * Starts, set and ends a span.
	 *
	 * @param   string  $name       The name of the span.
	 * @param   array   $values     Optional. The values to add to the span.
	 * @since   3.6.0
	 */
	public function inject_span( $name, $values ) {
		$id = $this->start_span_with_id( $name, null, 'auto', array_key_exists( 'timestamp', $values ) ? $values['timestamp'] : 0 );
		foreach ( $values as $key => $value ) {
			if ( 'tags' === $key ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $k => $v ) {
						self::$traces_registry[ $id ]['tags'][ (string) $k ] = (string) $v;
					}
				}
			} else {
				self::$traces_registry[ $id ][ $key ] = $value;
			}
		}
		array_pop( self::$stack );
	}

	/**
	 * Initializes a span.
	 *
	 * @param   string  $id     Optional. The forced Id.
	 * @return  array   An initialized span.
	 * @since   3.0.0
	 */
	private function init_span( $id = null ) {
		return [
			'id'             => $id ?? UUID::generate_unique_id( 8 ),
			'traceId'        => DECALOG_TRACEID,
			'parentId'       => '',
			'name'           => $this->name . ' / ',
			'timestamp'      => (int) ( 1000000 * microtime( true ) ),
			'duration'       => 0,
			'localEndpoint'  => [
				'serviceName' => ucwords( $this->class ),
			],
			'remoteEndpoint' => [],
			'tags'           => [
				'component.class'   => $this->class,
				'component.name'    => $this->name,
				'component.version' => $this->version,
			],
		];
	}

	/**
	 * Initializes traces root.
	 *
	 * @since   3.0.0
	 */
	private function init_root() {
		if ( ! defined( 'POWP_START_TIMESTAMP' ) ) {
			define( 'POWP_START_TIMESTAMP', microtime( true ) );
		}
		if ( ! defined( 'POWS_START_TIMESTAMP' ) ) {
			if ( array_key_exists( 'REQUEST_TIME_FLOAT', $_SERVER ) ) {
				define( 'POWS_START_TIMESTAMP', (float) filter_var( $_SERVER['REQUEST_TIME_FLOAT'], FILTER_VALIDATE_FLOAT ) );
			} else {
				define( 'POWS_START_TIMESTAMP', POWP_START_TIMESTAMP );
			}
		}
		if ( ! defined( 'POMU_END_TIMESTAMP' ) ) {
			define( 'POMU_END_TIMESTAMP', microtime( true ) );
		}
		if ( ! defined( 'POPL_START_TIMESTAMP' ) ) {
			define( 'POPL_START_TIMESTAMP', microtime( true ) );
		}
		if ( ! defined( 'DECALOG_SPAN_MUPLUGINS_LOAD' ) ) {
			define( 'DECALOG_SPAN_MUPLUGINS_LOAD', UUID::generate_unique_id( 8 ) );
		}
		if ( ! defined( 'DECALOG_SPAN_PLUGINS_LOAD' ) ) {
			define( 'DECALOG_SPAN_PLUGINS_LOAD', UUID::generate_unique_id( 8 ) );
		}
		if ( ! defined( 'DECALOG_SPAN_THEME_SETUP' ) ) {
			define( 'DECALOG_SPAN_THEME_SETUP', UUID::generate_unique_id( 8 ) );
		}
		if ( ! defined( 'DECALOG_SPAN_USER_AUTHENTICATION' ) ) {
			define( 'DECALOG_SPAN_USER_AUTHENTICATION', UUID::generate_unique_id( 8 ) );
		}
		if ( ! defined( 'DECALOG_SPAN_PLUGINS_INITIALIZATION' ) ) {
			define( 'DECALOG_SPAN_PLUGINS_INITIALIZATION', UUID::generate_unique_id( 8 ) );
		}
		if ( ! defined( 'DECALOG_SPAN_MAIN_RUN' ) ) {
			define( 'DECALOG_SPAN_MAIN_RUN', UUID::generate_unique_id( 8 ) );
		}
		if ( ! defined( 'DECALOG_SPAN_SHUTDOWN' ) ) {
			define( 'DECALOG_SPAN_SHUTDOWN', UUID::generate_unique_id( 8 ) );
		}
		// Root
		$root                                 = $this->init_span();
		$root['name']                         = 'CALL:' . $this->channel_tag( Environment::exec_mode() );
		$root['localEndpoint']['serviceName'] = 'Main Request';
		$root['timestamp']                    = (int) ( 1000000 * POWS_START_TIMESTAMP );
		$root['tags']                         = $this->www_data();
		$root['kind']                         = 'SERVER';
		unset( $root['parentId'] );
		self::$traces_registry['ROOT'] = $root;
		// Server Init
		if ( 0 < POWP_START_TIMESTAMP - POWS_START_TIMESTAMP ) {
			$init                                 = $this->init_span();
			$init['parentId']                     = $root['id'];
			$init['name']                         = 'Initialization';
			$init['localEndpoint']['serviceName'] = 'Server';
			$init['tags']                         = [];
			$init['timestamp']                    = (int) ( 1000000 * POWS_START_TIMESTAMP );
			$init['duration']                     = (int) ( 1000000 * ( POWP_START_TIMESTAMP - POWS_START_TIMESTAMP ) );
			self::$traces_registry['INIT']        = $init;
		}
		// WordPress execution
		$wp                                 = $this->init_span();
		$wp['parentId']                     = $root['id'];
		$wp['name']                         = 'Execution';
		$wp['localEndpoint']['serviceName'] = 'WordPress';
		$wp['tags']                         = [];
		$wp['timestamp']                    = (int) ( 1000000 * POWP_START_TIMESTAMP );
		self::$traces_registry[ $wp['id'] ] = $wp;
		self::$wp_root_id                   = $wp['id'];
		// WordPress full load
		$wpfl                                 = $this->init_span();
		$wpfl['parentId']                     = $wp['id'];
		$wpfl['name']                         = 'WordPress / Load';
		$wpfl['localEndpoint']['serviceName'] = 'Core';
		$wpfl['timestamp']                    = (int) ( 1000000 * POWP_START_TIMESTAMP );
		self::$traces_registry['WPFL']        = $wpfl;
		// WordPress load
		$wpl                                 = $this->init_span();
		$wpl['id']                           = DECALOG_SPAN_MUPLUGINS_LOAD;
		$wpl['parentId']                     = $wpfl['id'];
		$wpl['name']                         = 'WordPress / Core & MU-Plugins Load';
		$wpl['localEndpoint']['serviceName'] = 'Core';
		$wpl['timestamp']                    = (int) ( 1000000 * POWP_START_TIMESTAMP );
		$wpl['duration']                     = (int) ( 1000000 * ( POMU_END_TIMESTAMP - POWP_START_TIMESTAMP ) );
		self::$traces_registry[ $wpl['id'] ] = $wpl;
		// Plugins load
		$wpl                                 = $this->init_span();
		$wpl['id']                           = DECALOG_SPAN_PLUGINS_LOAD;
		$wpl['parentId']                     = $wpfl['id'];
		$wpl['name']                         = 'WordPress / Plugins Load';
		$wpl['localEndpoint']['serviceName'] = 'Core';
		$wpl['timestamp']                    = (int) ( 1000000 * POPL_START_TIMESTAMP );
		self::$traces_registry[ $wpl['id'] ] = $wpl;
	}

	/**
	 * Initializes traces root.
	 *
	 * @since   3.0.0
	 */
	public static function plugins_loaded() {
		self::$traces_registry[ DECALOG_SPAN_PLUGINS_LOAD ]['duration'] = (int) ( ( 1000000 * microtime( true ) ) - self::$traces_registry[ DECALOG_SPAN_PLUGINS_LOAD ]['timestamp'] );
	}

	/**
	 * Verify if a trace must be skipped.
	 *
	 * @param   array   $trace    The trace to verify.
	 * @param   integer $index    The index of the trace to verify.
	 * @return  boolean     True if the record must be skipped, false otherwise.
	 * @since   3.0.0
	 */
	private function is_skipped( array $trace, int $index ) {
		if ( ! isset( $trace[ $index ] ) ) {
			return false;
		}
		return isset( $trace[ $index ]['class'] ) || in_array( $trace[ $index ]['function'], $this->skip_functions, true );
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
		return ChannelTypes::$channels[ $id ];
	}

	/**
	 * Data to add for introspection processing.
	 *
	 * @return  array   The introspection records.
	 * @since   3.0.0
	 */
	private function introspection_data() {
		// phpcs:ignore
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		array_shift( $trace );
		array_shift( $trace );
		$i = 0;
		while ( $this->is_skipped( $trace, $i ) ) {
			if ( isset( $trace[ $i ]['class'] ) ) {
				foreach ( $this->skip_classes as $part ) {
					if ( strpos( $trace[ $i ]['class'], $part ) !== false ) {
						$i++;
						continue 2;
					}
				}
			} elseif ( in_array( $trace[ $i ]['function'], $this->skip_functions, true ) ) {
				$i++;
				continue;
			}
			break;
		}
		return [
			'php.file'     => $trace[ $i - 1 ]['file'] ?? null,
			'php.line'     => $trace[ $i - 1 ]['line'] ?? null,
			'php.class'    => $trace[ $i ]['class'] ?? null,
			'php.function' => $trace[ $i ]['function'] ?? null,
		];
	}

	/**
	 * Data to add for WordPress processing.
	 *
	 * @return  array   The WordPress records.
	 * @since   3.0.0
	 */
	private function wordpress_data() {
		if ( -1 === $this->siteid ) {
			$this->siteid = Blog::get_current_blog_id( 0 );
		}
		if ( -1 === $this->userid ) {
			$this->userid = User::get_current_user_id( 0 );
		}
		if ( '' === $this->sessionid && function_exists( 'wp_parse_auth_cookie' ) ) {
			$this->sessionid = Hash::simple_hash( wp_get_session_token(), false );
		}
		return [
			'wp.siteid'    => $this->siteid,
			'wp.userid'    => $this->userid,
			'wp.sessionid' => $this->sessionid,
			'wp.remoteip'  => $this->ip,
		];
	}

	/**
	 * Data to add for WordPress processing.
	 *
	 * @return  array   The WordPress records.
	 * @since   3.0.0
	 */
	private function www_data() {
		$result = [
			'http.remoteip' => $this->ip,
		];
		if ( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) ) {
			$result['http.useragent'] = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' );
		}
		if ( array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
			$result['http.uri'] = filter_input( INPUT_SERVER, 'REQUEST_URI' );
		}
		if ( array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
			$result['http.method'] = filter_input( INPUT_SERVER, 'REQUEST_METHOD' );
		}
		if ( array_key_exists( 'HTTP_REFERER', $_SERVER ) ) {
			$result['http.referer'] = filter_input( INPUT_SERVER, 'HTTP_REFERER' );
		}
		return $result;
	}

	/**
	 * Get the traces registry.
	 *
	 * @return  array   The registry;
	 * @since 3.0.0
	 */
	public function traces() {
		if ( ! $this->allowed ) {
			return [];
		}
		$this->before_close();
		return self::$traces;
	}

	/**
	 * Computes traces.
	 *
	 * @since   3.0.0
	 */
	private function before_close() {
		if ( self::$self_closed ) {
			return;
		}
		self::$self_closed = true;
		if ( ! defined( 'POWP_END_TIMESTAMP' ) ) {
			define( 'POWP_END_TIMESTAMP', microtime( true ) );
		}
		$end = (int) ( 1000000 * POWP_END_TIMESTAMP );
		foreach ( self::$traces_registry as $tid => $span ) {
			if ( 'ROOT' !== $tid && 'INIT' !== $tid ) {
				$span['tags'] = array_merge( $span['tags'], $this->wordpress_data(), $this->www_data() );
			}
			if ( 0 === $span['duration'] ) {
				$span['duration'] = $end - $span['timestamp'];
			}
			foreach ( $span as $key => $value ) {
				if ( is_null( $value ) || ( is_array( $value ) && 0 === count( $value ) ) || ( is_string( $value ) && '' === $value ) ) {
					unset( $span[ $key ] );
				}
			}
			self::$traces[] = $span;
		}
	}
}
