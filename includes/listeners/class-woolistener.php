<?php
/**
 * WooCommerce listener for DecaLog.
 *
 * Defines class for WooCommerce listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */

namespace Decalog\Listener;

/**
 * WooCommerce listener for DecaLog.
 *
 * Defines methods and properties for WooCommerce listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class WooListener extends AbstractListener {

	/**
	 * Is this listener pre-launched.
	 *
	 * @since  3.6.0
	 * @var    boolean   $prelaunched    Is this listener pre-launched.
	 */
	public static $prelaunched = false;

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'woo';
		$this->class   = 'plugin';
		$this->product = 'WooCommerce';
		$this->name    = 'WooCommerce';
		if ( function_exists( 'WC' ) ) {
			$woocommerce   = WC();
			$this->version = $woocommerce->version;
		} else {
			$this->version = 'x';
		}
		$this->prelaunch();
	}

	/**
	 * Get info about the listener.
	 *
	 * @return  array  The infos about the listener.
	 * @since    3.6.0
	 */
	public function get_info() {
		$result              = [];
		$result['id']        = $this->id;
		$result['name']      = $this->name;
		$result['product']   = $this->product;
		$result['class']     = $this->class;
		$result['version']   = $this->version;
		$result['available'] = $this->is_available();
		$result['step']      = '';
		if ( self::$prelaunched ) {
			$result['step'] = 'pre+';
		}
		return $result;
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.6.0
	 */
	protected function is_available() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * "Pre-launch" the listener.
	 *
	 * @since    3.6.0
	 */
	protected function prelaunch() {
		add_filter( 'woocommerce_register_log_handlers', [ $this, 'woocommerce_register_log_handlers' ], 0, 1 );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.6.0
	 */
	protected function launch() {
		add_filter( 'woocommerce_register_log_handlers', [ $this, 'woocommerce_register_log_handlers' ], 0, 1 );
		add_action( 'woocommerce_rest_insert_system_status_tool', [ $this, 'status_tool_executed' ], 10, 2 );
		add_action( 'woocommerce_system_status_tool_executed', [ $this, 'status_tool_executed' ], 10, 1 );
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
	 * "woocommerce_rest_insert_system_status_tool" and "woocommerce_system_status_tool_executed" actions.
	 *
	 * @param array           $tool    Details about the tool that has been executed.
	 * @param WP_REST_Request $request The current WP_REST_Request object.
	 * @since    3.6.0
	 */
	public function status_tool_executed( $tool, $request = null ) {
		if ( is_array( $tool ) && array_key_exists( 'message', $tool ) ) {
			$this->logger->notice( '[wc-status-tool] ' . $tool['message'] );
		} else {
			$this->logger->warning( '[wc-status-tool] Unknown command executed.' );
		}
	}

	/**
	 * "woocommerce_register_log_handlers" filter.
	 *
	 * @since    1.6.0
	 */
	public function woocommerce_register_log_handlers( $handlers ) {
		if ( class_exists( '\WC_Log_Handler' ) && ! self::$prelaunched ) {
			$handlers[] = new \Decalog\Integration\WCLogger( $this->class, $this->name, $this->version );
			self::$prelaunched = true;
		}
		return $handlers;
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.0.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		// No monitors to finalize.
	}
}
