<?php
/**
 * bbPress listener for DecaLog.
 *
 * Defines class for bbPress listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;

/**
 * bbPress listener for DecaLog.
 *
 * Defines methods and properties for bbPress listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class bbPressListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    2.4.0
	 */
	protected function init() {
		$this->id      = 'bbpress';
		$this->class   = 'plugin';
		$this->product = 'bbPress';
		$this->name    = 'bbPress';
		if ( function_exists( 'bbp_get_version' ) ) {
			$this->version = bbp_get_version();
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    2.4.0
	 */
	protected function is_available() {
		return class_exists( 'bbPress' ) && function_exists( 'bbp_get_version' );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    2.4.0
	 */
	protected function launch() {
		add_filter( 'bbp_toggle_forum_action_admin', [ $this, 'bbp_toggle_forum_action_admin' ], 10, 3 );
		add_filter( 'bbp_toggle_topic', [ $this, 'bbp_toggle_topic' ], 10, 3 );
		add_filter( 'bbp_toggle_topic_action_admin', [ $this, 'bbp_toggle_topic_action_admin' ], 10, 3 );
		add_filter( 'bbp_toggle_reply', [ $this, 'bbp_toggle_reply' ], 10, 3 );
		add_filter( 'bbp_toggle_reply_admin', [ $this, 'bbp_toggle_reply_admin' ], 10, 3 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		// No post-launch operations
	}

	/**
	 * "bbp_toggle_forum_action_admin" event.
	 *
	 * @param array $retval.
	 * @param array $forum_id.
	 * @param array $action.
	 * @return  array   The unmodified $retval.
	 * @since    2.4.0
	 */
	public function bbp_toggle_forum_action_admin( $retval, $forum_id, $action ) {
		return $this->bbp_toggle_forum(
			$retval,
			[
				'id'     => $forum_id,
				'action' => $action,
			]
		);
	}

	/**
	 * "bbp_toggle_forum" pseudo event.
	 *
	 * @param array $retval.
	 * @param array $r.
	 * @param array $args.
	 * @return  array   The unmodified $retval.
	 * @since    2.4.0
	 */
	public function bbp_toggle_forum( $retval, $r = [], $args = [] ) {
		if ( is_array( $r ) && array_key_exists( 'action', $r ) ) {
			$id         = 0;
			$title      = '';
			$author     = '';
			$sub_action = '';
			if ( array_key_exists( 'id', $r ) ) {
				$id   = $r['id'];
				$post = get_post( $id );
				if ( $post instanceof \WP_Post ) {
					$title  = $post->post_title;
					$author = $this->get_user( $post->post_author );
				}
			}
			if ( array_key_exists( 'sub_action', $r ) ) {
				$sub_action = (string) $r['sub_action'];
			}
			switch ( $r['action'] ) {
				case 'bbp_toggle_forum_close':
					if ( 0 !== $id ) {
						if ( bbp_is_forum_open( $id ) ) {
							$this->logger->info( sprintf( 'Forum opened: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						} else {
							$this->logger->info( sprintf( 'Forum closed: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						}
					} else {
						$this->logger->warning( 'Trying to open/close an unknown forum.' );
					}
					break;
				case 'bbp_toggle_forum_trash':
					if ( 0 !== $id ) {
						switch ( $sub_action ) {
							case 'trash':
								$this->logger->info( sprintf( 'Forum trashed: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;
							case 'untrash':
								$this->logger->info( sprintf( 'Forum restored from trash: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;

							case 'delete':
								$this->logger->info( sprintf( 'Forum deleted: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;
						}
					} else {
						$this->logger->warning( 'Trying to trash/restore/delete an unknown forum.' );
					}
					break;
			}
		} else {
			$this->logger->warning( 'Unknown toggling action.' );
		}
		return $retval;
	}

	/**
	 * "bbp_toggle_topic_action_admin" event.
	 *
	 * @param array $retval.
	 * @param array $topic_id.
	 * @param array $action.
	 * @return  array   The unmodified $retval.
	 * @since    2.4.0
	 */
	public function bbp_toggle_topic_action_admin( $retval, $topic_id, $action ) {
		return $this->bbp_toggle_topic(
			$retval,
			[
				'id'     => $topic_id,
				'action' => $action,
			]
		);
	}

	/**
	 * "bbp_toggle_topic" event.
	 *
	 * @param array $retval.
	 * @param array $r.
	 * @param array $args.
	 * @return  array   The unmodified $retval.
	 * @since    2.4.0
	 */
	public function bbp_toggle_topic( $retval, $r = [], $args = [] ) {
		if ( is_array( $r ) && array_key_exists( 'action', $r ) ) {
			$id         = 0;
			$title      = '';
			$author     = '';
			$sub_action = '';
			if ( array_key_exists( 'id', $r ) ) {
				$id   = $r['id'];
				$post = get_post( $id );
				if ( $post instanceof \WP_Post ) {
					$title  = $post->post_title;
					$author = $this->get_user( $post->post_author );
				}
			}
			if ( array_key_exists( 'sub_action', $r ) ) {
				$sub_action = (string) $r['sub_action'];
			}
			switch ( $r['action'] ) {
				case 'bbp_toggle_topic_approve':
					if ( 0 !== $id ) {
						if ( bbp_is_topic_pending( $id ) ) {
							$this->logger->notice( sprintf( 'Topic unapproved: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						} else {
							$this->logger->info( sprintf( 'Topic approved: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						}
					} else {
						$this->logger->warning( 'Trying to approve/unapprove an unknown topic.' );
					}
					break;
				case 'bbp_toggle_topic_close':
					if ( 0 !== $id ) {
						if ( bbp_is_topic_open( $id ) ) {
							$this->logger->info( sprintf( 'Topic opened: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						} else {
							$this->logger->info( sprintf( 'Topic closed: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						}
					} else {
						$this->logger->warning( 'Trying to open/close an unknown topic.' );
					}
					break;
				case 'bbp_toggle_topic_spam':
					if ( 0 !== $id ) {
						if ( bbp_is_topic_spam( $id ) ) {
							$this->logger->notice( sprintf( 'Topic marked as spam: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						} else {
							$this->logger->info( sprintf( 'Topic marked as not spam: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						}
					} else {
						$this->logger->warning( 'Trying to mark as spam/not spam an unknown topic.' );
					}
					break;
				case 'bbp_toggle_topic_stick':
					if ( 0 !== $id ) {
						if ( bbp_is_topic_sticky( $id ) ) {
							if ( '1' === filter_input( INPUT_GET, 'super' ) ) {
								$this->logger->info( sprintf( 'Topic super-stuck: "%s" (post ID %s) by %s.', $title, $id, $author ) );
							} else {
								$this->logger->info( sprintf( 'Topic stuck: "%s" (post ID %s) by %s.', $title, $id, $author ) );
							}
						} else {
							$this->logger->info( sprintf( 'Topic unstuck: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						}
					} else {
						$this->logger->warning( 'Trying to make sticky/unsticky an unknown topic.' );
					}
					break;
				case 'bbp_toggle_topic_trash':
					if ( 0 !== $id ) {
						switch ( $sub_action ) {
							case 'trash':
								$this->logger->info( sprintf( 'Topic trashed: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;
							case 'untrash':
								$this->logger->info( sprintf( 'Topic restored from trash: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;

							case 'delete':
								$this->logger->info( sprintf( 'Topic deleted: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;
						}
					} else {
						$this->logger->warning( 'Trying to trash/restore/delete an unknown topic.' );
					}
					break;
			}
		} else {
			$this->logger->warning( 'Unknown toggling action.' );
		}
		return $retval;
	}

	/**
	 * "bbp_toggle_reply_action_admin" event.
	 *
	 * @param array $retval.
	 * @param array $reply_id.
	 * @param array $action.
	 * @return  array   The unmodified $retval.
	 * @since    2.4.0
	 */
	public function bbp_toggle_reply_action_admin( $retval, $reply_id, $action ) {
		return $this->bbp_toggle_reply(
			$retval,
			[
				'id'     => $reply_id,
				'action' => $action,
			]
		);
	}

	/**
	 * "bbp_toggle_reply" event.
	 *
	 * @param array $retval.
	 * @param array $r.
	 * @param array $args.
	 * @return  array   The unmodified $retval.
	 * @since    2.4.0
	 */
	public function bbp_toggle_reply( $retval, $r = [], $args = [] ) {
		if ( is_array( $r ) && array_key_exists( 'action', $r ) ) {
			$id         = 0;
			$title      = '';
			$author     = '';
			$sub_action = '';
			if ( array_key_exists( 'id', $r ) ) {
				$id   = $r['id'];
				$post = get_post( $id );
				if ( $post instanceof \WP_Post ) {
					$title  = $post->post_title;
					$author = $this->get_user( $post->post_author );
				}
			}
			if ( array_key_exists( 'sub_action', $r ) ) {
				$sub_action = (string) $r['sub_action'];
			}
			switch ( $r['action'] ) {
				case 'bbp_toggle_reply_approve':
					if ( 0 !== $id ) {
						if ( bbp_is_reply_pending( $id ) ) {
							$this->logger->notice( sprintf( 'Reply unapproved: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						} else {
							$this->logger->info( sprintf( 'Reply approved: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						}
					} else {
						$this->logger->warning( 'Trying to approve/unapprove an unknown reply.' );
					}
					break;
				case 'bbp_toggle_reply_spam':
					if ( 0 !== $id ) {
						if ( bbp_is_reply_spam( $id ) ) {
							$this->logger->notice( sprintf( 'Reply marked as spam: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						} else {
							$this->logger->info( sprintf( 'Reply marked as not spam: "%s" (post ID %s) by %s.', $title, $id, $author ) );
						}
					} else {
						$this->logger->warning( 'Trying to mark as spam/not spam an unknown reply.' );
					}
					break;
				case 'bbp_toggle_reply_trash':
					if ( 0 !== $id ) {
						switch ( $sub_action ) {
							case 'trash':
								$this->logger->info( sprintf( 'Reply trashed: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;
							case 'untrash':
								$this->logger->info( sprintf( 'Reply restored from trash: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;

							case 'delete':
								$this->logger->info( sprintf( 'Reply deleted: "%s" (post ID %s) by %s.', $title, $id, $author ) );
								break;
						}
					} else {
						$this->logger->warning( 'Trying to trash/restore/delete an unknown reply.' );
					}
					break;
			}
		} else {
			$this->logger->warning( 'Unknown toggling action.' );
		}
		return $retval;
	}

	/**
	 * Finalizes monitoring operations.
	 *
	 * @since    3.0.0
	 */
	public function monitoring_close() {
		if ( ! $this->is_available() ) {
			return;
		}
		if ( ! \Decalog\Plugin\Feature\DMonitor::$active ) {
			return;
		}
		// No monitors to finalize.
	}


}
