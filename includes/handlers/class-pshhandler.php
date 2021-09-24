<?php
/**
 * Pushover handler for Monolog
 *
 * Handles all features of Pushover handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use DLMonolog\Handler\PushoverHandler;

/**
 * Define the Monolog Pushover handler.
 *
 * Handles all features of Pushover handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
 */
class PshHandler extends PushoverHandler {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string       $token             Pushover api token.
	 * @param string|array $users             Pushover user id or array of ids the message will be sent to.
	 * @param string|null  $title             Optional. Title sent to the Pushover API.
	 * @param integer      $timeout           Optional. The socket timeout.
	 * @param int|string   $level             The minimum logging level at which this handler will be triggered.
	 * @since   1.3.0
	 */
	public function __construct( $token, $users, $title = null, $timeout = 500, $level = Logger::CRITICAL ) {
		$new_timeout = $timeout / 1000;
		$old_timeout = ini_get( 'default_socket_timeout' );
		// phpcs:ignore
		ini_set( 'default_socket_timeout', (string) $new_timeout );
		parent::__construct( $token, $users, $title, $level, true, true );
		// phpcs:ignore
		ini_set( 'default_socket_timeout', (string) $old_timeout );
	}
}
