<?php
/**
 * Listener stub for DecaLog.
 *
 * Defines abstract class for all DecaLog listeners.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

use Decalog\Log;
use Decalog\System\Option;
use Decalog\System\User;
use WP_User;

/**
 * Listener stub for DecaLog.
 *
 * Defines abstract methods and properties for all DecaLog listeners classes.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
abstract class AbstractListener {

	/**
	 * An instance of DLogger to log internal events.
	 *
	 * @since  1.0.0
	 * @var    DLogger    $log    An instance of DLogger to log internal events.
	 */
	protected $log = null;

	/**
	 * An instance of DLogger to log listener events.
	 *
	 * @since  1.0.0
	 * @var    DLogger    $logger    An instance of DLogger to log listener events.
	 */
	protected $logger = null;

	/**
	 * The listener id.
	 *
	 * @since  1.0.0
	 * @var    string    $id    The listener id.
	 */
	protected $id = '';

	/**
	 * The listener full name.
	 *
	 * @since  1.0.0
	 * @var    string    $name    The listener full name.
	 */
	protected $name = '';

	/**
	 * The product name.
	 *
	 * @since  1.0.0
	 * @var    string    $product    The product name.
	 */
	protected $product = 'Unknown';

	/**
	 * The product class.
	 *
	 * @since  1.0.0
	 * @var    string    $class    The product class.
	 */
	protected $class = '';

	/**
	 * The product version.
	 *
	 * @since  1.0.0
	 * @var    string    $version    The product version.
	 */
	protected $version = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param    DLogger $internal_logger    An instance of DLogger to log internal events.
	 * @since    1.0.0
	 */
	public function __construct( $internal_logger ) {
		$this->log = $internal_logger;
		$this->init();
		if ( $this->is_available() ) {
			$launch = Option::get( 'autolisteners' );
			if ( ! $launch ) {
				if ( in_array( $this->id, Option::get( 'listeners' ), true ) ) {
					$launch = true;
				}
			}
			if ( $launch && $this->launch() || 'SelfListener' === get_class( $this ) ) {
				$this->logger = Log::bootstrap( $this->class, $this->product, $this->version );
				$this->log->notice( sprintf( 'Listener for %s is launched.', $this->name ) );
				$this->logger->notice( 'Listener launched and operational.' );
			}
		}
	}

	/**
	 * Get info about the listener.
	 *
	 * @return  array  The infos about the listener.
	 * @since    1.0.0
	 */
	public function get_info() {
		$result              = [];
		$result['id']        = $this->id;
		$result['name']      = $this->name;
		$result['product']   = $this->product;
		$result['class']     = $this->class;
		$result['version']   = $this->version;
		$result['available'] = $this->is_available();
		return $result;
	}

	/**
	 * Pseudonymizes user if needed.
	 *
	 * @param    mixed $user    The user.
	 * @return  string  The user string, pseudonymized if needed.
	 * @since    1.0.0
	 */
	protected function get_user( $user ) {
		$id = 0;
		if ( isset( $user ) && is_numeric( $user ) ) {
			$id = $user;
		}
		if ( 0 === $id && ! empty( $user ) ) {
			if ( $user instanceof WP_User ) {
				return $user->ID;
			}
			if ( is_object( $user ) && isset( $user->ID ) ) {
				return $user->ID;
			}
		}
		return User::get_user_string( $id, Option::get( 'pseudonymization' ) );
	}

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	abstract protected function init();

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.0.0
	 */
	abstract protected function is_available();

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	abstract protected function launch();

}
