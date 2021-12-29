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
use Decalog\Plugin\Feature\EventTypes;

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
		$tracer = new DTracer( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
		$span   = $tracer->start_span( 'Events logs rotation' );
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$logger['uuid'] = $key;
				$instance       = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				$instance->cron_clean();
			}
		}
		$tracer->end_span( $span );
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

	/**
	 * Finalize the logger.
	 *
	 * @since    1.0.0
	 */
	public function finalize() {
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$logger['uuid'] = $key;
				$instance       = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				$instance->finalize();
			}
		}
	}

	/**
	 * Get loggers debug info (for Site Health).
	 *
	 * @return array    The loggers definitions.
	 * @since    1.0.0
	 */
	public function debug_info() {
		$result = [];
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			if ( ! array_key_exists( 'configuration', $logger ) ) {
				$logger['configuration'] = [];
			}
			$name = $logger['name'];
			unset( $logger['name'] );
			$logger['uuid']    = '{' . $key . '}';
			$logger['running'] = $logger['running'] ? 'yes' : 'no';
			if ( array_key_exists( $logger['level'], EventTypes::$level_names ) ) {
				$logger['level'] = strtolower( EventTypes::$level_names[ $logger['level'] ] );
			} else {
				$logger['level'] = 'none';
			}
			$privacy           = [];
			foreach ( $logger['privacy'] as $i => $item ) {
				if ( $item ) {
					$privacy[] = $i;
				}
			}
			$logger['privacy']    = '[' . implode(', ', $privacy ) . ']';
			$logger['processors'] = '[' . implode(', ', $logger['processors'] ) . ']';
			$configuration        = [];
			foreach ( $logger['configuration'] as $i => $item ) {
				if ( in_array( $i, [ 'webhook', 'token', 'user', 'users', 'filename', 'pass', 'cloudid', 'key' ], true ) ) {
					$configuration[] = $i . ':xxx';
				} else {
					$configuration[] = $i . ':' . ( is_bool( $item ) ? ( $item ? 'false' : 'true' ) : $item );
				}
			}
			$logger['configuration'] = '[' . implode(', ', $configuration ) . ']';
			$result[ $key ]          = [ 'label' => $name, 'value' => $logger ];
		}
		return $result;
	}

	/**
	 * Get loggers debug info (for Site Health).
	 *
	 * @since    1.0.0
	 */
	public static function forced_pause() {
		$loggers = Option::network_get( 'loggers' );
		if ( 0 < count( $loggers ) ) {
			$new = [];
			foreach ( $loggers as $uuid => $logger ) {
				$logger['running'] = false;
				$new[ $uuid ]      = $logger;
			}
			Option::network_set( 'loggers', $new );
		}
	}

}
