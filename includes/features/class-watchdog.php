<?php
/**
 * Watchdog for DecaLog.
 *
 * This listener is used in case of 'PhpListener' deactivation to
 * allow class banning.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.2.1
 */

namespace Decalog\Plugin\Feature;

use Decalog\Plugin\Feature\DLogger;

/**
 * Watchdog for DecaLog.
 *
 * Defines methods and properties for watchdog class.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.2.1
 */
class Watchdog {

	/**
	 * The unique instance of the class.
	 *
	 * @since  1.2.1
	 * @var self $instance The unique instance of the class.
	 */
	private static $instance;

	/**
	 * The previous error handler, to restore if needed.
	 *
	 * @since  1.2.1
	 * @var callable $previous_error_handler The previous error handler.
	 */
	private $previous_error_handler;

	/**
	 * The previous exception handler, to restore if needed.
	 *
	 * @since  1.2.1
	 * @var callable $previous_exception_handler The previous exception handler.
	 */
	private $previous_exception_handler;

	/**
	 * Create the class instance.
	 *
	 * @since    1.2.1
	 */
	public static function init() {
		self::$instance = new Watchdog();
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.2.1
	 */
	public function __construct() {
		// phpcs:ignore
		$this->previous_error_handler = set_error_handler( [ $this, 'handle_error' ] );
		// phpcs:ignore
		$this->previous_exception_handler = set_exception_handler( [ $this, 'handle_exception' ] );
	}

	/**
	 * Handles errors.
	 *
	 * @param   integer $code The error code.
	 * @param   string  $message The error message.
	 * @param   string  $file The file where the error was raised.
	 * @param   integer $line The line where the error was raised.
	 * @param   array   $context The context of the error.
	 * @since    1.2.1
	 */
	public function handle_error( $code, $message, $file = '', $line = 0, $context = [] ) {
		DLogger::ban( $file, $message );
		if ( $this->previous_error_handler && is_callable( $this->previous_error_handler ) ) {
			return call_user_func( $this->previous_error_handler, $code, $message, $file, $line, $context );
		} else {
			return false;
		}
	}

	/**
	 * Handles errors.
	 *
	 * @param   \Exception $exception  The uncaught exception.
	 * @since    1.2.1
	 */
	public function handle_exception( $exception ) {
		DLogger::ban( $exception->getFile(), $exception->getMessage() );
		if ( $this->previous_exception_handler && is_callable( $this->previous_exception_handler ) ) {
			call_user_func( $this->previous_exception_handler, $exception );
		} else {
			exit( 254 );
		}
	}
}

Watchdog::init();
