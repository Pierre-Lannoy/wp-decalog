<?php
/**
 * Blog (site) handling
 *
 * Handles all Blog (site) operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the Blog (site) functionality.
 *
 * Handles all Blog (site) operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Blog {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a blog name.
	 *
	 * @param   integer $id         Optional. The blog id.
	 * @param   string  $default    Optional. Default value to return if blog is not detected.
	 * @return  string  The blog name if detected, $default otherwise.
	 * @since   1.0.0
	 */
	public static function get_blog_name( $id = null, $default = 'unknown' ) {
		if ( $id && is_numeric( $id ) && $id > 0 && Environment::is_wordpress_multisite() ) {
			$blog_info = get_blog_details( $id );
			if ( is_object( $blog_info ) ) {
				return $blog_info->blogname;
			}
			return '- deleted site -';

		} elseif ( $id && is_numeric( $id ) && $id > 0 ) {
			return get_bloginfo( 'name' );

		} else {
			return $default;
		}
	}

	/**
	 * Get a blog url.
	 *
	 * @param   integer $id         Optional. The blog id.
	 * @param   string  $default    Optional. Default value to return if blog is not detected.
	 * @return  string  The blog url (without scheme) if detected, $default otherwise.
	 * @since   1.0.0
	 */
	public static function get_blog_url( $id = null, $default = 'wordpress.org' ) {
		if ( $id && is_numeric( $id ) && $id > 0 && Environment::is_wordpress_multisite() ) {
			$url_parts = wp_parse_url( get_blog_option( $id, 'siteurl', 'https://wordpress.org' ) );

		} elseif ( $id && is_numeric( $id ) && $id > 0 ) {
			$url_parts         = wp_parse_url( get_option( 'siteurl', 'https://wordpress.org' ) );
			$url_parts['path'] = '';

		} else {
			return $default;
		}
		$site = '';
		if ( array_key_exists( 'host', $url_parts ) && isset( $url_parts['host'] ) ) {
			$site .= $url_parts['host'];
		}
		if ( array_key_exists( 'path', $url_parts ) && isset( $url_parts['path'] ) ) {
			$site .= $url_parts['path'];
		}
		return $site;
	}

	/**
	 * Verify if a blog exist.
	 *
	 * @param   integer $id         The blog id.
	 * @return  boolean  True if the blog exists, false otherwise.
	 * @since   1.0.0
	 */
	public static function is_blog_exists( $id ) {
		$result = false;
		if ( $id && is_numeric( $id ) && $id > 0 && Environment::is_wordpress_multisite() ) {
			$blog_info = get_blog_details( $id );
			if ( is_object( $blog_info ) ) {
				$result = $id === $blog_info->id;
			}
		}
		return $result;
	}

	/**
	 * Get a fully qualified blog name.
	 *
	 * @param   mixed $id         Optional. The blog id.
	 * @return  string  The blog name if detected, $default otherwise.
	 * @since   1.3.0
	 */
	public static function get_full_blog_name( $id = 0 ) {
		if ( is_numeric( $id ) ) {
			return sprintf( '"%s" (site ID %s)', self::get_blog_name( $id ), $id );
		}
		if ( $id instanceof \WP_Site ) {
			return sprintf( '"%s" (site ID %s)', (string) $id->blogname, $id->id );
		}
		return 'unknown site';
	}

	/**
	 * Get the current blog id.
	 *
	 * @param   mixed   $default    Optional. Default value to return if blog is not detected.
	 * @return  mixed|integer The blog id if detected, null otherwise.
	 * @since   1.0.0
	 */
	public static function get_current_blog_id( $default = null ) {
		$blog_id = $default;
		$id      = get_current_blog_id();
		if ( $id && is_numeric( $id ) && $id > 0 ) {
			$blog_id = $id;
		}
		return $blog_id;
	}

	/**
	 * Get the current blog name.
	 *
	 * @param   string  $default    Optional. Default value to return if blog is not detected.
	 * @return  string  The current blog name if detected, "anonymous" otherwise.
	 * @since   1.0.0
	 */
	public static function get_current_blog_name( $default = 'unknown' ) {
		return self::get_blog_name( self::get_current_blog_id(), $default );
	}
	
	/**
	 * Get the current blog url.
	 *
	 * @param   string  $default    Optional. Default value to return if blog is not detected.
	 * @return  string  The current blog url if detected, "wordpress.org" otherwise.
	 * @since   3.2.0
	 */
	public static function get_current_blog_url( $default = 'wordpress.org' ) {
		return self::get_blog_url( self::get_current_blog_id(), $default );
	}

}
