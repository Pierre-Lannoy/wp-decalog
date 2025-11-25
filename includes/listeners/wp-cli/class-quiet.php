<?php
/**
 * WP-CLI listener for DecaLog.
 *
 * Defines class for WP-CLI listener.
 *
 * @package Listeners\WP_CLI
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.6.0
 */

namespace Decalog\Listener\WP_CLI;

use Decalog\Plugin\Feature\Log;

/**
 * WP-CLI listener for DecaLog.
 *
 * Defines class for WP-CLI listener.
 *
 * @package Listeners\WP_CLI
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.6.0
 */
class Quiet extends \WP_CLI\Loggers\Quiet {

	/**
	 * The "true" DLogger instance.
	 *
	 * @since  3.6.0
	 * @var    \Decalog\Plugin\Feature\DLogger    $logger    Maintains the internal DLogger instance.
	 */
	private $logger = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param bool $in_color Optional. Whether or not to Colorize strings.
	 * @since 3.6.0
	 */
	public function __construct( $in_color = false ) {
		parent::__construct( $in_color );
		$this->logger = Log::bootstrap( 'core', 'WP-CLI', defined( 'WP_CLI_VERSION' ) ? WP_CLI_VERSION : 'x' );
	}

	/**
	 * Adds a log record at the INFO level.
	 *
	 * @param string $message Message to write.
	 * @since 3.6.0
	 */
	public function info( $message ) {
		$this->logger->debug( ucfirst( $message ) );
		parent::info( $message );
	}

	/**
	 * Adds a log record at the NOTICE level.
	 *
	 * @param string $message Message to write.
	 * @since 3.6.0
	 */
	public function success( $message ) {
		$this->logger->debug( ucfirst( $message ) );
		parent::success( $message );
	}

	/**
	 * Adds a log record at the WARNING level.
	 *
	 * @param string $message Message to write.
	 * @since 3.6.0
	 */
	public function warning( $message ) {
		$this->logger->warning( ucfirst( $message ) );
		parent::warning( $message );
	}

	/**
	 * Adds a log record at the ERROR level.
	 *
	 * @param string $message Message to write.
	 * @since 3.6.0
	 */
	public function error( $message ) {
		$this->logger->error( ucfirst( $message ) );
		parent::error( $message );
	}

	/**
	 * Adds a log (multiline) record at the ERROR level.
	 *
	 * @param  array $message_lines Message to write.
	 * @since 3.6.0
	 */
	public function error_multi_line( $message_lines ) {
		if ( is_array( $message_lines ) ) {
			$message = decalog_mb_full_trim( implode( ' ', $message_lines ), ' ' );
		} elseif ( is_string( $message_lines ) ) {
			$message = decalog_mb_full_trim( $message_lines, ' ' );
		} else {
			$message = 'Unknown error: ' . json_encode( $message_lines );
		}
		$this->logger->error( ucfirst( $message ) );
		parent::error_multi_line( $message_lines );
	}

}