<?php
/**
 * Logger maintenance handling
 *
 * Handles all logger maintenance operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\System\Option;

/**
 * Define the logger maintenance functionality.
 *
 * Handles all logger maintenance operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class LoggerMaintainer {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Create an instance of $class_name.
	 *
	 * @param   string $class_name The class name.
	 * @param   array  $args   The param of the constructor for $class_name class.
	 * @return  boolean|object The instance of the class if creation was possible, null otherwise.
	 * @since    1.0.0
	 */
	private function create_instance( $class_name, $args = [] ) {
		if ( class_exists( $class_name ) ) {
			try {
				$reflection = new \ReflectionClass( $class_name );
				return $reflection->newInstanceArgs( $args );
			} catch ( \Exception $e ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Clean the logger.
	 *
	 * @since    1.0.0
	 */
	public function cron_clean() {
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$logger['uuid'] = $key;
				$instance       = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				$instance->cron_clean();
			}
		}
	}

	/**
	 * Update the logger.
	 *
	 * @since    1.0.0
	 */
	public function update( $from ) {
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$logger['uuid'] = $key;
				$instance       = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				$instance->update( $from );
			}
		}
	}

}
