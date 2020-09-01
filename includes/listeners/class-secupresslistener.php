<?php
/**
 * SecuPress listener for DecaLog.
 *
 * Defines class for SecuPress listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;

/**
 * SecuPress listener for DecaLog.
 *
 * Defines methods and properties for SecuPress listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */
class SecupressListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.14.0
	 */
	protected function init() {
		$this->id    = 'secupress';
		$this->class = 'plugin';
		if ( defined( 'SECUPRESS_PLUGIN_NAME' ) ) {
			$this->product = SECUPRESS_PLUGIN_NAME;
		} else {
			$this->product = 'SecuPress';
		}
		$this->name = $this->product;
		if ( defined( 'SECUPRESS_VERSION' ) ) {
			$this->version = SECUPRESS_VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.14.0
	 */
	protected function is_available() {
		return defined( 'SECUPRESS_VERSION' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.14.0
	 */
	protected function launch() {
		add_filter( 'secupress.logs.action-log.log-it', [ $this, 'secupress_log_action' ], PHP_INT_MAX, 4 );
		add_action( 'secupress.block', [ $this, 'secupress_log_block' ], 0, 4 );
		return true;
	}

	/**
	 * "secupress.logs.action-log.log-it" event.
	 *
	 * @since    1.14.0
	 */
	public function secupress_log_block( $module, $ip, $args, $block_id ) {
		$this->logger->emergency( $module . ' / ' . $ip . ' / ' . $block_id );
	}

	/**
	 * "secupress.logs.action-log.log-it" event.
	 *
	 * @since    1.14.0
	 */
	public function secupress_log_action( $log_it, $type, $target, $data ) {
		$item = '<unknow>';
		switch ( $target ) {
			case 'secupress.block':
				$message = 'Request prevented: %';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;


			case 'wp_login':
				if ( is_array( $data ) && 1 < count( $data ) ) {
					$item = $this->get_user( $data[1] );
				}
				$this->logger->notice( sprintf( 'Administrator logged-in: %s', $item ) );
				break;




			case 'secupress.ban.ip_banned':
				$message = 'IP banned: %s';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;
			case 'switch_theme':
				$message = 'Theme activated: %s';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;

			case 'delete_user':
				$message = 'User deleted: %s';
				$this->logger->notice( sprintf( 'User deleted: %s.', $this->get_user( 1 ) ) );
				break;
			case 'profile_update':
				$message = '%s\'s user data changed';
				$this->logger->info( sprintf( 'User updated: %s.', $this->get_user( 1 ) ) );
				break;
			case 'user_register':
				$message = 'New user %s created';
				$this->logger->notice( sprintf( 'User created: %s.', $this->get_user( 1 ) ) );
				break;
			case 'added_user_meta':
				$message = 'User meta %2$s added to %1$s';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;
			case 'updated_user_meta':
				$message = 'User meta %2$s updated for %1$s';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;
			case 'deleted_user_meta':
				$message = 'User meta %2$s deleted for %1$s';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;
			case 'wpmu_new_blog':
				$message = 'Blog %1$s created with %2$s as Administrator';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;
			case 'delete_blog':
				$message = 'Blog %s deleted';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				break;
			case 'phpmailer_init':
				$message = 'E-mail sent from %1$s to %2$s';
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
				//Logger::info( sprintf( 'Mail "%s" sent from %s to %s.', esc_html( $mail['raw']['subject'] ), $mail['raw']['from'], implode( ', ', $mail['raw']['to'] ) ) );
				break;



			case 'http_api_debug':
				if ( is_array( $data ) && 4 < count( $data ) ) {
					$item = (string) $data[4];
				}
				//$this->logger->info( sprintf( 'Outbound request: %s.', $item ) );
				break;




			default:
				$this->logger->error( $type . ' / ' . $target . ' / ' . print_r($data,true));
		}




		

		return $log_it;

	}

}
