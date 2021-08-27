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
	 * Returns a base64 svg resource for the Stripe icon.
	 *
	 * @param string $color Optional. Color of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_stripe_icon( $color = '#6772e5' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  viewBox="0 0 32 32" width="64" height="64" fill="' . $color . '">';
		$source .= '<g transform="translate(0,0) scale(0.8,0.8)">';
		$source .= '<path d="M111.328 15.602c0-4.97-2.415-8.9-7.013-8.9s-7.423 3.924-7.423 8.863c0 5.85 3.32 8.8 8.036 8.8 2.318 0 4.06-.528 5.377-1.26V19.22a10.246 10.246 0 0 1-4.764 1.075c-1.9 0-3.556-.67-3.774-2.943h9.497a39.64 39.64 0 0 0 .063-1.748zm-9.606-1.835c0-2.186 1.35-3.1 2.56-3.1s2.454.906 2.454 3.1zM89.4 6.712a5.434 5.434 0 0 0-3.801 1.509l-.254-1.208h-4.27v22.64l4.85-1.032v-5.488a5.434 5.434 0 0 0 3.444 1.265c3.472 0 6.64-2.792 6.64-8.957.003-5.66-3.206-8.73-6.614-8.73zM88.23 20.1a2.898 2.898 0 0 1-2.288-.906l-.03-7.2a2.928 2.928 0 0 1 2.315-.96c1.775 0 2.998 2 2.998 4.528.003 2.593-1.198 4.546-2.995 4.546zM79.25.57l-4.87 1.035v3.95l4.87-1.032z" fill-rule="evenodd"/>';
		$source .= '<path d="M74.38 7.035h4.87V24.04h-4.87z"/>';
		$source .= '<path d="M69.164 8.47l-.302-1.434h-4.196V24.04h4.848V12.5c1.147-1.5 3.082-1.208 3.698-1.017V7.038c-.646-.232-2.913-.658-4.048 1.43zm-9.73-5.646L54.698 3.83l-.02 15.562c0 2.87 2.158 4.993 5.038 4.993 1.585 0 2.756-.302 3.405-.643v-3.95c-.622.248-3.683 1.138-3.683-1.72v-6.9h3.683V7.035h-3.683zM46.3 11.97c0-.758.63-1.05 1.648-1.05a10.868 10.868 0 0 1 4.83 1.25V7.6a12.815 12.815 0 0 0-4.83-.888c-3.924 0-6.557 2.056-6.557 5.488 0 5.37 7.375 4.498 7.375 6.813 0 .906-.78 1.186-1.863 1.186-1.606 0-3.68-.664-5.307-1.55v4.63a13.461 13.461 0 0 0 5.307 1.117c4.033 0 6.813-1.992 6.813-5.485 0-5.796-7.417-4.76-7.417-6.943zM13.88 9.515c0-1.37 1.14-1.9 2.982-1.9A19.661 19.661 0 0 1 25.6 9.876v-8.27A23.184 23.184 0 0 0 16.862.001C9.762.001 5 3.72 5 9.93c0 9.716 13.342 8.138 13.342 12.326 0 1.638-1.4 2.146-3.37 2.146-2.905 0-6.657-1.202-9.6-2.802v8.378A24.353 24.353 0 0 0 14.973 32C22.27 32 27.3 28.395 27.3 22.077c0-10.486-13.42-8.613-13.42-12.56z" fill-rule="evenodd"/>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the Amelia Stripe Gateway icon.
	 *
	 * @param string $color1 Optional. Color 1 of the icon.
	 * @param string $color2 Optional. Color 2 of the icon.
	 * @param string $color3 Optional. Color 3 of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_amelia_icon( $color1 = '#2287ef', $color2 = '#47aafc', $color3 = '#045af7' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100%" height="100%"  viewBox="0 0 100 100">';
		$source .= '<g transform="scale(0.19,-0.19) translate(0,-500)">';
		$source .= '<path d="M 160.828851 299.768638 C 178.594187 305.690418 197.957501 291.093660 203.211658 272.522544 L 203.211658 104.553794 C 200.572806 97.322965 198.754575 90.919519 193.934314 86.975669 C 179.769999 75.386684 102.709908 33.122263 82.117908 22.034263 C 68.450725 14.675011 53.817422 1.887280 37.684314 1.038169 C 24.223960 0.329730 8.218195 9.553713 3.797595 22.815501 C 1.137674 30.795277 1.063220 39.092885 1.063220 49.866294 C 3.541358 74.794601 3.118697 148.405207 1.063220 174.866294 C 1.063220 187.357829 -0.055376 199.595042 7.117909 208.362391 C 9.552013 211.337405 12.333874 214.316787 15.321031 216.760827 C 23.296875 223.286515 152.092726 296.856598 160.828851 299.768638 Z" transform="scale(1.000000,1.000000) translate(26.280530,182.555581)" fill="' . $color1 . '"></path>';
		$source .= '<path d="M 1.000000 126.000000 C 3.055476 99.538913 3.478138 25.928307 1.000000 1.000000 L 1.000000 63.500000 Z" transform="scale(1.000000,1.000000) translate(26.343750,231.421875)" fill="' . $color1 . '"></path>';
		$source .= '<path d="M 160.593453 224.609420 C 169.072913 224.609420 175.331379 224.742351 181.589546 222.656295 C 194.858336 218.233366 283.135777 164.597147 304.441097 153.125034 C 320.363504 144.551448 339.436521 133.858198 338.327828 112.793014 C 337.285097 92.981140 322.558481 83.493534 306.784871 75.000034 C 284.278175 62.881062 191.041144 4.252993 175.241890 1.464889 C 157.432522 -1.677941 142.687647 11.857679 128.073918 19.726620 C 112.808495 27.946453 16.023154 82.241781 10.202828 89.355514 C 2.413027 98.876382 -0.477217 109.889949 1.706737 122.265682 C 4.717603 139.327262 24.812870 147.853470 38.230168 155.078159 C 55.884186 164.584178 149.316420 221.151773 160.593453 224.609420 Z" transform="scale(1.000000,1.000000) translate(78.664360,13.671830)" fill="' . $color2 . '"></path>';
		$source .= '<path d="M 88.109387 275.187111 C 103.151320 267.087601 187.780525 220.315600 194.261713 212.394143 C 201.849066 203.120717 203.148438 193.891732 203.148438 179.776954 C 200.662690 154.742107 201.074198 67.941437 203.148438 41.105079 C 203.148438 24.693892 199.850790 23.767596 193.382812 10.831641 C 185.126721 2.791197 171.674629 -0.244442 162.035150 1.456635 C 147.460655 4.028612 57.947465 59.381864 36.937488 70.694925 C 23.829804 77.752912 1.000000 88.012442 1.000000 108.487891 L 1.000000 272.550391 C 6.931916 290.083865 22.083569 303.555175 40.062500 300.382423 C 56.170965 297.539752 73.693022 282.949761 88.109387 275.187111 Z" transform="scale(1.000000,1.000000) translate(265.601562,182.527734)" fill="' . $color3 . '"></path>';
		$source .= '<path d="M 2.713463 139.671875 L 2.713463 70.335938 L 2.713463 1.000000 C 0.639223 27.836358 0.227715 114.637028 2.713463 139.671875 Z" transform="scale(1.000000,1.000000) translate(466.036537,222.632812)" fill="' . $color3 . '"></path>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the WooCommerce icon.
	 *
	 * @param string $color1 Optional. Color 1 of the icon.
	 * @param string $color2 Optional. Color 2 of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_woo_icon( $color1 = '#7f54b3', $color2 = '#fff' ) {
		$source  = '<svg preserveAspectRatio="xMidYMid" version="1.1" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">';
		$source .= '<g transform="scale(1,1) translate(0,40)">';
		$source .= '<path d="m23.759 0h208.38c13.187 0 23.863 10.675 23.863 23.863v79.542c0 13.187-10.675 23.863-23.863 23.863h-74.727l10.257 25.118-45.109-25.118h-98.695c-13.187 0-23.863-10.675-23.863-23.863v-79.542c-0.10466-13.083 10.571-23.863 23.758-23.863z" fill="' . $color1 . '"/>';
		$source .= '<path d="m14.578 21.75c1.4569-1.9772 3.6423-3.0179 6.5561-3.226 5.3073-0.41626 8.3252 2.0813 9.0537 7.4927 3.226 21.75 6.7642 40.169 10.511 55.259l22.79-43.395c2.0813-3.9545 4.6829-6.0358 7.8049-6.2439 4.5789-0.3122 7.3886 2.6016 8.5333 8.7415 2.6016 13.841 5.9317 25.6 9.8862 35.59 2.7057-26.433 7.2846-45.476 13.737-57.236 1.561-2.9138 3.8504-4.3707 6.8683-4.5789 2.3935-0.20813 4.5789 0.52033 6.5561 2.0813 1.9772 1.561 3.0179 3.5382 3.226 5.9317 0.10406 1.8732-0.20813 3.4341-1.0407 4.9951-4.0585 7.4927-7.3886 20.085-10.094 37.567-2.6016 16.963-3.5382 30.179-2.9138 39.649 0.20813 2.6016-0.20813 4.8911-1.2488 6.8683-1.2488 2.2894-3.122 3.5382-5.5154 3.7463-2.7057 0.20813-5.5154-1.0406-8.2211-3.8504-9.678-9.8862-17.379-24.663-22.998-44.332-6.7642 13.32-11.759 23.311-14.985 29.971-6.1398 11.759-11.343 17.795-15.714 18.107-2.8098 0.20813-5.2033-2.1854-7.2846-7.1805-5.3073-13.633-11.031-39.961-17.171-78.985-0.41626-2.7057 0.20813-5.0992 1.665-6.9724zm223.64 16.338c-3.7463-6.5561-9.2618-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0.10406-8.1171-1.665-14.881-4.9951-20.501zm-13.112 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93659-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0 2.7057-0.20813 5.3073-0.72846 7.7008zm-52.033-28.826c-3.7463-6.5561-9.3659-10.511-16.65-12.072-1.9772-0.41626-3.8504-0.62439-5.6195-0.62439-9.9902 0-18.107 5.2033-24.455 15.61-5.4114 8.8455-8.1171 18.628-8.1171 29.346 0 8.013 1.665 14.881 4.9951 20.605 3.7463 6.5561 9.2618 10.511 16.65 12.072 1.9772 0.41626 3.8504 0.62439 5.6195 0.62439 10.094 0 18.211-5.2033 24.455-15.61 5.4114-8.9496 8.1171-18.732 8.1171-29.45 0-8.1171-1.665-14.881-4.9951-20.501zm-13.216 28.826c-1.4569 6.8683-4.0585 11.967-7.9089 15.402-3.0179 2.7057-5.8276 3.8504-8.4293 3.3301-2.4976-0.52033-4.5789-2.7057-6.1398-6.7642-1.2488-3.226-1.8732-6.452-1.8732-9.4699 0-2.6016 0.20813-5.2033 0.72846-7.5967 0.93658-4.2667 2.7057-8.4293 5.5154-12.384 3.4341-5.0992 7.0764-7.1805 10.823-6.452 2.4976 0.52033 4.5789 2.7057 6.1398 6.7642 1.2488 3.226 1.8732 6.452 1.8732 9.4699 0.10406 2.7057-0.20813 5.3073-0.72846 7.7008z" fill="' . $color2 . '"/>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the BuddyPress icon.
	 *
	 * @param string $color Optional. Color of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_buddypress_icon( $color = '#d84800' ) {
		$source  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" preserveAspectRatio="xMidYMid meet">';
		$source .= '<g transform="translate(0,-924.36218) matrix(0.02335871,0,0,-0.02334121,-0.11965895,1052.4471)" style="fill:' . $color . '">';
		$source .= '<path d="M 2515,5484 C 1798,5410 1171,5100 717,4595 332,4168 110,3689 23,3105 -1,2939 -1,2554 24,2385 111,1783 363,1266 774,842 1492,102 2529,-172 3521,116 c 448,130 858,379 1195,726 413,426 667,949 751,1548 24,173 24,548 -1,715 -91,625 -351,1150 -781,1580 -425,425 -943,685 -1555,780 -101,16 -520,29 -615,19 z m 611,-143 C 4158,5186 4999,4440 5275,3435 5501,2611 5302,1716 4747,1055 4319,547 3693,214 3028,141 c -125,-14 -441,-14 -566,0 -140,15 -338,55 -468,95 C 722,621 -58,1879 161,3188 c 41,249 115,474 234,717 310,631 860,1110 1528,1330 213,70 374,102 642,129 96,10 436,-4 561,-23 z" />';
		$source .= '<path d="M 2575,5090 C 1629,5020 813,4386 516,3490 384,3089 362,2641 456,2222 643,1386 1307,696 2134,479 c 233,-61 337,-73 611,-73 274,0 378,12 611,73 548,144 1038,500 1357,986 193,294 315,629 363,995 20,156 15,513 -10,660 -42,241 -108,448 -215,665 -421,857 -1325,1375 -2276,1305 z m 820,-491 c 270,-48 512,-261 608,-537 26,-76 31,-104 35,-222 4,-115 1,-149 -17,-220 -62,-250 -237,-457 -467,-553 -63,-27 -134,-48 -134,-41 0,2 15,35 34,72 138,274 138,610 0,883 -110,220 -334,412 -564,483 -30,10 -62,20 -70,23 -21,7 77,56 175,88 126,41 255,49 400,24 z m -610,-285 c 310,-84 541,-333 595,-641 18,-101 8,-278 -20,-368 -75,-236 -220,-401 -443,-505 -109,-51 -202,-70 -335,-70 -355,0 -650,217 -765,563 -28,84 -31,104 -31,232 -1,118 3,152 22,220 89,306 335,528 650,585 67,13 257,3 327,-16 z M 4035,2940 c 301,-95 484,-325 565,-710 21,-103 47,-388 37,-414 -6,-14 -30,-16 -182,-16 -96,0 -175,3 -175,6 0,42 -37,236 -60,313 -99,334 -315,586 -567,661 -24,7 -43,17 -43,21 0,5 32,45 72,90 l 72,82 106,-6 c 67,-3 130,-13 175,-27 z m -1703,-510 258,-255 92,90 c 51,49 183,178 293,286 l 200,197 75,-9 c 207,-26 404,-116 547,-252 170,-161 267,-361 308,-632 15,-100 21,-394 9,-454 l -6,-31 -1519,0 c -1074,0 -1520,3 -1524,11 -14,21 -18,297 -6,407 59,561 364,896 866,950 97,10 55,41 407,-308 z" />';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the Amelia Stripe Gateway icon.
	 *
	 * @param string $color1 Optional. Color 1 of the icon.
	 * @param string $color2 Optional. Color 2 of the icon.
	 * @param string $color3 Optional. Color 3 of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private static function get_base64_w3tc_icon( $color1 = '#3b7e83', $color2 = '#3b7e83', $color3 = '#3b7e83' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100" height="100"  viewBox="0 0 100 100">';
		$source .= '<g transform="scale(6.4,6.4) translate(0,0)">';
		$source .= '<path fill="' . $color1 . '" d="M10.39 6.69C10.79 6.9 11.26 6.9 11.67 6.7C12.35 6.36 13.75 5.68 14.43 5.34C14.71 5.2 14.71 4.8 14.43 4.65C13.12 3.96 9.87 2.25 8.56 1.57C8.11 1.33 7.57 1.3 7.09 1.49C6.33 1.8 4.85 2.4 4.11 2.7C3.78 2.84 3.76 3.29 4.07 3.46C5.46 4.17 8.97 5.96 10.39 6.69Z"/>';
		$source .= '<path fill="' . $color2 . '" d="M9.02 14.58C8.7 14.76 8.33 14.45 8.46 14.11C8.97 12.77 10.26 9.32 10.81 7.87C10.92 7.57 11.13 7.33 11.41 7.19C12.17 6.8 13.89 5.92 14.62 5.54C14.83 5.44 15.06 5.64 14.99 5.86C14.55 7.17 13.45 10.49 13.02 11.79C12.89 12.19 12.62 12.53 12.25 12.73C11.42 13.21 9.78 14.15 9.02 14.58Z"/>';
		$source .= '<path fill="' . $color3 . '" d="M3.95 3.7L10.24 6.91L10.39 7.01L10.5 7.13L10.58 7.28L10.62 7.45L10.62 7.62L10.58 7.79L8.23 14.02L8.14 14.18L8.02 14.3L7.87 14.37L7.7 14.41L7.53 14.39L7.36 14.33L1.64 10.97L1.39 10.78L1.2 10.55L1.07 10.28L1 9.99L1 9.68L1.07 9.38L3.04 4.06L3.13 3.89L3.26 3.76L3.42 3.67L3.59 3.63L3.77 3.64L3.95 3.7ZM3.76 9.39L4.66 8.34L4.66 9.93L5.06 10.11L6.23 8.91L7.38 9.51L6.79 9.86L6.91 10.05L6.98 10.2L7.02 10.33L7.01 10.42L6.95 10.49L6.84 10.53L6.74 10.51L6.62 10.43L6.48 10.29L6.3 10.11L6.15 10.11L6.01 10.1L5.89 10.1L5.79 10.11L5.7 10.11L6.1 10.65L6.47 11.04L6.82 11.27L7.15 11.35L7.45 11.28L7.76 11.03L7.88 10.74L7.86 10.47L7.75 10.24L7.61 10.11L7.7 10.04L7.82 9.94L7.97 9.82L8.17 9.68L8.39 9.51L6.18 8.19L5.22 9.16L5.13 7.66L4.73 7.44L3.9 8.42L3.9 6.9L3.28 6.58L3.28 9.09L3.76 9.39Z"/>';
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
			case 'W3 Total Cache':
				$result = self::get_base64_w3tc_icon();
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
			case 'Amelia Stripe Gateway':
				$result = self::get_base64_amelia_icon();
				break;
			case 'Standard Stripe Gateway':
			case 'Forminator Stripe Gateway':
				$result = self::get_base64_stripe_icon();
				break;
			case 'Action Scheduler':
			case 'WooCommerce':
				$result = self::get_base64_woo_icon();
				break;
			case 'BuddyPress':
				$result = self::get_base64_buddypress_icon();
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
