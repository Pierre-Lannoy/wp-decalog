<?php
/**
 * Bootstrap handler for DecaLog.
 *
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */


if ( ! defined( 'DECALOG_BOOTSTRAPPED' ) ) {
	define( 'DECALOG_BOOTSTRAPPED', true );
}

$dclg_btsrp = [];

/**
 * Bootstrap handler for DecaLog.
 *
 * Defines methods and properties for bootstrap handling.
 *
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class Decalog_Error_Handler extends \WP_Fatal_Error_Handler {

	/**
	 * The previous error handler, to restore if needed.
	 *
	 * @since  2.4.0
	 * @var callable $previous_error_handler The previous error handler.
	 */
	private $previous_error_handler;

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
	 * Initialize the class and set its properties.
	 *
	 * @since    2.4.0
	 */
	public function __construct() {
		if ( ! defined( 'WP_SANDBOX_SCRAPING' ) ) {
			// phpcs:ignore
			$this->previous_error_handler = \set_error_handler( [ $this, 'handle_error' ] );
		}
		if ( ! defined( 'DECALOG_TRACEID' ) ) {
			define( 'DECALOG_TRACEID', $this->generate_unique_id() );
		}
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
		if ( ! defined( 'SAVEQUERIES' ) ) {
			define( 'SAVEQUERIES', true );
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
	 * @since    2.4.0
	 */
	public function handle_error( $code, $message, $file = '', $line = 0, $context = [] ) {
		$level   = $this->error_level_map[ $code ] ?? 500;
		$file    = $this->normalized_file_line( $file, $line );
		$message = \sprintf( 'Error (%s): "%s" at `%s`.', $this->error_string_map[ $code ] ?? 'Unknown PHP error', $message, $file );
		$this->prelog( $level, $message, (int) $code );
		if ( $this->previous_error_handler && \is_callable( $this->previous_error_handler ) ) {
			return \call_user_func( $this->previous_error_handler, $code, $message, $file, $line, $context );
		}
		return false;
	}

	/**
	 * In-memory logging.
	 *
	 * @param integer   $level      The log level.
	 * @param string    $message    The log message.
	 * @param integer   $code       Optional. The log code.
	 * @since    2.4.0
	 */
	private function prelog( $level, $message, int $code = 0 ) {
		global $dclg_btsrp;
		if ( ! is_array( $dclg_btsrp ) ) {
			$dclg_btsrp = [];
		}
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
	 * Generates a v4 UUID.
	 *
	 * @since  1.0.0
	 * @return string      A v4 UUID.
	 */
	private function generate_v4() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// phpcs:disable
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		// phpcs:enabled
		);
	}

	/**
	 * Generates a (pseudo) unique ID.
	 * This function does not generate cryptographically secure values, and should not be used for cryptographic purposes.
	 *
	 * @param   integer $length     Optional. The length of the ID.
	 * @return  string  The unique ID.
	 * @since  1.0.0
	 */
	private function generate_unique_id( $length = 32 ) {
		$result = '';
		$date   = new \DateTime();
		do {
			$s       = $this->generate_v4();
			$s       = str_replace( '-', (string) ( $date->format( 'u' ) ), $s );
			$result .= $s;
			$l       = strlen( $result );
		} while ( $l < $length );
		return substr( str_shuffle( $result ), 0, $length );
	}
}

return new Decalog_Error_Handler();
