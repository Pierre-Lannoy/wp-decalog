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

	/**
	 * Get comment types (like get_post_types() do for posts).
	 *
	 * @return  array  An array of comment type names.
	 * @since   3.0.0
	 */
	public static function get_types() {
		$cache_id = 'data/comment_types';
		$types    = Cache::get( $cache_id, true );
		if ( ! $types ) {
			$types = [
				'comment'   => 'comment',
				'trackback' => 'trackback',
				'pingback'  => 'pingback',
				'pings'     => 'pings',
			];
			global $wpdb;
			$sql = 'SELECT DISTINCT `comment_type` FROM `' . $wpdb->comments . '`;';
			// phpcs:ignore
			$comt = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $comt as $t ) {
				if ( ! array_key_exists( $t['comment_type'], $types ) ) {
					$types[ strtolower( $t['comment_type'] ) ] = strtolower( str_replace( '_', ' ', $t['comment_type'] ) );
				}
			}
			Cache::set( $cache_id, $types, 'longquery', true );
		}
		return $types;
	}

	/**
	 * Get comment status (like get_post_status() do for posts).
	 *
	 * @return  array  An array of comment status names.
	 * @since   3.0.0
	 */
	public static function get_status() {
		$cache_id = 'data/comment_status';
		$status   = Cache::get( $cache_id, true );
		if ( ! $status ) {
			$status = [
				'hold'    => 'Unapproved',
				'approve' => 'Approved',
				'spam'    => 'Spam',
				'trash'   => 'Trash',
			];
			global $wpdb;
			$sql = 'SELECT DISTINCT `comment_approved` FROM `' . $wpdb->comments . '`;';
			// phpcs:ignore
			$comt = $wpdb->get_results( $sql, ARRAY_A );
			foreach ( $comt as $t ) {
				if ( ! array_key_exists( $t['comment_approved'], $status ) && ! is_numeric( $t['comment_approved'] ) ) {
					$status[ strtolower( $t['comment_approved'] ) ] = strtolower( str_replace( '_', ' ', $t['comment_approved'] ) );
				}
			}
			Cache::set( $cache_id, $status, 'longquery', true );
		}
		return $status;
	}

}
