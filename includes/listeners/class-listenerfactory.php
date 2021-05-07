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

use Decalog\Plugin\Feature\DTracer;
use Decalog\Plugin\Feature\Log;
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
	];

	/**
	 * Excluded files from early listeners auto loading.
	 *
	 * @since  1.6.0
	 * @var    array    $late_init    The list of excluded files.
	 */
	private $late_init = [
		'class-wsallistener.php',
		'class-wordfencelistener.php',
		'class-updraftpluslistener.php',
		'class-libforminatorstripelistener.php',
		'class-libstripelistener.php',
		'class-libaschedulerlistener.php',
	];

	/**
	 * Infos on all loadable listeners.
	 *
	 * @since  1.0.$infos
	 * @var    array    $infos    The list of all loadable listeners.
	 */
	public static $infos = [];

	/**
	 * Instances of loaded listeners.
	 *
	 * @since  3.0.0
	 * @var    array    $instances    The list of loaded listeners.
	 */
	private static $instances = [];

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
		self::$infos = [];
		foreach (
			array_diff( scandir( DECALOG_LISTENERS_DIR ), $this->excluded_files, $this->late_init ) as $item ) {
			if ( ! is_dir( DECALOG_LISTENERS_DIR . $item ) ) {
				$classname = str_replace( [ 'class-', '.php' ], '', $item );
				$classname = str_replace( 'listener', 'Listener', strtolower( $classname ) );
				$classname = ucfirst( $classname );
				$instance  = $this->create_listener_instance( $classname );
				if ( $instance ) {
					self::$infos[] = $instance->get_info();
				} else {
					$this->log->error( sprintf( 'Unable to load "%s".', $classname ) );
				}
			}
		}
		DTracer::plugins_loaded();
	}

	/**
	 * Launch the listeners which need to be launched at the end of plugin load sequence.
	 *
	 * @since    1.6.0
	 */
	public function launch_late_init() {
		foreach (
			array_intersect( scandir( DECALOG_LISTENERS_DIR ), $this->late_init ) as $item ) {
			if ( ! is_dir( DECALOG_LISTENERS_DIR . $item ) ) {
				$classname = str_replace( [ 'class-', '.php' ], '', $item );
				$classname = str_replace( 'listener', 'Listener', strtolower( $classname ) );
				$classname = ucfirst( $classname );
				$instance  = $this->create_listener_instance( $classname );
				if ( $instance ) {
					self::$infos[] = $instance->get_info();
				} else {
					$this->log->error( sprintf( 'Unable to load "%s".', $classname ) );
				}
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
				$reflection        = new \ReflectionClass( $class_name );
				$instance          = $reflection->newInstanceArgs( [ $this->log ] );
				self::$instances[] = $instance;
				return $instance;
			} catch ( \Exception $e ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.0.0
	 */
	public static function force_monitoring_close() {
		foreach ( self::$instances as $instance ) {
			$instance->monitoring_close();
		}
	}

}
