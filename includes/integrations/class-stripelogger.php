<?php
/**
 * Stripe integration definition.
 *
 * @package Integrations
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since 2.4.0
 */

namespace Decalog\Integration;

use Decalog\Plugin\Feature\DLogger;

if ( interface_exists( '\Stripe\Util\LoggerInterface' ) ) {
	/**
	 * Stripe integration class.
	 *
	 * This class defines all code necessary to log events with Stripe.
	 *
	 * @package API
	 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
	 * @since 2.4.0
	 */
	class StripeLogger implements \Stripe\Util\LoggerInterface {

		/**
		 * The "true" DLogger instance.
		 *
		 * @since 2.4.0
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
		 * @since 2.4.0
		 */
		public function __construct( $class, $name = null, $version = null ) {
			$this->logger = new DLogger( $class, $name, $version, null, true );
		}

		/**
		 * Runtime errors that do not require immediate action but should typically
		 * be logged and monitored.
		 *
		 * @param string $message
		 * @param array $context
		 * @since 2.4.0
		 */
		public function error( $message, array $context = [] ) {
			$this->logger->error( (string) $message );
		}
	}
}
