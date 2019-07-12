<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\System\Environment;

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

use Monolog\Processor\IntrospectionProcessor;
use Decalog\Processor\WWWProcessor;
use Decalog\Processor\WordpressProcessor;


/**
 * Fired after 'init' hook.
 *
 * This class defines all code necessary to run during the plugin's initialization.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Initializer {

	/**
	 * The list of available channels.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array    $channel    Maintains the channels definitions.
	 */
	protected static $channel = [
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
		]
	];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   boolean $self_register Optional. Self register after instanciation.
	 * @since   1.0.0
	 */
	public function __construct( $self_register = true ) {
		if ( $self_register ) {
			$this->self_register();
		}
	}

	/**
	 * Start the loggers.
	 *
	 * @since 1.0.0
	 */
	public function start() {
		// create a log channel
		$log = new Logger( $this->current_channel_tag() );

		$handler = new ErrorLogHandler();
		$handler->pushProcessor( new WordpressProcessor() );
		//$handler->pushProcessor(new WWWProcessor());
		// $handler->pushProcessor(new IntrospectionProcessor());
		$log->pushHandler( $handler );

		// add records to the log
		$log->warning( 'FooFooFooFoo'/*, array('username' => 'Seldaek')*/ );
		$log->error( 'BarBarBarBar' );
	}

	/**
	 * Get the current channel tag.
	 *
	 * @return  string The currentchannel id.
	 * @since 1.0.0
	 */
	private function current_channel_tag() {
		return $this->channel_tag( Environment::exec_mode() );
	}

	/**
	 * Self register Decalog as source.
	 *
	 * @since 1.0.0
	 */
	public function self_register() {
		/*
		 create a log channel
		$log = new Logger('TEST');
		$handler = new ErrorLogHandler();
		$handler->pushProcessor(new WordpressProcessor(true, true));
		//$handler->pushProcessor(new WebProcessor());
		//$handler->pushProcessor(new IntrospectionProcessor());
		$log->pushHandler($handler);

		// add records to the log
		$log->warning('FooFooFooFoo'/*, array('username' => 'Seldaek')/);
		$log->error('BarBarBarBar');*/
	}

	/**
	 * Get the channel tag.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel tag.
	 * @since 1.0.0
	 */
	public function channel_tag( $id = 0 ) {
		if (!array_key_exists($id, self::$channel)) {
			$id = 0;
		}
		return self::$channel[$id]['tag'];
	}

	/**
	 * Get the channel name.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel name.
	 * @since 1.0.0
	 */
	public function channel_name( $id = 0 ) {
		if (!array_key_exists($id, self::$channel)) {
			$id = 0;
		}
		return self::$channel[$id]['name'];
	}

}
