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
	 * The components' icons.
	 *
	 * @since  3.1.0
	 * @var    array    $icons    Maintains the icons list.
	 */
	private static $icons = [];

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
	 * Returns a base64 svg resource for the PHP icon.
	 *
	 * @param string $color Optional. Color of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_php_icon( $color = '#777BB3' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100%" height="100%"  viewBox="0 0 100 100">';
		$source .= '<g transform="translate(2,24) scale(0.94,0.94)">';
		$source .= '<path style="fill:' . $color . '" d="m7.579 10.123 14.204 0c4.169 0.035 7.19 1.237 9.063 3.604 1.873 2.367 2.491 5.6 1.855 9.699-0.247 1.873-0.795 3.71-1.643 5.512-0.813 1.802-1.943 3.427-3.392 4.876-1.767 1.837-3.657 3.003-5.671 3.498-2.014 0.495-4.099 0.742-6.254 0.742l-6.36 0-2.014 10.07-7.367 0 7.579-38.001 0 0m6.201 6.042-3.18 15.9c0.212 0.035 0.424 0.053 0.636 0.053 0.247 0 0.495 0 0.742 0 3.392 0.035 6.219-0.3 8.48-1.007 2.261-0.742 3.781-3.321 4.558-7.738 0.636-3.71 0-5.848-1.908-6.413-1.873-0.565-4.222-0.83-7.049-0.795-0.424 0.035-0.83 0.053-1.219 0.053-0.353 0-0.724 0-1.113 0l0.053-0.053"/>';
		$source .= '<path style="fill:' . $color . '" d="m41.093 0 7.314 0-2.067 10.123 6.572 0c3.604 0.071 6.289 0.813 8.056 2.226 1.802 1.413 2.332 4.099 1.59 8.056l-3.551 17.649-7.42 0 3.392-16.854c0.353-1.767 0.247-3.021-0.318-3.763-0.565-0.742-1.784-1.113-3.657-1.113l-5.883-0.053-4.346 21.783-7.314 0 7.632-38.054 0 0"/>';
		$source .= '<path style="fill:' . $color . '" d="m70.412 10.123 14.204 0c4.169 0.035 7.19 1.237 9.063 3.604 1.873 2.367 2.491 5.6 1.855 9.699-0.247 1.873-0.795 3.71-1.643 5.512-0.813 1.802-1.943 3.427-3.392 4.876-1.767 1.837-3.657 3.003-5.671 3.498-2.014 0.495-4.099 0.742-6.254 0.742l-6.36 0-2.014 10.07-7.367 0 7.579-38.001 0 0m6.201 6.042-3.18 15.9c0.212 0.035 0.424 0.053 0.636 0.053 0.247 0 0.495 0 0.742 0 3.392 0.035 6.219-0.3 8.48-1.007 2.261-0.742 3.781-3.321 4.558-7.738 0.636-3.71 0-5.848-1.908-6.413-1.873-0.565-4.222-0.83-7.049-0.795-0.424 0.035-0.83 0.053-1.219 0.053-0.353 0-0.724 0-1.113 0l0.053-0.053"/>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the WordPress icon.
	 *
	 * @param string $color Optional. Color of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_wordpress_icon( $color = '#0073AA' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100%" height="100%"  viewBox="0 0 100 100">';
		$source .= '<g transform="translate(-54,-26) scale(18,18)">';
		$source .= '<path style="fill:' . $color . '" d="m5.8465 1.9131c0.57932 0 1.1068 0.222 1.5022 0.58547-0.1938-0.0052-0.3872 0.11-0.3952 0.3738-0.0163 0.5333 0.6377 0.6469 0.2853 1.7196l-0.2915 0.8873-0.7939-2.3386c-0.0123-0.0362 0.002-0.0568 0.0465-0.0568h0.22445c0.011665 0 0.021201-0.00996 0.021201-0.022158v-0.13294c0-0.012193-0.00956-0.022657-0.021201-0.022153-0.42505 0.018587-0.8476 0.018713-1.2676 0-0.0117-0.0005-0.0212 0.01-0.0212 0.0222v0.13294c0 0.012185 0.00954 0.022158 0.021201 0.022158h0.22568c0.050201 0 0.064256 0.016728 0.076091 0.049087l0.3262 0.8921-0.4907 1.4817-0.8066-2.3758c-0.01-0.0298 0.0021-0.0471 0.0308-0.0471h0.25715c0.011661 0 0.021197-0.00996 0.021197-0.022158v-0.13294c0-0.012193-0.00957-0.022764-0.021197-0.022153-0.2698 0.014331-0.54063 0.017213-0.79291 0.019803 0.39589-0.60984 1.0828-1.0134 1.8639-1.0134l-0.0000029-0.0000062zm1.9532 1.1633c0.17065 0.31441 0.26755 0.67464 0.26755 1.0574 0 0.84005-0.46675 1.5712-1.1549 1.9486l0.6926-1.9617c0.1073-0.3036 0.2069-0.7139 0.1947-1.0443h-0.000004zm-1.2097 3.1504c-0.2325 0.0827-0.4827 0.1278-0.7435 0.1278-0.2247 0-0.4415-0.0335-0.6459-0.0955l0.68415-1.9606 0.70524 1.9284v-1e-7zm-1.6938-0.0854c-0.75101-0.35617-1.2705-1.1213-1.2705-2.0075 0-0.32852 0.071465-0.64038 0.19955-0.92096l1.071 2.9285 0.000003-0.000003zm0.95023-4.4367c1.3413 0 2.4291 1.0878 2.4291 2.4291s-1.0878 2.4291-2.4291 2.4291-2.4291-1.0878-2.4291-2.4291 1.0878-2.4291 2.4291-2.4291zm0-0.15354c1.4261 0 2.5827 1.1566 2.5827 2.5827s-1.1566 2.5827-2.5827 2.5827-2.5827-1.1566-2.5827-2.5827 1.1566-2.5827 2.5827-2.5827z"/>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the Jetpack icon.
	 *
	 * @param string $color Optional. Color of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_jetpack_icon( $color = '#00be28' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100%" height="100%"  viewBox="0 0 100 100">';
		$source .= '<g transform="translate(6,2) scale(0.19,0.19)">';
		$source .= '<path style="fill:#FFFFFF" d="M252.1 447.56S387.8 188.22 387.35 187.9c.44.32-145.7-.23-146.15-.54.45.3-1.2-161.78-1.2-161.78s-24.73.55-25.16.24c.43.3-130.88 262.4-131.32 262.1.44.3 131.75-.25 131.32-.56.43.3 9.23 156.9 8.8 156.6.43.3 28.45 3.6 28.45 3.6z"/>';
		$source .= '<path style="fill:' . $color . '" d="M240 0C107.63 0 0 107.63 0 240s107.63 240 240 240 240-107.63 240-240S372.37 0 240 0zm-12.37 279.85H108.1L227.62 47.18v232.67zm24.28 152.52V199.7h119.55L251.9 432.36z"/>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the WordPress icon.
	 *
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_blank_icon() {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100%" height="100%"  viewBox="0 0 100 100">';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Get the (mainly) self-registered icon for a specific component.
	 *
	 * @param   string  $component  The name of the component.
	 * @param   boolean $blank      Optional. Return a blank icon if not found.
	 * @return  string  The base64 encoded image.
	 * @since    3.1.0
	 */
	public static function get_icon( $component, $blank = true ) {
		if ( array_key_exists( $component, self::$icons ) ) {
			return self::$icons[ $component ];
		}
		$result = '';
		switch ( $component ) {
			case 'WordPress':
				$result = self::get_base64_wordpress_icon();
				break;
			case 'PHP':
				$result = self::get_base64_php_icon();
				break;
			case 'Jetpack':
				$result = self::get_base64_jetpack_icon();
				break;
			case 'DecaLog':
				$result = Core::get_base64_logo();
				break;
		}
		if ( '' === $result ) {
			foreach ( self::get_selfreg() as $logger ) {
				if ( $logger['name'] === $component ) {
					$result = $logger['icon'];
				}
			}
		}
		if ( '' === $result && $blank ) {
			$result = self::get_base64_blank_icon();
		}
		self::$icons[ $component ] = $result;
		return $result;
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
