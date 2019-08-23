<?php
/**
 * Logger types diagnosis
 *
 * Diagnose all available logger types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

/**
 * Define the logger types diagnosis.
 *
 * Diagnose all available logger types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class HandlerDiagnosis {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Check if a handler is usable.
	 *
	 * @param   string $handler  The class name of the handler to verify.
	 * @return  boolean     True if handler can be used, false otherwise.
	 * @since    1.0.0
	 */
	public function check( $handler ) {
		$result = true;
		$method = 'check_' . strtolower( $handler );
		if ( method_exists( $this, $method ) ) {
			$result = call_user_func( [ $this, $method ] );
		}
		return $result;
	}

	/**
	 * Get error string.
	 *
	 * @param   string $handler  The class name of the handler to verify.
	 * @return  string     The error in plain text.
	 * @since    1.0.0
	 */
	public function error_string( $handler ) {
		$result = '';
		$method = 'error_string_' . strtolower( $handler );
		if ( method_exists( $this, $method ) ) {
			$result = call_user_func( [ $this, $method ] );
		}
		return $result;
	}

	/**
	 * Check if SyslogUdpHandler is usable.
	 *
	 * @return  boolean     True if SyslogUdpHandler can be used, false otherwise.
	 * @since    1.0.0
	 */
	public function check_syslogudphandler() {
		$result = true;
		if ( ! function_exists( 'socket_create' ) ) {
			$result = false;
		}
		return $result;
	}

	/**
	 * Get error string for SyslogUdpHandler.
	 *
	 * @return  string     The error in plain text.
	 * @since    1.0.0
	 */
	public function error_string_syslogudphandler() {
		$result = '';
		if ( ! function_exists( 'socket_create' ) ) {
			$result = 'PHP support for sockets is not installed.';
		}
		return $result;
	}

}
