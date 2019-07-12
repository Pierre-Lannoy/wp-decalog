<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

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
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function initialize() {
		// create a log channel
		$log = new Logger('TEST');
		$handler = new ErrorLogHandler();
		$handler->pushProcessor(new WordpressProcessor(true, true));
		//$handler->pushProcessor(new WebProcessor());
		//$handler->pushProcessor(new IntrospectionProcessor());
		$log->pushHandler($handler);

		// add records to the log
		$log->warning('FooFooFooFoo'/*, array('username' => 'Seldaek')*/);
		$log->error('BarBarBarBar');
	}

}
