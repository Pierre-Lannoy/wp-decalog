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
	private $log = null;

	/**
	 * An instance of DLogger to log listener events.
	 *
	 * @since  1.0.0
	 * @var    DLogger    $logger    An instance of DLogger to log listener events.
	 */
	private $logger = null;

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
	 * @@param    DLogger    $internal_logger    An instance of DLogger to log internal events.
	 * @since    1.0.0
	 */
	public function __construct($internal_logger) {
		$this->log = $internal_logger;
		$this->init();
		if ($this->is_needed()) {
			$launch = Option::get('autolisteners');
			if (!$launch) {
				$listeners = Option::get('listeners');
				if (array_key_exists($this->id, $listeners)) {
					$launch = $listeners[$this->id];
				}
			}
			if ($launch && $this->launch()) {
				$this->logger = Log::bootstrap( $this->class, $this->product, $this->version );
				$this->log->debug( sprintf( 'Listener for %s is launched.', $this->name ) );
				$this->logger->debug( sprintf( 'Listener launched and operational.', $this->name ) );
			}
		}
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
	abstract protected function is_needed();

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	abstract protected function launch();

}
