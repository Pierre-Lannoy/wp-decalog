<?php
/**
 * DecaLog logger definition.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Plugin\Feature\HandlerTypes;
use DLMonolog\Logger;
use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\System\Timezone;
use Decalog\Plugin\Feature\LoggerFactory;
use Decalog\Plugin\Feature\ClassTypes;
use Decalog\Plugin\Feature\ChannelTypes;
use Decalog\Plugin\Feature\HandlerDiagnosis;
use Decalog\System\UUID;
use Decalog\Plugin\Feature\DMonitor;

/**
 * Main DecaLog logger class.
 *
 * This class defines all code necessary to log events with DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class DLogger {

	/**
	 * The class of the component.
	 *
	 * @since  1.0.0
	 * @var    string    $class    Maintains the class of the component.
	 */
	protected $class = 'unknwon';

	/**
	 * The name of the component.
	 *
	 * @since  1.0.0
	 * @var    string    $class    Maintains the name of the component.
	 */
	protected $name = 'unknown';

	/**
	 * The version of the component.
	 *
	 * @since  1.0.0
	 * @var    string    $version    Maintains the version of the component.
	 */
	protected $version = '-';

	/**
	 * The monolog logger.
	 *
	 * @since  1.0.0
	 * @var    object    $logger    Maintains the logger.
	 */
	protected $logger = null;

	/**
	 * Is the logger in test.
	 *
	 * @since  1.2.1
	 * @var    boolean    $in_test    Maintains the test status of the logger.
	 */
	protected $in_test = false;

	/**
	 * Is this listener a PSR-3 logger.
	 *
	 * @since  1.3.0
	 * @var    boolean    $psr3    Maintains the psr3 status of the logger.
	 */
	private $psr3 = false;

	/**
	 * Is logger allowed to run.
	 *
	 * @since  1.3.0
	 * @var    boolean    $allowed    Maintains the allowed status of the logger.
	 */
	private $allowed = true;

	/**
	 * Messages counter.
	 *
	 * @since  1.3.0
	 * @var    array    $counter    Maintains the messages counter.
	 */
	private static $counter = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $class The class identifier, must be in self::$classes.
	 * @param   string  $name Optional. The name of the component.
	 * @param   string  $version Optional. The version of the component.
	 * @param   string  $test Optional. The handler to create if specified.
	 * @param   boolean $psr3 Optional. True if this logger is a PSR-3 logger.
	 * @since   1.0.0
	 */
	public function __construct( $class, $name = null, $version = null, $test = null, $psr3 = false ) {
		if ( ! defined( 'DECALOG_TRACEID' ) ) {
			define( 'DECALOG_TRACEID', UUID::generate_unique_id( 32 ) );
		}
		if ( in_array( $class, ClassTypes::$classes, true ) ) {
			$this->class = $class;
		}
		if ( $name && is_string( $name ) ) {
			$this->name = $name;
		}
		if ( $version && is_string( $version ) ) {
			$this->version = $version;
		}
		$this->psr3 = $psr3;
		$this->init( $test );
		$this->debug( 'A new instance of DecaLog logger is initialized and operational.' );
	}

	/**
	 * Init the logger.
	 *
	 * @param   string $test Optional. The handler to init if specified.
	 * @since 1.0.0
	 */
	private function init( $test = null ) {
		if ( $this->psr3 ) {
			if ( ! Option::network_get( 'autolisteners' ) ) {
				$this->allowed = in_array( 'psr3', Option::network_get( 'listeners' ), true );
			}
		}
		$this->in_test = isset( $test );
		$factory       = new LoggerFactory();
		$this->logger  = new Logger( $this->current_channel_tag(), [], [], Timezone::network_get() );
		$handlers      = new HandlerTypes();
		$diagnosis     = new HandlerDiagnosis();
		$unloadable    = [];
		$skipped       = [];
		foreach ( $this->loggers_check() as $key => $logger ) {
			if ( $this->in_test && $key !== $test ) {
				continue;
			}
			$handler_def    = $handlers->get( $logger['handler'] );
			$logger['uuid'] = $key;
			if ( $handler_def && $diagnosis->check( $handler_def['id'] ) ) {
				$handler = $factory->create_logger( $logger );
				if ( $handler instanceof \DLMonolog\Handler\HandlerInterface ) {
					$this->logger->pushHandler( $handler );
				} elseif ( $logger['running'] ) {
					$skipped[] = sprintf( 'Skipping loading of a %s logger.', $handler_def['name'] );
				}
			} else {
				if ( $handler_def ) {
					$unloadable[] = sprintf( 'Unable to load a %s logger. %s', $handler_def['name'], $diagnosis->error_string( $handler_def['id'] ) );
				} else {
					$unloadable[] = 'Unable to load a logger.';
				}
			}
		}
		if ( count( $unloadable ) > 0 ) {
			foreach ( $unloadable as $item ) {
				$this->error( $item, 666 );
			}
		}
		if ( count( $skipped ) > 0 ) {
			foreach ( $skipped as $item ) {
				$this->debug( $item );
			}
		}
	}

	/**
	 * Check the loggers.
	 *
	 * @return  array   The logger list.
	 * @since 2.0.0
	 */
	private function loggers_check() {
		$loggers = Option::network_get( 'loggers' );
		// Verify shared memory logger
		if ( ! array_key_exists( DECALOG_SHM_ID, $loggers ) ) {
			$shm                       = [];
			$shm['name']               = __( 'System events-logger', 'decalog' );
			$shm['handler']            = 'SharedMemoryHandler';
			$shm['running']            = Option::network_get( 'livelog' );
			$shm['level']              = Logger::INFO;
			$shm['privacy']            = [
				'obfuscation'      => 0,
				'pseudonymization' => 0,
			];
			$shm['processors']         = [ 'WordpressProcessor', 'IntrospectionProcessor', 'WWWProcessor' ];
			$loggers[ DECALOG_SHM_ID ] = $shm;
			Option::network_set( 'loggers', $loggers );
		}
		return Option::network_get( 'loggers' );
	}

	/**
	 * Get the current channel tag.
	 *
	 * @return  string The current channel tag.
	 * @since 1.0.0
	 */
	private function current_channel_tag() {
		return $this->channel_tag( Environment::exec_mode() );
	}

	/**
	 * Get the channel tag.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel tag.
	 * @since 1.0.0
	 */
	public function channel_tag( $id = 0 ) {
		if ( $id >= count( ChannelTypes::$channels ) ) {
			$id = 0;
		}
		return ChannelTypes::$channels[ $id ];
	}

	/**
	 * Verify if DEBUG is allowed.
	 *
	 * @return  boolean True if DEBUG message are allowed, false otherwise.
	 * @since 1.0.0
	 */
	private function is_debug_allowed() {
		if ( ! Option::network_get( 'respect_wp_debug' ) ) {
			return true;
		}
		if ( defined( 'WP_DEBUG' ) ) {
			return WP_DEBUG;
		}
		return true;
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
		$string = str_replace( '>=', '≥', $string );
		$string = str_replace( '<=', '≤', $string );
		return filter_var( $string, FILTER_SANITIZE_STRING );
	}

	/**
	 * Normalize an array.
	 *
	 * @param mixed  $array The array.
	 * @return mixed   The normalized array.
	 * @since 1.10.0+
	 */
	private function normalize_array( $array ) {
		array_walk_recursive(
			$array,
			function ( &$item, $key ) {
				if ( is_string( $item ) ) {
					$item = $this->normalize_string( $item );
				} }
		);
		return $array;
	}

	/**
	 * Normalize an array.
	 *
	 * @param mixed    $level   The log level.
	 * @return  integer The counter for this level.
	 * @since 1.10.0+
	 */
	public static function count( $level ) {
		if ( is_string( $level ) ) {
			$level = EventTypes::$levels[ strtolower( $level ) ];
		}
		if ( ! array_key_exists( $level, self::$counter ) ) {
			return 0;
		}
		return self::$counter[ $level ];
	}

	/**
	 * Adds a log record at a specific level.
	 *
	 * @param mixed    $level   The log level.
	 * @param string   $message The log message.
	 * @param integer  $code    Optional. The log code.
	 * @param string   $phase   Optional. The log phase.
	 * @param boolean  $signal  Optional. Add .
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function log( $level, $message, $code = 0, $phase = '', $signal = true ) {
		if ( is_string( $level ) ) {
			$level = EventTypes::$levels[ strtolower( $level ) ];
		}
		if ( ! array_key_exists( $level, self::$counter ) ) {
			self::$counter[ $level ] = 0;
		}
		self::$counter[ $level ]++;
		if ( ! $this->allowed ) {
			return false;
		}
		$debug = ( Logger::DEBUG === $level || 100 === $level || 'DEBUG' === strtoupper( (string) $level ) );
		if ( $debug && ! $this->is_debug_allowed() ) {
			return false;
		}
		try {
			$context = [
				'class'       => (string) $this->class,
				'component'   => (string) $this->name,
				'version'     => (string) $this->version,
				'phase'       => (string) $phase,
				'code'        => (int) $code,
				'environment' => (string) Environment::stage(),
				'traceID'     => (string) DECALOG_TRACEID,
				'instance'    => (string) DECALOG_INSTANCE_NAME,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$result = true;
			// phpcs:ignore
			set_error_handler( function () use (&$result) {$result = false;} );
			$this->logger->log( $level, $this->normalize_string( $message ), $this->normalize_array( $context ) );
			// phpcs:ignore
			restore_error_handler();
		} catch ( \Throwable $t ) {
			$result = false;
		} finally {
			if ( $signal && ! $result && ! $debug ) {
				//$this->log( Logger::ALERT, 'error', 666, '', false );
			}
			return $result;
		}
	}

	/**
	 * Adds a log record at the DEBUG level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function debug( $message, $code = 0 ) {
		return $this->log( Logger::DEBUG, $message, $code );
	}

	/**
	 * Adds a log record at the INFO level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function info( $message, $code = 0 ) {
		return $this->log( Logger::INFO, $message, $code );
	}

	/**
	 * Adds a log record at the NOTICE level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function notice( $message, $code = 0 ) {
		return $this->log( Logger::NOTICE, $message, $code );
	}

	/**
	 * Adds a log record at the WARNING level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function warning( $message, $code = 0 ) {
		return $this->log( Logger::WARNING, $message, $code );
	}

	/**
	 * Adds a log record at the ERROR level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function error( $message, $code = 0 ) {
		return $this->log( Logger::ERROR, $message, $code );
	}

	/**
	 * Adds a log record at the CRITICAL level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function critical( $message, $code = 0 ) {
		return $this->log( Logger::CRITICAL, $message, $code );
	}

	/**
	 * Adds a log record at the ALERT level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function alert( $message, $code = 0 ) {
		return $this->log( Logger::ALERT, $message, $code );
	}

	/**
	 * Adds a log record at the EMERGENCY level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @return  boolean     True if message was logged, false otherwise.
	 * @since 1.0.0
	 */
	public function emergency( $message, $code = 0 ) {
		return $this->log( Logger::EMERGENCY, $message, $code );
	}

}
