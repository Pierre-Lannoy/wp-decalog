<?php
/**
 * Special PHP listener for DecaLog.
 *
 * This listener is used in case of 'PhpListener' deactivation to
 * allow class banning.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

use Decalog\API\DLogger;
use Decalog\Log;

/**
 * Special PHP listener for DecaLog.
 *
 * Defines methods and properties for special PHP listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class SelfListener extends AbstractListener {

	/**
	 * The previous error handler, to restore if needed.
	 *
	 * @since  1.0.0
	 * @var callable $previous_error_handler The previous error handler.
	 */
	private $previous_error_handler;

	/**
	 * The previous exception handler, to restore if needed.
	 *
	 * @since  1.0.0
	 * @var callable $previous_exception_handler The previous exception handler.
	 */
	private $previous_exception_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param    DLogger $internal_logger    An instance of DLogger to log internal events.
	 * @since    1.0.0
	 */
	public function __construct( $internal_logger ) {
		parent::__construct( $internal_logger );
		$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$this->logger->debug( 'Fallback listener is launched and operational.' );
	}

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = '';
		$this->name    = '';
		$this->class   = 'plugin';
		$this->product = DECALOG_PRODUCT_SHORTNAME;
		$this->version = DECALOG_VERSION;
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.0.0
	 */
	protected function is_available() {
		return true;
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	protected function launch() {
		register_shutdown_function( [ $this, 'handle_fatal_error' ] );
		// phpcs:ignore
		$this->previous_error_handler = set_error_handler( [ $this, 'handle_error' ] );
		// phpcs:ignore
		$this->previous_exception_handler = set_exception_handler( [ $this, 'handle_exception' ] );
		return true;
	}

	/**
	 * Handles fatal errors.
	 *
	 * @since    1.0.0
	 */
	public function handle_fatal_error() {
		$last_error = error_get_last();
		DLogger::ban( str_replace( '.php', '', strtolower( $last_error['file'] ) ), $last_error['message'] );
	}

	/**
	 * Handles errors.
	 *
	 * @param   integer $code The error code.
	 * @param   string  $message The error message.
	 * @param   string  $file The file where the error was raised.
	 * @param   integer $line The line where the error was raised.
	 * @param   array   $context The context of the error.
	 * @since    1.0.0
	 */
	public function handle_error( $code, $message, $file = '', $line = 0, $context = [] ) {
		DLogger::ban( str_replace( '.php', '', strtolower( $file ) ), $message );
		if ( $this->previous_error_handler && is_callable( $this->previous_error_handler ) ) {
			return call_user_func( $this->previous_error_handler, $code, $message, $file, $line, $context );
		} else {
			return true;
		}
	}

	/**
	 * Handles errors.
	 *
	 * @param   Exception $exception  The uncaught exception.
	 * @since    1.0.0
	 */
	public function handle_exception( $exception ) {
		DLogger::ban( str_replace( '.php', '', strtolower( $exception->getFile() ) ), $exception->getMessage() );
		if ( $this->previous_exception_handler && is_callable( $this->previous_exception_handler ) ) {
			return call_user_func( $this->previous_exception_handler, $exception );
		} else {
			exit( 255 );
		}
	}
}
