<?php
/**
 * Standard PerfOps One resources handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace PerfOpsOne;

use Feather\Icons;

/**
 * Standard PerfOps One resources handling.
 *
 * This class defines all code necessary to initialize and handle PerfOps One admin menus.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

if ( ! class_exists( 'PerfOpsOne\Resources' ) ) {
	class Resources {

		/**
		 * The PerfOps One admin menus.
		 *
		 * @since  2.0.0
		 * @var    array $menus Maintains the PerfOps One admin menus.
		 */
		private static $menus = [];

		/**
		 * The PerfOps One internal version.
		 *
		 * @since  2.1.0
		 * @var    string $version Maintains the PerfOps One internal version.
		 */
		public static $version = '2.2.1';

		/**
		 * Returns a base64 svg resource for the PerfOps One logo.
		 *
		 * @return string The svg resource as a base64.
		 * @since 2.0.0
		 */
		public static function get_base64_logo() {
			$source  = '<svg width="100%" height="100%" viewBox="0 0 3000 3000" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-miterlimit:10;">';
			$source .= '<g id="PerfOps-One" transform="matrix(27.8636,0,0,27.8636,467.713,-452.787)">';
			$source .= '<g transform="matrix(-3.04913,22.8685,22.8685,3.04913,37.8651,84.512)">';
			$source .= '<path d="M0.086,-0.481L0.61,-0.481L0.473,0.55L-0.034,0.412L0.086,-0.481Z" style="fill:url(#_Linear1);fill-rule:nonzero;"/>';
			$source .= '</g>';
			$source .= '<g transform="matrix(0,-65.5897,-65.5897,0,37,97.8667)">';
			$source .= '<path d="M0.821,0.471C0.821,0.497 0.8,0.518 0.774,0.518L0.198,0.518C0.172,0.518 0.15,0.497 0.15,0.471L0.15,-0.471C0.15,-0.497 0.172,-0.518 0.198,-0.518L0.774,-0.518C0.8,-0.518 0.821,-0.497 0.821,-0.471L0.821,0.471Z" style="fill:url(#_Linear2);fill-rule:nonzero;"/>';
			$source .= '</g>';
			$source .= '<g transform="matrix(0,-19.9701,-19.9701,0,37,96.9333)">';
			$source .= '<path d="M0.748,1.703L0.573,1.703C0.476,1.703 0.397,1.633 0.397,1.548L0.397,-1.548C0.397,-1.633 0.476,-1.703 0.573,-1.703L0.748,-1.703L0.748,1.703Z" style="fill:url(#_Linear3);fill-rule:nonzero;"/>';
			$source .= '</g>';
			$source .= '<g opacity="0.4">';
			$source .= '<g transform="matrix(1,0,0,1,0,26)">';
			$source .= '<rect x="7" y="21" width="61" height="32" style="fill:white;"/>';
			$source .= '</g>';
			$source .= '</g>';
			$source .= '<g transform="matrix(27,0,0,-27,23.5,65.5)">';
			$source .= '<rect x="-0.537" y="-0.5" width="2.074" height="1" style="fill:url(#_Linear4);"/>';
			$source .= '</g>';
			$source .= '<g transform="matrix(6.26387,0,0,-6.26387,33.1667,97)">';
			$source .= '<rect x="-2.102" y="-0.16" width="5.428" height="0.319" style="fill:url(#_Linear5);"/>';
			$source .= '</g>';
			$source .= '<g transform="matrix(1,0,0,1,9,66.144)">';
			$source .= '<path d="M0,-0.644L14.974,-0.644L21.342,-11.048L28,10.404L35.397,-2.911L36.877,0.356L56,0.356" style="fill:none;fill-rule:nonzero;stroke:white;stroke-width:0.21px;"/>';
			$source .= '</g>';
			$source .= '</g>';
			$source .= '<defs>';
			$source .= '<linearGradient id="_Linear1" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(0.991228,0.132164,0.132164,-0.991228,0.00248874,-0.0374963)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,-2.22045e-16)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,4.44089e-16)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear4" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(0,1,1,0,0.5,-0.5)"><stop offset="0" style="stop-color:rgb(65,172,255);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(202,238,252);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear5" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(0,1,1,0,0.611975,-0.611975)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '</defs>';
			$source .= '</svg>';

			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $source );
		}

		/**
		 * Returns a base64 svg resource for the PerfOps One menu item.
		 *
		 * @return string The svg resource as a base64.
		 * @since 2.0.0
		 */
		public static function get_menu_base64_logo() {
			$source  = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">';
			$source .= '<g id="perfopsone">';
			$source .= '<path fill="#9DA1A7" d="M92 57c0 2.2-1.8 4-4 4H69.2c-1.7 0-3.2-.9-3.8-2.6l-3-8.4-9.8 33.1c-.5 1.7-2.1 2.9-3.8 2.9h-.1c-1.8-.1-3.4-1.3-3.8-3.1L32 27l-7.8 31.1c-.4 1.8-2 2.9-3.9 2.9H4c-2.2 0-4-1.8-4-4s1.8-4 4-4h13.2L28.3 8.9c.4-1.8 2.1-2.9 3.9-2.9 1.8 0 3.4 1.3 3.9 3.1l13.2 57.2 9-30.4c.5-1.7 2-2.9 3.7-2.9 1.7-.1 3.3 1 3.9 2.6L72 53h16c2.2 0 4 1.8 4 4z"/>';
			$source .= '</g>';
			$source .= '</svg>';

			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $source );
		}
	}
}

