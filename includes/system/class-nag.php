<?php
/**
 * Nag handling
 *
 * Handles all nag operations.
 * Please, use these features with respect for users.
 * Don't hijack the admin dashboard!
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace WPPluginBoilerplate\System;

/**
 * Define the nag functionality.
 *
 * Handles all nag operations. Note this nag feature respects the
 * DISABLE_NAG_NOTICES unofficial "standard".
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Nag {

	/**
	 * The nags list.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $nags    The nags list.
	 */
	private static $nags = [];

	/**
	 * Indicates whether nags are allowed or not.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    boolean    $allowed    Is nags allowed?
	 */
	private static $allowed = true;

	/**
	 * Verify if nags are allowed and if yes, load the nags.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		if ( defined( 'DISABLE_NAG_NOTICES' ) ) {
			self::$allowed = DISABLE_NAG_NOTICES;
		}
		if ( self::$allowed ) {
			self::$allowed = Option::get( 'display_nag' );
		}
		if ( self::$allowed ) {
			self::$nags = Option::get( 'nags' );
		}
	}

	/**
	 * Adds a Nag.
	 *
	 * @param   string $id     Id of the nag.
	 * @param   string $type   Type of the nag ('error', 'warning', 'success' or 'info').
	 * @param   string $value  Value to display.
	 * @since 1.0.0
	 */
	public static function add( $id, $type, $value ) {
		self::$nags[ $id ] = [
			'type'  => $type,
			'value' => $value,
		];
		Option::set( 'nags', self::$nags );
	}

	/**
	 * Deletes a Nag.
	 *
	 * @param   string $id     Id of the nag.
	 * @since 1.0.0
	 */
	public static function delete( $id ) {
		if ( array_key_exists( $id, self::$nags ) ) {
			unset( self::$nags[ $id ] );
			Option::set( 'nags', self::$nags );
		}
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Show all available notices.
	 *
	 * @since 1.0.0
	 */
	public function display() {
		if ( self::$allowed ) {
			foreach ( self::$nags as $key => $nag ) {
				$nonce_action = sanitize_key( $key );
				$nonce_name   = str_replace( [ '-', '_' ], '', $nonce_action );
				$nonce        = wp_nonce_field( $nonce_action, $nonce_name, false, false );
				$div_id       = 'wppb-' . $nonce_name;
				$div_class    = 'notice notice-' . $nag['type'] . ' is-dismissible';
				$text         = wp_kses(
					$nag['value'],
					[
						'a'      => [
							'href' => true,
						],
						'br'     => true,
						'em'     => true,
						'strong' => true,
					]
				);
				$html         = '<div id="' . $div_id . '" class="' . $div_class . '">' . $nonce . '<p>' . $text . '</p></div>';
				$js           = '<script>jQuery(document).ready(function($){$("#' . $div_id . '").on("click", ".notice-dismiss", function(event){$.post(ajaxurl,{action: "hide_wppb_nag",' . $nonce_name . ': $("#' . $nonce_name . '").val()});});});</script>';
				// phpcs:ignore
				print( $html . $js );
			}
		}
	}

	/**
	 * Ajax handler for updating the displaying status of notices.
	 *
	 * @since 1.0.0
	 */
	public static function hide_callback() {
		foreach ( self::$nags as $key => $nag ) {
			$nonce_action = sanitize_key( $key );
			$nonce_name   = str_replace( [ '-', '_' ], '', $nonce_action );
			if ( false !== check_ajax_referer( $nonce_action, $nonce_name, false ) ) {
				self::delete( $key );
				wp_die( 200 );
			}
		}
		wp_die( 409 );
	}
}

Nag::init();
