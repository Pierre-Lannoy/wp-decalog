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
use Decalog\System\Option;

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
	 * Excluded files from listeners auto loading.
	 *
	 * @since  1.0.0
	 * @var    array    $excluded_files    The list of excluded files.
	 */
	private $excluded_files = [
		'..',
		'.',
		'index.php',
		'class-abstractlistener.php',
		'class-listenerfactory.php',
		'class-selflistener.php',
	];

	/**
	 * Infos on all loadable listeners.
	 *
	 * @since  1.0.0
	 * @var    array    $excluded_files    The list of all loadable listeners.
	 */
	public static $infos = [];

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
		self::$infos        = [];
		$phplistener_loaded = false;
		foreach (
			array_diff( scandir( DECALOG_LISTENERS_DIR ), $this->excluded_files ) as $item ) {
			if ( ! is_dir( DECALOG_LISTENERS_DIR . $item ) ) {
				$classname = str_replace( [ 'class-', '.php' ], '', $item );
				$classname = str_replace( 'listener', 'Listener', strtolower( $classname ) );
				$classname = ucfirst( $classname );
				$instance  = $this->create_listener_instance( $classname );
				if ( $instance ) {
					self::$infos[] = $instance->get_info();
					if ( 'PhpListener' === $classname && in_array( 'PhpListener', Option::get( 'listeners' ), true ) ) {
						$phplistener_loaded = true;
					}
				} else {
					$this->log->error( sprintf( 'Unable to load "%s".', $classname ) );
				}
			}
		}
		if ( ! $phplistener_loaded ) {
			$instance = $this->create_listener_instance( 'SelfListener' );
			if ( ! $instance ) {
				$this->log->alert( 'Unable to load fallback listener.', 666 );
			}
		}
	}

	/**
	 * Create an instance of a listener.
	 *
	 * @param   string $class_name The class name.
	 * @return  boolean|object The instance of the class if creation was possible, null otherwise.
	 * @since    1.0.0
	 */
	private function create_listener_instance( $class_name ) {
		$class_name = 'Decalog\Listener\\' . $class_name;
		if ( class_exists( $class_name ) ) {
			try {
				$reflection = new \ReflectionClass( $class_name );
				return $reflection->newInstanceArgs( [ $this->log ] );
			} catch ( Exception $e ) {
				return false;
			}
		}
		return false;
	}

}
