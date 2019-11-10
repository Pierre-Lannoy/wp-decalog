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
		return sprintf( '%s (user ID %s)', $title, $id );
	}

}
