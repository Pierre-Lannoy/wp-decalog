<?php
/**
 * Hosting environment handling.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * The class responsible to manage and detect hosting environment.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Hosting {


	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Check if Cloudflare Geoip is enabled.
	 *
	 * @return bool    True if Cloudflare Geoip is enabled.
	 * @since  1.0.0
	 */
	public static function is_cloudflare_geoip_enabled() {
		return array_key_exists( 'HTTP_CF_IPCOUNTRY', $_SERVER ) || array_key_exists( 'CF-IPCountry', $_SERVER );
	}

	/**
	 * Check if Cloudfront (AWS) Geoip is enabled.
	 *
	 * @return bool    True if Cloudfront Geoip is enabled.
	 * @since  1.0.0
	 */
	public static function is_cloudfront_geoip_enabled() {
		return array_key_exists( 'CloudFront-Viewer-Country', $_SERVER );
	}

	/**
	 * Check if Google LB Geoip is enabled.
	 *
	 * @return bool    True if Google Geoip is enabled.
	 * @since  2.3.0
	 */
	public static function is_googlelb_geoip_enabled() {
		return array_key_exists( 'X-Client-Geo-Location', $_SERVER );
	}

	/**
	 * Check if Apache Geoip is enabled.
	 *
	 * @return bool    True if Cloudfront Geoip is enabled.
	 * @since  1.0.0
	 */
	public static function is_apache_geoip_enabled() {
		return array_key_exists( 'GEOIP_COUNTRY_CODE', $_SERVER );
	}

}
