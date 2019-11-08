<?php
/**
 * WooCommerce integration definition.
 *
 * @package Integrations
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */

namespace Decalog\Integration;

use Decalog\Plugin\Feature\DLogger;

/**
 * WooCommerce integration class.
 *
 * This class defines all code necessary to log events with WooCommerce.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class WCLogger extends \WC_Log_Handler {

	/**
	 * The "true" DLogger instance.
	 *
	 * @since  1.6.0
	 * @var    \Decalog\API\DLogger    $logger    Maintains the internal DLogger instance.
	 */
	private $logger = null;

	/**
	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $class   The class identifier, must be a value in ['plugin', 'theme'].
	 * @param string $name    Optional. The name of the component that will trigger events.
	 * @param string $version Optional. The version of the component that will trigger events.
	 * @since 1.6.0
	 */
	public function __construct( $class, $name = null, $version = null ) {
		$this->logger = new DLogger( $class, $name, $version, null, true );
	}

	/**
	 * Handle a log entry.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array  $context Additional information for log handlers.
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context ) {
		$this->logger->log( $level, array_key_exists( 'source', $context ) ? '[' . (string) $context['source'] . '] ' . $message : $message, array_key_exists( 'code', $context ) ? (int) $context['code'] : 0 );
		return true;
	}
}