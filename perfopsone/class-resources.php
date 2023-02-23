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
		public static $version = '2.3.0';

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
		 * Returns a base64 svg resource for the Hosterra logo.
		 *
		 * @return string The svg resource as a base64.
		 * @since 2.3.0
		 */
		public static function get_sponsor_base64_logo() {
			$source  = '<svg width="100%" height="100%" viewBox="0 0 107 107" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">';
			$source .= '<path d="M102.38,4.16C99.11,1.48 80.45,-0.23 78.74,0.02C75.99,0.42 74.94,2.46 73.57,6.59C72.69,9.24 70.95,16.17 69.56,21.84L62.28,21.73L66.79,30.25C65.93,31.67 65.02,33.06 64.07,34.42L54.34,33.39L58,42.11C57.04,43.25 56.12,44.4 55.19,45.54L46.73,43.12L48.94,52.81C47.65,54.2 46.38,55.65 45.11,57.1L37.3,54.87L39.26,63.46C37.71,65 36.11,66.43 34.43,67.67L27.59,63.59L27.45,71.35C26.82,71.55 26.17,71.72 25.51,71.86C17.16,73.59 0.04,55.85 0,55.83C0,55.83 7.79,90.91 35.73,88.01L30.85,97.38L31.34,98.7L34.04,106.6C34.09,106.75 34.25,106.83 34.4,106.78L37.61,105.68C37.76,105.63 37.84,105.47 37.79,105.32L36.47,101.47L48.15,87.73C49.46,87.51 50.78,87.24 52.11,86.9C53.97,86.43 55.75,85.73 57.45,84.85L68.23,97.08L59.65,97.08C59.02,94.8 56.96,93.1 54.47,93.05C51.43,92.99 48.92,95.39 48.86,98.43C48.8,101.47 51.2,103.98 54.24,104.04C56.72,104.09 58.85,102.49 59.58,100.25L85.78,100.25C85.83,100.25 86.27,100.22 86.63,99.99C87,99.77 87.16,99.49 87.24,99.35L92.51,86.8L93.97,94.24C92.73,95.27 91.96,96.84 92,98.57C92.06,101.61 94.58,104.01 97.61,103.95C100.65,103.88 103.05,101.37 102.99,98.34C102.93,95.3 100.41,92.9 97.38,92.96C97.2,92.96 97.02,92.98 96.84,93L90.56,61.12L92.88,60.79C95.32,62.04 97.66,60.55 98.41,59.98C99.1,59.76 99.56,59.07 99.46,58.32C99.35,57.48 98.58,56.9 97.74,57.01L97.19,57.09C95.49,55.32 91.66,51.51 90.7,50.52C87.6,47.32 84.28,44.32 80.61,41.79C80.38,41.63 80.14,41.47 79.89,41.32C80.58,38.31 82.06,32.54 83.28,27.87C84.36,24.73 85.42,22.45 86.33,21.89C87.72,21.04 91.43,22.84 95.67,22.59C99.91,22.35 101.78,21.74 102.93,20.63C105.88,17.77 95.67,17.69 95.67,17.69C87.01,17.08 87.31,13.32 87.31,13.32C89.64,13.94 95.98,14.55 98.94,14.3C101.82,14.06 104.05,13.55 104.77,12.54C105.38,11.5 105.67,6.83 102.4,4.15L102.38,4.16ZM91.42,81.29L84.31,97.08L80.12,97.08L80.12,93.8C80.12,93.65 80,93.52 79.84,93.52L74.54,93.52L66.13,78.05C67.85,76.15 69.36,74.06 70.64,71.87C73.34,67.25 75.12,62.16 76.38,56.98C76.51,56.43 76.65,55.87 76.79,55.31L79.61,59.58L78.45,59.74C77.61,59.85 77.03,60.62 77.14,61.46C77.25,62.3 78.02,62.88 78.86,62.77L80.99,62.47C83.3,63.96 85.44,62.11 85.77,61.8L87.53,61.55L91.42,81.3L91.42,81.29ZM90.71,58.01L88.69,58.29L88.52,58.29C88.47,58.29 88.43,58.31 88.38,58.33L85.36,58.76L81.79,51.48C83.59,52.7 85.38,53.92 87.18,55.15C88.44,56 89.69,56.89 90.71,58.01Z" style="fill:rgb(0,102,122);fill-rule:nonzero;"/>';
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

