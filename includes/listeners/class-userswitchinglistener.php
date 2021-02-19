<?php
/**
 * User Switching listener for DecaLog.
 *
 * Defines class for User Switching listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.4.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;

/**
 * User Switching listener for DecaLog.
 *
 * Defines methods and properties for User Switching listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.4.0
 */
class UserSwitchingListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'user-switching';
		$this->class   = 'plugin';
		$this->product = 'User Switching';
		$this->name    = 'User Switching';
		$this->version = '1.x.x';
		
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.4.0
	 */
	protected function is_available() {
		return class_exists( 'user_switching' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.4.0
	 */
	protected function launch() {
		add_action( 'set_user_switching_cookie', [ $this, 'set_user_switching_cookie' ], 10, 0 );
		add_action( 'set_olduser_cookie', [ $this, 'set_olduser_cookie' ], 10, 0 );
		add_action( 'clear_olduser_cookie', [ $this, 'set_olduser_cookie' ], 10, 0 );
		add_action( 'switch_to_user', [ $this, 'switch_to_user' ], 10, 4 );
		add_action( 'switch_back_user', [ $this, 'switch_back_user' ], 10, 4 );
		add_action( 'switch_off_user', [ $this, 'switch_off_user' ], 10, 2 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		// No post-launch operations
	}

	/**
	 * "set_user_switching_cookie" event.
	 *
	 * @since    1.4.0
	 */
	public function set_user_switching_cookie() {
		$this->logger->debug( 'Authentication cookie set.' );
	}

	/**
	 * "set_user_switching_cookie" event.
	 *
	 * @since    1.4.0
	 */
	public function set_olduser_cookie() {
		$this->logger->debug( 'Old user cookie set.' );
	}

	/**
	 * "clear_olduser_cookie" event.
	 *
	 * @since    1.4.0
	 */
	public function clear_olduser_cookie() {
		$this->logger->debug( 'Old user cookie cleared.' );
	}

	/**
	 * "switch_to_user" event.
	 *
	 * @since    1.4.0
	 */
	public function switch_to_user( $user_id, $old_user_id, $new_token = null, $old_token = null ) {
		$this->logger->warning( sprintf ( 'Switch from %s to %s.', $this->get_user( $old_user_id ), $this->get_user( $user_id )) );
	}

	/**
	 * "switch_back_user" event.
	 *
	 * @since    1.4.0
	 */
	public function switch_back_user( $user_id, $old_user_id, $new_token = null, $old_token = null ) {
		$this->logger->warning( sprintf ( 'Switch back from %s to %s.', $this->get_user( $old_user_id ), $this->get_user( $user_id )) );
	}

	/**
	 * "switch_off_user" event.
	 *
	 * @since    1.4.0
	 */
	public function switch_off_user( $old_user_id, $old_token = null ) {
		$this->logger->warning( sprintf ( 'Switch off %s.', $this->get_user( $old_user_id )) );
	}

}
