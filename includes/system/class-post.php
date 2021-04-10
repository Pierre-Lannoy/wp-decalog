<?php
/**
 * Posts handling
 *
 * Handles all post operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;


/**
 * Define the post functionality.
 *
 * Handles all post operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Post {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a user nice name.
	 *
	 * @param   integer $id         Optional. The post id.
	 * @param   string  $default    Optional. Default value to return if post has no title.
	 * @return  string  The user nice name if detected, $default otherwise.
	 * @since   1.0.0
	 */
	public static function get_post_title( $id = 0, $default = 'untitled' ) {
		$title = get_the_title( $id );
		if ( '' !== $id ) {
			return $title;

		} else {
			return $default;
		}
	}

	/**
	 * Get a user string representation.
	 *
	 * @param   integer $id         Optional. The user id.
	 * @return  string  The post string representation, ready to be inserted in a log.
	 * @since   1.0.0
	 */
	public static function get_post_string( $id = 0 ) {
		$post  = get_post( $id );
		$id    = isset( $post->ID ) ? $post->ID : 0;
		$title = self::get_post_title( $id );
		return sprintf( '"%s" (post ID %s)', $title, $id );
	}

	/**
	 * Get post types.
	 *
	 * @return  array  An array of post type names.
	 * @since   3.0.0
	 */
	public static function get_post_types() {
		$cache_id = 'data/comment_types';
		$types    = Cache::get( $cache_id, true );
		if ( ! $types ) {
			$types = get_post_types();
			global $wpdb;
			$sql = 'SELECT DISTINCT `post_type` FROM `' . $wpdb->posts . '`;';
			// phpcs:ignore
			$comt = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $comt as $t ) {
				if ( ! array_key_exists( $t['post_type'], $types ) ) {
					$types[ strtolower( $t['post_type'] ) ] = strtolower( str_replace( '_', ' ', $t['post_type'] ) );
				}
			}
			Cache::set( $cache_id, $types, 'longquery', true );
		}
		return $types;
	}

}
