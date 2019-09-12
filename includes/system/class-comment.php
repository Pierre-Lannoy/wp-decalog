<?php
/**
 * Comments handling
 *
 * Handles all comments operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.4.0
 */

namespace Decalog\System;

/**
 * Define the comments functionality.
 *
 * Handles all comments operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.4.0
 */
class Comment {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a fully qualified comment name.
	 *
	 * @param   mixed $id         Optional. The comment id or WP_comment instance.
	 * @return  string  The comment pseudo name if detected.
	 * @since   1.4.0
	 */
	public static function get_full_comment_name( $id ) {
		$comment = null;
		if ( is_numeric( $id ) ) {
			$comment = get_comment( $id );
		}
		if ( $id instanceof \WP_Comment ) {
			$comment = $id;
		}
		if ( $comment instanceof \WP_Comment ) {
			return sprintf( '"%s" (comment ID %s)', wp_trim_words( wp_kses( $comment->comment_content, [] ), 8 ), $comment->comment_ID );
		} else {
			return 'unknow comment';
		}
	}

}
