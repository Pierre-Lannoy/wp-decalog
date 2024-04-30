<?php
/**
 * PHP listener for DecaLog.
 *
 * Defines class for PHP listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

use Decalog\Plugin\Feature\DLogger;
use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\System\PHP;
use DLMonolog\Logger;
use DLMonolog\Utils;

/**
 * PHP listener for DecaLog.
 *
 * Defines methods and properties for PHP listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class PhpListener extends AbstractListener {

	/**
	 * The error level mapping.
	 *
	 * @since  1.0.0
	 * @var array $error_level_map List of mappings.
	 */
	private $error_level_map = [
		E_ERROR             => Logger::EMERGENCY,
		E_PARSE             => Logger::EMERGENCY,
		E_CORE_ERROR        => Logger::EMERGENCY,
		E_COMPILE_ERROR     => Logger::EMERGENCY,
		E_USER_ERROR        => Logger::ERROR,
		E_RECOVERABLE_ERROR => Logger::ERROR,
		E_CORE_WARNING      => Logger::WARNING,
		E_WARNING           => Logger::WARNING,
		E_COMPILE_WARNING   => Logger::WARNING,
		E_USER_WARNING      => Logger::WARNING,
		E_NOTICE            => Logger::NOTICE,
		E_USER_NOTICE       => Logger::NOTICE,
		E_STRICT            => Logger::NOTICE,
		E_DEPRECATED        => Logger::INFO,
		E_USER_DEPRECATED   => Logger::INFO,
	];

	/**
	 * The exception level mapping.
	 *
	 * @since  1.0.0
	 * @var array $exception_level_map List of mappings.
	 */
	private $exception_level_map = [
		'ParseError' => Logger::EMERGENCY,
		'Throwable'  => Logger::ERROR,
	];

	/**
	 * The fatal errors levels.
	 *
	 * @since  1.0.0
	 * @var array $fatal_errors List of fatal errors.
	 */
	private $fatal_errors = [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ];

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
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'php';
		$this->name    = 'PHP';
		$this->class   = 'php';
		$this->product = 'PHP';
		$this->version = Environment::php_version();
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
		add_action( 'wp_loaded', [ $this, 'version_check' ] );
		add_action( 'wp_loaded', [ $this, 'extensions_check' ] );
		add_action( 'wp_loaded', [ $this, 'opcache_check' ] );
		if ( defined( 'DECALOG_BOOTSTRAPPED' ) && DECALOG_BOOTSTRAPPED ) {
			if ( defined( 'DECALOG_EARLY_INIT' ) && DECALOG_EARLY_INIT ) {
				add_action( 'setup_theme', [ $this, 'bootstrap_check' ] );
			} else {
				add_action( 'sanitize_comment_cookies', [ $this, 'bootstrap_check' ] );
			}
		}
		if ( ! Environment::is_sandboxed() ) {
			add_action( 'shutdown', [ $this, 'handle_fatal_error' ], 10, 0 );
			// phpcs:ignore
			$this->previous_error_handler = set_error_handler( [ $this, 'handle_error' ] );
			// phpcs:ignore
			$this->previous_exception_handler = set_exception_handler( [ $this, 'handle_exception' ] );
		}
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		if ( Environment::is_sandboxed() ) {
			$this->logger->warning( 'The current request is sandboxed. PHP errors and exceptions will not be reported until the end of this request.' );
		} else {
			$this->monitor->create_dev_counter( 'error_fatal', 'Number of fatal errors per request - [count]' );
			$this->monitor->create_dev_counter( 'error_nonfatal', 'Number of non fatal errors per request - [count]' );
			$this->monitor->create_dev_counter( 'exception_uncaught', 'Number of uncaught exceptions per request - [count]' );
			$this->monitor->create_dev_gauge( 'extension', 0, 'Number of loaded extensions - [count]' );
			$this->monitor->create_dev_gauge( 'execution_latency', 0, 'Total PHP execution time per request - [second]' );
		}
	}

	/**
	 * Check versions modifications.
	 *
	 * @since    1.2.0
	 */
	public function version_check() {
		if ( 1 === Environment::exec_mode() ) {
			$prefix = DECALOG_INSTANCE_NAME . '_command_line_';
			$name   = DECALOG_INSTANCE_NAME . ' command-line';
		} else {
			$prefix = DECALOG_INSTANCE_NAME . '_web_server_';
			$name   = DECALOG_INSTANCE_NAME . ' web server';
		}
		$php_version = Environment::php_version();
		$old_version = Option::network_get( $prefix . 'php_version', 'x' );
		if ( 'x' === $old_version ) {
			Option::network_set( $prefix . 'php_version', $php_version );
			return;
		}
		if ( $php_version === $old_version ) {
			return;
		}
		Option::network_set( $prefix . 'php_version', $php_version );
		if ( version_compare( $php_version, $old_version, '<' ) ) {
			$this->logger->warning( sprintf( 'PHP version for %s downgraded from %s to %s.', $name, $old_version, $php_version ) );
			return;
		}
		$this->logger->notice( sprintf( 'PHP version for %s upgraded from %s to %s.', $name, $old_version, $php_version ) );
	}

	/**
	 * Check versions modifications.
	 *
	 * @since    2.4.0
	 */
	public function bootstrap_check() {
		global $dclg_btsrp;
		if ( is_array( $dclg_btsrp ) ) {
			foreach ( $dclg_btsrp as $event ) {
				$this->logger->log( $event['level'] ?? 550, $event['message'] ?? 'Unknown error', $event['code'] ?? 0, 'bootstrap' );
			}
		} else {
			// phpcs:ignore
			$this->logger->debug( 'No data in bootstrap array.' );
		}
	}

	/**
	 * Check extensions modifications.
	 *
	 * @since    1.2.0
	 */
	public function extensions_check() {
		if ( 1 === Environment::exec_mode() ) {
			$prefix = DECALOG_INSTANCE_NAME . '_command_line_';
			$name   = DECALOG_INSTANCE_NAME . ' command-line configuration';
		} else {
			$prefix = DECALOG_INSTANCE_NAME . '_web_server_';
			$name   = DECALOG_INSTANCE_NAME . ' web server configuration';
		}
		$old_extensions = Option::network_get( $prefix . 'php_extensions', 'x' );
		$new_extensions = get_loaded_extensions();
		$this->monitor->set_dev_gauge( 'extension', count( $new_extensions ) );
		if ( 'x' === $old_extensions ) {
			Option::network_set( $prefix . 'php_extensions', $new_extensions );
			return;
		}
		if ( $new_extensions === $old_extensions ) {
			return;
		}
		Option::network_set( $prefix . 'php_extensions', $new_extensions );
		$added   = array_diff( $new_extensions, $old_extensions );
		$removed = array_diff( $old_extensions, $new_extensions );
		if ( count( $added ) > 0 ) {
			$this->logger->notice( sprintf( 'Added PHP extension(s) to %s : %s.', $name, implode( ', ', $added ) ) );
		}
		if ( count( $removed ) > 0 ) {
			$this->logger->warning( sprintf( 'Removed PHP extension(s) to %s : %s.', $name, implode( ', ', $removed ) ) );
		}
	}

	/**
	 * Check extensions modifications.
	 *
	 * @since    1.6.0
	 */
	public function opcache_check() {
		if ( 1 === Environment::exec_mode() ) {
			$prefix = DECALOG_INSTANCE_NAME . '_command_line_';
			$name   = DECALOG_INSTANCE_NAME . ' command-line';
		} else {
			$prefix = DECALOG_INSTANCE_NAME . '_web_server_';
			$name   = DECALOG_INSTANCE_NAME . ' web server';
		}
		$old_opcache = Option::network_get( $prefix . 'php_opcache', 'x' );
		if ( function_exists( 'opcache_get_status' ) ) {
			// phpcs:ignore
			set_error_handler( null );
			// phpcs:ignore
			$new_opcache = @opcache_get_status( false );
			// phpcs:ignore
			restore_error_handler();
			if ( ! is_array( $new_opcache ) ) {
				$new_opcache = [];
			}
			Option::network_set( $prefix . 'php_opcache', $new_opcache );
			if ( 'x' === $old_opcache || $new_opcache === $old_opcache || ! is_array( $old_opcache ) || ! is_array( $new_opcache ) ) {
				return;
			}
			if ( array_key_exists( 'cache_full', $old_opcache ) && ! (bool) $old_opcache['cache_full'] && array_key_exists( 'cache_full', $new_opcache ) && (bool) $new_opcache['cache_full'] ) {
				$this->logger->error( sprintf( 'OPcache for %s is full.', $name ) );
			}
			if ( array_key_exists( 'restart_pending', $old_opcache ) && ! (bool) $old_opcache['restart_pending'] && array_key_exists( 'restart_pending', $new_opcache ) && (bool) $new_opcache['restart_pending'] ) {
				$this->logger->notice( sprintf( 'OPcache for %s ready to restart.', $name ) );
			}
			if ( array_key_exists( 'restart_in_progress', $old_opcache ) && ! (bool) $old_opcache['restart_in_progress'] && array_key_exists( 'restart_in_progress', $new_opcache ) && (bool) $new_opcache['restart_in_progress'] ) {
				$this->logger->notice( sprintf( 'OPcache for %s restart in progress.', $name ) );
			}
			if ( array_key_exists( 'opcache_statistics', $old_opcache ) && array_key_exists( 'opcache_statistics', $new_opcache ) && array_key_exists( 'oom_restarts', $old_opcache['opcache_statistics'] ) && array_key_exists( 'oom_restarts', $new_opcache['opcache_statistics'] ) ) {
				if ( (int) $old_opcache['opcache_statistics']['oom_restarts'] !== (int) $new_opcache['opcache_statistics']['oom_restarts'] ) {
					$this->logger->warning( sprintf( 'OPcache for %s restarted due to lack of free memory or cache fragmentation.', $name ) );
				}
			}
			if ( array_key_exists( 'opcache_statistics', $old_opcache ) && array_key_exists( 'opcache_statistics', $new_opcache ) && array_key_exists( 'hash_restarts', $old_opcache['opcache_statistics'] ) && array_key_exists( 'hash_restarts', $new_opcache['opcache_statistics'] ) ) {
				if ( (int) $old_opcache['opcache_statistics']['hash_restarts'] !== (int) $new_opcache['opcache_statistics']['hash_restarts'] ) {
					$this->logger->warning( sprintf( 'OPcache for %s restarted due to lack of free key.', $name ) );
				}
			}
			if ( array_key_exists( 'opcache_statistics', $old_opcache ) && array_key_exists( 'opcache_statistics', $new_opcache ) && array_key_exists( 'manual_restarts', $old_opcache['opcache_statistics'] ) && array_key_exists( 'manual_restarts', $new_opcache['opcache_statistics'] ) ) {
				if ( (int) $old_opcache['opcache_statistics']['manual_restarts'] !== (int) $new_opcache['opcache_statistics']['manual_restarts'] ) {
					$this->logger->warning( sprintf( 'OPcache for %s restarted due to an external query.', $name ) );
				}
			}
		}
	}

	/**
	 * Handles fatal errors.
	 *
	 * @since    1.0.0
	 */
	public function handle_fatal_error() {
		$last_error = error_get_last();
		if ( isset( $last_error ) && is_array( $last_error ) ) {
			if ( in_array( $last_error['type'], $this->fatal_errors, true ) ) {
				$file    = PHP::normalized_file_line( $last_error['file'], $last_error['line'] );
				$message = sprintf( 'Fatal error (%s): "%s" at `%s`.', $this->code_to_string( $last_error['type'] ), $last_error['message'], $file );
				$this->logger->alert( $message, (int) $last_error['type'] );
				$this->monitor->inc_dev_counter( 'error_fatal', 1 );
			}
		}
	}

	/**
	 * Handles errors.
	 *
	 * @param   integer $code The error code.
	 * @param   string  $message The error message.
	 * @param   string  $file The file where the error was raised.
	 * @param   integer $line The line where the error was raised.
	 * @param   array   $context The context of the error.
	 * @return mixed|false  The result of the previous handler if any, or false.
	 * @since    1.0.0
	 */
	public function handle_error( $code, $message, $file = '', $line = 0, $context = [] ) {
		if ( ! in_array( $code, $this->fatal_errors, true ) ) {
			/**
			 * Filters the error levels map
			 *
			 * @See https://github.com/Pierre-Lannoy/wp-decalog/blob/master/HOOKS.md
			 * @since 3.11.0
			 * @param   array   $levels       The current map
			 */
			$map = apply_filters( 'decalog_error_level_map', $this->error_level_map );
			$level = $map[ $code ] ?? Logger::CRITICAL;
			$file    = PHP::normalized_file_line( $file, $line );
			$message = sprintf( 'Error (%s): "%s" at `%s`.', $this->code_to_string( $code ), $message, $file );
			$this->logger->log( $level, $message, (int) $code );
			if ( in_array( $code, $this->fatal_errors, true ) ) {
				$this->monitor->inc_dev_counter( 'error_fatal', 1 );
			} else {
				$this->monitor->inc_dev_counter( 'error_nonfatal', 1 );
			}
		}
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
	 * @since    1.0.0
	 */
	public function handle_exception( $exception ) {
		$file    = PHP::normalized_file_line( $exception->getFile(), $exception->getLine() );
		$message = sprintf( 'Uncaught exception (%s): "%s" at `%s`.', Utils::getClass( $exception ), $exception->getMessage(), $file );
		$this->logger->error( $message, (int) $exception->getCode() );
		$this->monitor->inc_dev_counter( 'exception_uncaught', 1 );
		if ( $this->previous_exception_handler && is_callable( $this->previous_exception_handler ) ) {
			call_user_func( $this->previous_exception_handler, $exception );
		} else {
			exit( 255 );
		}
	}

	/**
	 * Get the PHP error.
	 *
	 * @param   integer $code   The original PHP error code.
	 * @return  string     The PHP error, ready to print.
	 * @since    1.0.0
	 */
	private function code_to_string( $code ) {
		switch ( $code ) {
			case E_ERROR:
				return 'E_ERROR';
			case E_WARNING:
				return 'E_WARNING';
			case E_PARSE:
				return 'E_PARSE';
			case E_NOTICE:
				return 'E_NOTICE';
			case E_CORE_ERROR:
				return 'E_CORE_ERROR';
			case E_CORE_WARNING:
				return 'E_CORE_WARNING';
			case E_COMPILE_ERROR:
				return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING:
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR:
				return 'E_USER_ERROR';
			case E_USER_WARNING:
				return 'E_USER_WARNING';
			case E_USER_NOTICE:
				return 'E_USER_NOTICE';
			case E_STRICT:
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR:
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED:
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED:
				return 'E_USER_DEPRECATED';
		}
		return 'Unknown PHP error';
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.0.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		if ( ! \Decalog\Plugin\Feature\DMonitor::$active ) {
			return;
		}
		if ( defined( 'POWP_START_TIMESTAMP' ) ) {
			$this->monitor->set_dev_gauge( 'execution_latency', microtime( true ) - POWP_START_TIMESTAMP );
		}
	}
}
