<?php
/**
 * DecaLog logger definition.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\API;

use Monolog\Logger;
use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\Plugin\Feature\LoggerFactory;



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
	 * The list of available channels.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array    $channels    Maintains the channels definitions.
	 */
	protected $channels = [
		[
			'tag'  => 'UNKNOWN',
			'name' => 'Unknown',
		],
		[
			'tag'  => 'CLI',
			'name' => 'Command Line Interface',
		],
		[
			'tag'  => 'CRON',
			'name' => 'Cron Job',
		],
		[
			'tag'  => 'AJAX',
			'name' => 'Ajax Request',
		],
		[
			'tag'  => 'XMLRPC',
			'name' => 'XML-RPC Request',
		],
		[
			'tag'  => 'API',
			'name' => 'Rest API Request',
		],
		[
			'tag'  => 'FEED',
			'name' => 'Atom/RDF/RSS Feed',
		],
		[
			'tag'  => 'WBACK',
			'name' => 'Site Backend',
		],
		[
			'tag'  => 'WFRONT',
			'name' => 'Site Frontend',
		],
	];

	/**
	 * The list of available classes.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array    $classes    Maintains the classes list.
	 */
	protected $classes = [ 'plugin', 'theme' ];

	/**
	 * The class of the component.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $class    Maintains the class of the component.
	 */
	protected $class = 'other';

	/**
	 * The name of the component.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $class    Maintains the name of the component.
	 */
	protected $name = 'unknown';

	/**
	 * The version of the component.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $version    Maintains the version of the component.
	 */
	protected $version = '-';

	/**
	 * The monolog logger.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    object    $logger    Maintains the logger.
	 */
	protected $logger = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $class The class identifier, must be in self::$classes.
	 * @param   string $name Optional. The name of the component.
	 * @param   string $version Optional. The version of the component.
	 * @since   1.0.0
	 */
	public function __construct( $class, $name = null, $version = null ) {
		if ( in_array( $class, $this->classes ) ) {
			$this->class = $class;
		}
		if ( $name && is_string( $name ) ) {
			$this->name = $name;
		}
		if ( $version && is_string( $version ) ) {
			$this->version = $version;
		}
		$this->init();
		$this->debug( 'A new instance of DecaLog logger is initialized and operational.' );
	}



	/**
	 * Init the logger.
	 *
	 * @since 1.0.0
	 */
	private function init() {
		$factory = new LoggerFactory();
		$this->logger = new Logger( $this->current_channel_tag() );
		foreach ( Option::get('loggers') as $logger ) {
			$handler = $factory->create_logger($logger);
			if ($handler) {
				$this->logger->pushHandler( $handler );
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
		if ( ! array_key_exists( $id, $this->channels ) ) {
			$id = 0;
		}
		return $this->channels[ $id ]['tag'];
	}

	/**
	 * Get the channel name.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel name.
	 * @since 1.0.0
	 */
	public function channel_name( $id = 0 ) {
		if ( ! array_key_exists( $id, $this->channels ) ) {
			$id = 0;
		}
		return $this->channels[ $id ]['name'];
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
	 * Adds a log record at the DEBUG level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function debug( $message, $code = 0 ) {
		if ( $this->is_debug_allowed() ) {
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
			$this->logger->debug( $message, $context );
		}
	}

	/**
	 * Adds a log record at the INFO level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function info( $message, $code = 0 ) {
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
		$this->logger->info( $message, $context );
	}

	/**
	 * Adds a log record at the NOTICE level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function notice( $message, $code = 0 ) {
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
		$this->logger->notice( $message, $context );
	}

	/**
	 * Adds a log record at the WARNING level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function warning( $message, $code = 0 ) {
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
		$this->logger->warning( $message, $context );
	}

	/**
	 * Adds a log record at the ERROR level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function error( $message, $code = 0 ) {
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
		$this->logger->error( $message, $context );
	}

	/**
	 * Adds a log record at the CRITICAL level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function critical( $message, $code = 0 ) {
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
		$this->logger->critical( $message, $context );
	}

	/**
	 * Adds a log record at the ALERT level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function alert( $message, $code = 0 ) {
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
		$this->logger->alert( $message, $context );
	}

	/**
	 * Adds a log record at the EMERGENCY level.
	 *
	 * @param string  $message The log message.
	 * @param integer $context Optional. The log code.
	 * @since 1.0.0
	 */
	public function emergency( $message, $code = 0 ) {
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
		$this->logger->emergency( $message, $context );
	}

}
