<?php
/**
 * SDK utilities for DecaLog.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Plugin\Core;

/**
 * SDK utilities for DecaLog.
 *
 * Defines methods and properties for SDK introspection.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */
class SDK {

	/**
	 * Verify if SDK is present.
	 *
	 * @retun   boolean True if SDK is present, false otherwise.
	 * @since    3.0.0
	 */
	public static function is_present() {
		return class_exists( '\DecaLog\Engine' );
	}

	/**
	 * Get the self-registered components list.
	 *
	 * @return  array   The self-registered components list.
	 * @since    3.0.0
	 */
	public static function get_selfreg() {
		if ( self::is_present() ) {
			$result = \DecaLog\Engine::getLoggers();
			foreach ( $result as $slug => $loger ) {
				$result[ $slug ]['slug'] = $slug;
			}
			return $result;
		}
		return [];
	}

	/**
	 * Get the self-registered components list for display.
	 *
	 * @return  string  The output of the shortcode, ready to print.
	 * @since    3.0.0
	 */
	public static function sc_get_selfreg() {
		$result = '';
		foreach ( self::get_selfreg() as $logger ) {
			$result .= '<div style="margin:20px;min-width:200px;">';
			$result .= '<img style="width:48px;float:left;padding-right:6px;" src="' . ( '' !== $logger['icon'] ? $logger['icon'] : Core::get_base64_logo() ) . '" />';
			$result .= '<div style="padding-top: 4px"> <span>' . $logger['name'] . '</span><br /><span style="color:silver">' . $logger['version'] . '</span></div>';
			$result .= '</div>';
		}
		return '<div style="display:flex;flex-direction:row;flex-wrap:wrap;">' . $result . '</div>';
	}
}
