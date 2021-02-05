<?php
/**
 * Bootstrap handler for DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Plugin\Feature;

if ( ! defined( 'DECALOG_BOOTSTRAPPED' ) ) {
	define( 'DECALOG_BOOTSTRAPPED', true );
}

$dclg_btsrp = [];

/**
 * Bootstrap handler for DecaLog.
 *
 * Defines methods and properties for bootstrap handling.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class BootstrapHandler {

	/**
	 * The error level mapping.
	 *
	 * @since  2.4.0
	 * @var array $error_level_map List of mappings.
	 */
	private $error_level_map = [
		E_ERROR             => 600,
		E_PARSE             => 600,
		E_CORE_ERROR        => 600,
		E_COMPILE_ERROR     => 600,
		E_USER_ERROR        => 400,
		E_RECOVERABLE_ERROR => 400,
		E_CORE_WARNING      => 300,
		E_WARNING           => 300,
		E_COMPILE_WARNING   => 300,
		E_USER_WARNING      => 300,
		E_NOTICE            => 250,
		E_USER_NOTICE       => 250,
		E_STRICT            => 250,
		E_DEPRECATED        => 200,
		E_USER_DEPRECATED   => 200,
	];

	/**
	 * The error string mapping.
	 *
	 * @since  2.4.0
	 * @var array $error_string_map List of mappings.
	 */
	private $error_string_map = [
		E_ERROR             => 'E_ERROR',
		E_PARSE             => 'E_PARSE',
		E_CORE_ERROR        => 'E_CORE_ERROR',
		E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
		E_USER_ERROR        => 'E_USER_ERROR',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_CORE_WARNING      => 'E_CORE_WARNING',
		E_WARNING           => 'E_WARNING',
		E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
		E_USER_WARNING      => 'E_USER_WARNING',
		E_NOTICE            => 'E_NOTICE',
		E_USER_NOTICE       => 'E_USER_NOTICE',
		E_STRICT            => 'E_STRICT',
		E_DEPRECATED        => 'E_DEPRECATED',
		E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
	];

	/**
	 * The unique instance of the class.
	 *
	 * @since  2.4.0
	 * @var self $instance The unique instance of the class.
	 */
	private static $instance;

	/**
	 * Create the class instance.
	 *
	 * @since    2.4.0
	 */
	public static function init() {
		self::$instance = new BootstrapHandler();
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.4.0
	 */
	public function __construct() {
		// phpcs:ignore
		$this->previous_error_handler = \set_error_handler( [ $this, 'handle_error' ] );
		// phpcs:ignore
		$this->previous_exception_handler = \set_exception_handler( [ $this, 'handle_exception' ] );
	}

	/**
	 * Handles errors.
	 *
	 * @param   integer $code The error code.
	 * @param   string  $message The error message.
	 * @param   string  $file The file where the error was raised.
	 * @param   integer $line The line where the error was raised.
	 * @param   array   $context The context of the error.
	 * @since    2.4.0
	 */
	public function handle_error( $code, $message, $file = '', $line = 0, $context = [] ) {
		$level   = $this->error_level_map[ $code ] ?? 500;
		$file    = $this->normalized_file_line( $file, $line );
		$message = \sprintf( 'Error (%s): "%s" at %s', $this->error_string_map[ $code ] ?? 'Unknown PHP error', $message, $file );
		$this->prelog( $level, $message, (int) $code );
		if ( $this->previous_error_handler && \is_callable( $this->previous_error_handler ) ) {
			return \call_user_func( $this->previous_error_handler, $code, $message, $file, $line, $context );
		}
		return false;
	}

	/**
	 * Handles errors.
	 *
	 * @param   \Throwable $exception  The uncaught exception.
	 * @since    2.4.0
	 */
	public function handle_exception( $exception ) {
		$file    = $this->normalized_file_line( $exception->getFile(), $exception->getLine() );
		$message = \sprintf( 'Uncaught exception (%s): "%s" at %s', $this->get_class( $exception ), $exception->getMessage(), $file );
		$this->prelog( 400, $message, (int) $exception->getCode() );
		if ( $this->previous_exception_handler && \is_callable( $this->previous_exception_handler ) ) {
			\call_user_func( $this->previous_exception_handler, $exception );
		}
		exit( 255 );
	}

	/**
	 * Handles errors.
	 *
	 * @param integer   $level      The log level.
	 * @param string    $message    The log message.
	 * @param integer   $code       Optional. The log code.
	 * @since    2.4.0
	 */
	private function prelog( $level, $message, int $code = 0 ) {
		global $dclg_btsrp;
		$dclg_btsrp[] = [
			'level'   => $level,
			'message' => $message,
			'code'    => $code,
		];
	}

	/**
	 * Normalizes a path+file.
	 *
	 * @param   string  $file   The raw file name.
	 * @return  string  The normalized file name.
	 * @since   2.4.0
	 */
	private function normalized_file( $file ) {
		if ( 'unknown' === $file || '' === $file ) {
			return 'PHP kernel';
		}
		if ( false !== \strpos( $file, 'phar://' ) ) {
			return \str_replace( 'phar://', '', $this->normalized_path( $file ) );
		}
		return './' . \str_replace( $this->normalized_path( ABSPATH ), '', $this->normalized_path( $file ) );
	}

	/**
	 * Normalizes a path+file.
	 *
	 * @param   string  $file   The raw file name.
	 * @param   string  $line   Optional. The file line.
	 * @return  string  The normalized file & line.
	 * @since   2.4.0
	 */
	private function normalized_file_line( $file, $line = '' ) {
		if ( '' === (string) $line || '0' === (string) $line ) {
			return $this->normalized_file( $file );
		}
		return $this->normalized_file( $file ) . ':' . (string) $line;
	}

	/**
	 * Test if a given path is a stream URL
	 *
	 * @param string $path The resource path or URL.
	 * @return bool True if the path is a stream URL.
	 * @since    2.4.0
	 */
	private function is_stream( $path ) {
		$scheme_separator = \strpos( $path, '://' );
		if ( false === $scheme_separator ) {
			return false;
		}
		$stream = \substr( $path, 0, $scheme_separator );
		return \in_array( $stream, \stream_get_wrappers(), true );
	}

	/**
	 * Normalize a filesystem path.
	 *
	 * On windows systems, replaces backslashes with forward slashes
	 * and forces upper-case drive letters.
	 * Allows for two leading slashes for Windows network shares, but
	 * ensures that all other duplicate slashes are reduced to a single.
	 *
	 * @param string $path Path to normalize.
	 * @return string Normalized path.
	 * @since    2.4.0
	 */
	private function normalized_path( $path ) {
		$wrapper = '';

		if ( $this->is_stream( $path ) ) {
			[ $wrapper, $path ] = \explode( '://', $path, 2 );
			$wrapper           .= '://';
		}
		$path = \str_replace( '\\', '/', $path );
		$path = \preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === \substr( $path, 1, 1 ) ) {
			$path = \ucfirst( $path );
		}
		return $wrapper . $path;
	}

	/**
	 * Get the class.
	 *
	 * @return  string     The class of the object.
	 * @since    2.4.0
	 */
	private function get_class( $object ) {
		$class = \get_class( $object );
		return 'c' === $class[0] && 0 === \strpos( $class, "class@anonymous\0" ) ? \get_parent_class( $class ) . '@anonymous' : $class;
	}
}

BootstrapHandler::init();
