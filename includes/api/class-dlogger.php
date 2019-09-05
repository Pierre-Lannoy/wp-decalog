<?php
/**
 * DecaLog logger definition.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\API;

use Decalog\Plugin\Feature\HandlerTypes;
use Monolog\Logger;
use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\System\Timezone;
use Decalog\Plugin\Feature\LoggerFactory;
use Decalog\Plugin\Feature\ClassTypes;
use Decalog\Plugin\Feature\ChannelTypes;
use Decalog\Plugin\Feature\HandlerDiagnosis;

/**
 * Main DecaLog logger class.
 *
 * This class defines all code necessary to log events with DecaLog.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class DLogger {

	/**
	 * The banned classes.
	 *
	 * @since  1.0.0
	 * @var    array    $banned    Maintains the list of banned classes.
	 */
	private static $banned = [];

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
	 * The bannissable extra classes.
	 *
	 * @since  1.0.0
	 * @var    array    $bannissable    Maintains the bannissable extra classes.
	 */
	private static $bannissable = [ 'ssl://api.pushover.net' => 'pshhandler' ];

	/**
	 * Temporarily ban a class.
	 *
	 * @param  string $classname The class name to ban.
	 * @param  string $message Optional. The message of the initial error.
	 * @since 1.0.0
	 */
	public static function ban( $classname, $message = '' ) {
		if ( '' !== $message ) {
			foreach ( self::$bannissable as $key => $val ) {
				if ( false !== strpos( $message, $key ) ) {
					if ( ! in_array( $val, self::$banned, true ) ) {
						self::$banned[] = $val;
					}
				}
			}
		}
		while ( false !== strpos( $classname, '/' ) ) {
			$classname = substr( $classname, strpos( $classname, '/' ) + 1 );
		}
		$classname = str_replace( '.php', '', strtolower( $classname ) );
		if ( ! in_array( $classname, self::$banned, true ) ) {
			self::$banned[] = $classname;
		}
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $class The class identifier, must be in self::$classes.
	 * @param   string $name Optional. The name of the component.
	 * @param   string $version Optional. The version of the component.
	 * @param   string $test Optional. The handler to create if specified..
	 * @since   1.0.0
	 */
	public function __construct( $class, $name = null, $version = null, $test = null ) {
		if ( in_array( $class, ClassTypes::$classes, true ) ) {
			$this->class = $class;
		}
		if ( $name && is_string( $name ) ) {
			$this->name = $name;
		}
		if ( $version && is_string( $version ) ) {
			$this->version = $version;
		}
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
		$this->in_test = isset( $test );
		$factory       = new LoggerFactory();
		$this->logger  = new Logger( $this->current_channel_tag(), [], [], Timezone::get_wp() );
		$handlers      = new HandlerTypes();
		$diagnosis     = new HandlerDiagnosis();
		$banned        = [];
		$unloadable    = [];
		foreach ( Option::get( 'loggers' ) as $key => $logger ) {
			if ( $this->in_test && $key !== $test ) {
				continue;
			}
			$handler_def    = $handlers->get( $logger['handler'] );
			$logger['uuid'] = $key;
			if ( $this->in_test || ( ! in_array( strtolower( $handler_def['ancestor'] ), self::$banned, true ) && ! in_array( strtolower( $handler_def['id'] ), self::$banned, true ) ) ) {
				if ( $diagnosis->check( $handler_def['id'] ) ) {
					$handler = $factory->create_logger( $logger );
					if ( $handler ) {
						$this->logger->pushHandler( $handler );
					}
				} else {
					$unloadable[] = sprintf( 'Unable to load a %s logger. %s', $handler_def['name'], $diagnosis->error_string( $handler_def['id'] ) );
				}
			} else {
				$banned[] = $handler_def['name'];
			}
		}
		if ( count( $banned ) > 0 ) {
			// phpcs:ignore
			$this->critical( sprintf ('Due to DecaLog internal errors, the following logger types have been temporarily deactivated: %s.', implode(', ', $banned ) ), 666 );
		}
		if ( count( $unloadable ) > 0 ) {
			foreach ( $unloadable as $item ) {
				$this->error( $item, 666 );
			}
		}
	}

	/**
	 * Check the integrity of the logger.
	 *
	 * @since 1.0.0
	 */
	private function integrity_check() {
		if ( count( self::$banned ) > 0 && ! $this->in_test ) {
			$handlers = new HandlerTypes();
			$banned   = [];
			foreach ( $this->logger->getHandlers() as $handler ) {
				$classname = get_class( $handler );
				while ( false !== strpos( $classname, '\\' ) ) {
					$classname = substr( $classname, strpos( $classname, '\\' ) + 1 );
				}
				$handler_def = $handlers->get( $classname );
				$ancestor    = $handler_def['ancestor'];
				if ( in_array( strtolower( $classname ), self::$banned, true ) || in_array( strtolower( $ancestor ), self::$banned, true ) ) {
					$this->logger->popHandler( $handler );
					$banned[] = $handler_def['name'];
				}
			}
			if ( count( $banned ) > 0 ) {
				// phpcs:ignore
				$this->critical( sprintf ('Due to DecaLog internal errors, the following logger types have been temporarily deactivated: %s.', implode(', ', $banned ) ), 666 );
			}
		}
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
		if ( ! Option::get( 'respect_wp_debug' ) ) {
			return true;
		}
		if ( defined( 'WP_DEBUG' ) ) {
			return WP_DEBUG;
		}
		return true;
	}

	/**
	 * Adds a log record at a specific level.
	 *
	 * @param mixed   $level The log level.
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function log( $level, $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->log( $level, filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

	/**
	 * Adds a log record at the DEBUG level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function debug( $message, $code = 0 ) {
		if ( $this->is_debug_allowed() ) {
			try {
				$context = [
					'class'     => (string) $this->class,
					'component' => (string) $this->name,
					'version'   => (string) $this->version,
					'code'      => (int) $code,
				];
				$channel = $this->current_channel_tag();
				if ( $this->logger->getName() !== $channel ) {
					$this->logger = $this->logger->withName( $channel );
				}
				$this->logger->debug( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
				$result = true;
			} catch ( \Throwable $t ) {
				$this->integrity_check();
				$result = false;
			} finally {
				return $result;
			}
		}
	}

	/**
	 * Adds a log record at the INFO level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function info( $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->info( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

	/**
	 * Adds a log record at the NOTICE level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function notice( $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->notice( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

	/**
	 * Adds a log record at the WARNING level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function warning( $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->warning( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

	/**
	 * Adds a log record at the ERROR level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function error( $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->error( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

	/**
	 * Adds a log record at the CRITICAL level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function critical( $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->critical( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

	/**
	 * Adds a log record at the ALERT level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function alert( $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->alert( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

	/**
	 * Adds a log record at the EMERGENCY level.
	 *
	 * @param string  $message The log message.
	 * @param integer $code Optional. The log code.
	 * @since 1.0.0
	 */
	public function emergency( $message, $code = 0 ) {
		try {
			$context = [
				'class'     => (string) $this->class,
				'component' => (string) $this->name,
				'version'   => (string) $this->version,
				'code'      => (int) $code,
			];
			$channel = $this->current_channel_tag();
			if ( $this->logger->getName() !== $channel ) {
				$this->logger = $this->logger->withName( $channel );
			}
			$this->logger->emergency( filter_var( $message, FILTER_SANITIZE_STRING ), $context );
			$result = true;
		} catch ( \Throwable $t ) {
			$this->integrity_check();
			$result = false;
		} finally {
			return $result;
		}
	}

}
