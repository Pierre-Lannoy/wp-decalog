<?php
/**
 * Yoast SEO integration definition.
 *
 * @package Integrations
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */

namespace Decalog\Integration;

use Decalog\Plugin\Feature\DLogger;

/**
 * Yoast SEO integration class.
 *
 * This class defines all code necessary to log events for Yoast SEO.
 *
 * @package Integrations
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */
class WpseoLogger implements \YoastSEO_Vendor\Psr\Log\LoggerInterface {

	/**
	 * True if possible to add multiple loggers.
	 *
	 * @var bool
	 */
	protected $allow_multiple = true;

	/**
	 * The "true" DLogger instance.
	 *
	 * @since  1.14.0
	 * @var    \Decalog\Plugin\Feature\DLogger    $logger    Maintains the internal DLogger instance.
	 */
	private $logger = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.14.0
	 */
	public function __construct() {
		$this->logger = new DLogger( 'plugin', 'Yoast SEO', defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : 'x', null, true );
	}

	/**
	 * Returns logger description
	 *
	 * @since 1.14.0
	 * @return string
	 */
	public function get_description() {
		return decalog_esc_html__( 'Log all events via DecaLog', 'decalog' );
	}

	/**
	 * System is unusable.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function emergency( $message, array $context = [] ) {
		$this->logger->emergency( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function alert( $message, array $context = [] ) {
		$this->logger->alert( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Critical conditions.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function critical( $message, array $context = [] ) {
		$this->logger->critical( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function error( $message, array $context = [] ) {
		$this->logger->error( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function warning( $message, array $context = [] ) {
		$this->logger->warning( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function notice( $message, array $context = [] ) {
		$this->logger->notice( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function info( $message, array $context = [] ) {
		$this->logger->info( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function debug( $message, array $context = [] ) {
		$this->logger->debug( (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param  string $level   emergency|alert|critical|error|warning|notice|info|debug.
	 * @param  string $message The log message.
	 * @param  array  $context Optional. The context - only code will be set in DecaLog.
	 * @since 1.14.0
	 */
	public function log( $level, $message, array $context = [] ) {
		$this->logger->log( $level, (string) $message, is_array( $context ) && array_key_exists( 'code', $context ) && is_scalar( $context['code'] ) ? (int) $context['code'] : 0 );
	}
}