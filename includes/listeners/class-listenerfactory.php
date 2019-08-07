<?php
/**
 * Listeners handling
 *
 * Handles all listeners operations.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

use Decalog\Log;

/**
 * Define the listeners handling functionality.
 *
 * Handles all listeners operations.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ListenerFactory {

	/**
	 * An instance of DLogger to log internal events.
	 *
	 * @since  1.0.0
	 * @var    DLogger    $log    An instance of DLogger to log internal events.
	 */
	private $log = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->log = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
	}

	/**
	 * Launch the listeners.
	 *
	 * @since    1.0.0
	 */
	public function launch() {
		foreach (
			array_diff( scandir( DECALOG_LISTENERS_DIR ), array(
				'..',
				'.',
				'index.php',
				'class-abstractlistener.php',
				'class-listenerfactory.php'
			) ) as $item
		) {
			if ( ! is_dir( DECALOG_LISTENERS_DIR . $item ) ) {
				$classname = str_replace( [ 'class-', '.php' ], '', $item );
				$classname = str_replace( 'listener', 'Listener', strtolower( $classname ) );
				$classname = lcfirst( $classname );
				if ( ! $this->create_listener_instance( $classname ) ) {
					$this->log->error( sprintf( 'Trying to launch a wrong listener: %s.', $classname ) );
				}
			}
		}
	}

	/**
	 * Create an instance of $class_name.
	 *
	 * @param   string $class_name The class name.
	 * @return  null|object The instance of the class if creation was possible, null otherwise.
	 * @since    1.0.0
	 */
	private function create_listener_instance( $class_name ) {
		$class_name = 'Decalog\Listener\\' . $class_name;
		if ( class_exists( $class_name ) ) {
			$reflection = new \ReflectionClass( $class_name );
			return $reflection->newInstanceArgs( [$this->log] );
		}
		return false;
	}

}
