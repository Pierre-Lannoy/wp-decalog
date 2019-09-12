<?php

/**
 * WP core listener for DecaLog.
 *
 * Defines class for WP core listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\System\Http;
use Decalog\System\Comment;

/**
 * WP core listener for DecaLog.
 *
 * Defines methods and properties for WP core listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class CoreListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		global $wp_version;
		$this->id      = 'wpcore';
		$this->name    = esc_html__( 'WordPress core', 'decalog' );
		$this->class   = 'core';
		$this->product = 'WordPress';
		$this->version = $wp_version;
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.0.0
	 */
	protected function is_available() {
		return true;
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	protected function launch() {
		add_action( 'wp_loaded', [ $this, 'version_check' ], 10, 0 );
		// Attachments.
		add_action( 'add_attachment', [ $this, 'add_attachment' ], 10, 1 );
		add_action( 'delete_attachment', [ $this, 'delete_attachment' ], 10, 1 );
		add_action( 'edit_attachment', [ $this, 'edit_attachment' ], 10, 1 );
		// Posts and Pages.
		add_action( 'deleted_post', [ $this, 'deleted_post' ], 10, 1 );
		add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
		// Terms.
		add_action( 'edited_terms', [ $this, 'edited_terms' ], 10, 2 );
		add_action( 'created_term', [ $this, 'created_term' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'delete_term' ], 10, 5 );
		// Comments.
		add_action( 'comment_flood_trigger', [ $this, 'comment_flood_trigger' ], 10, 2 );
		add_action( 'comment_duplicate_trigger', [ $this, 'comment_duplicate_trigger' ], 10, 1 );
		add_action( 'comment_post', [ $this, 'comment_post' ], 10, 3 );
		add_action( 'wp_insert_comment', [ $this, 'wp_insert_comment' ], 10, 2 );
		add_action( 'edit_comment', [ $this, 'edit_comment' ], 10, 2 );
		add_action( 'delete_comment', [ $this, 'delete_comment' ], 10, 2 );
		add_action( 'trash_comment', [ $this, 'trash_comment' ], 10, 2 );
		add_action( 'untrash_comment', [ $this, 'untrash_comment' ], 10, 2 );
		add_action( 'spam_comment', [ $this, 'spam_comment' ], 10, 2 );
		add_action( 'unspam_comment', [ $this, 'unspam_comment' ], 10, 2 );
		add_action( 'transition_comment_status', [ $this, 'transition_comment_status' ], 10, 3 );
		// Menus.
		add_action( 'wp_create_nav_menu', [ $this, 'wp_create_nav_menu' ], 10, 2 );
		add_action( 'wp_update_nav_menu', [ $this, 'wp_update_nav_menu' ], 10, 2 );
		add_action( 'wp_delete_nav_menu', [ $this, 'wp_delete_nav_menu' ], 10, 1 );
		add_action( 'wp_add_nav_menu_item', [ $this, 'wp_add_nav_menu_item' ], 10, 3 );
		add_action( 'wp_update_nav_menu_item', [ $this, 'wp_update_nav_menu_item' ], 10, 3 );
		// Mail.
		add_action( 'phpmailer_init', [ $this, 'phpmailer_init' ], 10, 1 );
		add_action( 'wp_mail_failed', [ $this, 'wp_mail_failed' ], 10, 1 );
		// Administrative.
		add_action( 'added_option', [ $this, 'added_option' ], 10, 2 );
		add_action( 'updated_option', [ $this, 'updated_option' ], 10, 3 );
		add_action( 'deleted_option', [ $this, 'deleted_option' ], 10, 1 );
		add_action( 'delete_user', [ $this, 'delete_user' ], 10, 2 );
		add_action( 'user_register', [ $this, 'user_register' ], 10, 1 );
		add_action( 'lostpassword_post', [ $this, 'lostpassword_post' ], 10, 1 );
		add_action( 'password_reset', [ $this, 'password_reset' ], 10, 2 );
		add_action( 'wp_logout', [ $this, 'wp_logout' ], 10, 0 );
		add_action( 'wp_login_failed', [ $this, 'wp_login_failed' ], 10, 1 );
		add_action( 'wp_login', [ $this, 'wp_login' ], 10, 2 );
		// Advanced.
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], PHP_INT_MAX );
		add_action( 'load_textdomain', [ $this, 'load_textdomain' ], 10, 2 );
		add_action( 'wp_loaded', [ $this, 'wp_loaded' ] );
		add_action( 'auth_cookie_malformed', [ $this, 'auth_cookie_malformed' ], 10, 2 );
		add_action( 'auth_cookie_valid', [ $this, 'auth_cookie_valid' ], 10, 2 );
		add_action( 'generate_rewrite_rules', [ $this, 'generate_rewrite_rules' ], 10, 1 );
		// Plugins & Themes.
		add_action( 'upgrader_process_complete', [ $this, 'upgrader_process_complete' ], 10, 2 );
		add_action( 'activated_plugin', [ $this, 'activated_plugin' ], 10, 2 );
		add_action( 'deactivated_plugin', [ $this, 'deactivated_plugin' ], 10, 2 );
		add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], PHP_INT_MAX );
		add_action( 'switch_theme', [ $this, 'switch_theme' ], 10, 3 );
		// Errors.
		add_filter( 'wp_die_ajax_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_xmlrpc_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_json_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_jsonp_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_xml_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp', [ $this, 'wp' ], 10, 1 );
		add_filter( 'http_api_debug', [ $this, 'http_api_debug' ], 10, 5 );
		return true;
	}

	/**
	 * Check versions modifications.
	 *
	 * @since    1.2.0
	 */
	public function version_check() {
		global $wp_version;
		$old_version = Option::network_get( 'wp_version', 'x' );
		if ( 'x' === $old_version ) {
			Option::network_set( 'wp_version', $wp_version );
			return;
		}
		if ( $wp_version === $old_version ) {
			return;
		}
		Option::network_set( 'wp_version', $wp_version );
		if ( version_compare( $wp_version, $old_version, '<' ) ) {
			$this->logger->warning( sprintf( 'WordPress version downgraded from %s to %s.', $old_version, $wp_version ) );
			return;
		}
		$this->logger->notice( sprintf( 'WordPress version upgraded from %s to %s.', $old_version, $wp_version ) );
	}

	/**
	 * "add_attachment" event.
	 *
	 * @since    1.0.0
	 */
	public function add_attachment( $post_ID ) {
		$message = 'Attachment added.';
		if ( $att = wp_get_attachment_metadata( $post_ID ) ) {
			$message = sprintf( 'Attachment added: "%s".', $att['file'] );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * "delete_attachment" event.
	 *
	 * @since    1.0.0
	 */
	public function delete_attachment( $post_ID ) {
		$message = 'Attachment deleted.';
		if ( $att = wp_get_attachment_metadata( $post_ID ) ) {
			$message = sprintf( 'Attachment deleted: "%s".', $att['file'] );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * "edit_attachment" event.
	 *
	 * @since    1.0.0
	 */
	public function edit_attachment( $post_ID ) {
		$message = 'Attachment updated.';
		if ( $att = wp_get_attachment_metadata( $post_ID ) ) {
			$message = sprintf( 'Attachment updated: "%s".', $att['file'] );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * "delete_post" event.
	 *
	 * @since    1.0.0
	 */
	public function deleted_post( $post_ID ) {
		$message = 'Post deleted.';
		if ( $post = get_post( $post_ID ) ) {
			$message = sprintf( 'Post deleted: "%s" by %s.', $post->post_title, $this->get_user( $post->post_author ) );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * "transition_post_status" event.
	 *
	 * @since    1.4.0
	 */
	public function transition_post_status( $new, $old, $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return;
		} elseif ( in_array( $post->post_type, [ 'nav_menu_item', 'attachment', 'revision' ], true ) ) {
			return;
		} elseif ( in_array( $new, [ 'auto-draft', 'inherit' ], true ) ) {
			return;
		} elseif ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		} elseif ( 'draft' === $new && 'publish' === $old ) {
			$action = 'unpublished';
		} elseif ( 'trash' === $old && 'trash' !== $new ) {
			$action = 'restored from trash';
		} elseif ( 'draft' === $new && 'draft' === $old ) {
			$action = 'draft saved';
		} elseif ( 'publish' === $new && 'draft' === $old ) {
			$action = 'published';
		} elseif ( 'draft' === $new ) {
			$action = 'drafted';
		} elseif ( 'pending' === $new ) {
			$action = 'pending review';
		} elseif ( 'future' === $new ) {
			$action = 'scheduled';
		} elseif ( 'future' === $old && 'publish' === $new ) {
			$action = 'published immediately';
		} elseif ( 'private' === $new ) {
			$action = 'privately published';
		} elseif ( 'trash' === $new ) {
			$action = 'trashed';
		} else {
			$action = 'updated';
		}
		if ( 'auto-draft' === $old && 'auto-draft' !== $new ) {
			$action = 'created';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Post %s: "%s" (post ID %s) by %s.', $action, $post->post_title, $post->ID, $this->get_user( $post->post_author ) ) );
		}
	}

	/**
	 * "edited_terms" event.
	 *
	 * @since    1.0.0
	 */
	public function edited_terms( $term_id, $taxonomy ) {
		$message = 'Term updated.';
		if ( $term = get_term( $term_id, $taxonomy ) ) {
			$message = sprintf( 'Term updated: "%s" from "%s".', $term->name, $term->taxonomy );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * "created_term" event.
	 *
	 * @since    1.0.0
	 */
	public function created_term( $term_id, $tt_id, $taxonomy ) {
		$message = 'Term created.';
		if ( $term = get_term( $term_id, $taxonomy ) ) {
			$message = sprintf( 'Term created: "%s" from "%s".', $term->name, $term->taxonomy );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * "delete_term" event.
	 *
	 * @since    1.0.0
	 */
	public function delete_term( $term_id, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		$message = 'Term deleted.';
		if ( ! is_wp_error( $deleted_term ) ) {
			$message = sprintf( 'Term deleted: "%s" from "%s".', $deleted_term->name, $deleted_term->taxonomy );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * "wp_create_nav_menu" event.
	 *
	 * @since    1.4.0
	 */
	public function wp_create_nav_menu( $term_id, $menu_data = null ) {
		if ( isset( $this->logger ) && isset( $menu_data ) ) {
			$this->logger->info( sprintf( 'Menu created: "%s" (menu ID %s).', $menu_data['menu-name'], $term_id ) );
		}
	}

	/**
	 * "wp_update_nav_menu" event.
	 *
	 * @since    1.4.0
	 */
	public function wp_update_nav_menu( $term_id, $menu_data = null ) {
		if ( isset( $this->logger ) && isset( $menu_data ) ) {
			$this->logger->info( sprintf( 'Menu updated: "%s" (menu ID %s).', $menu_data['menu-name'], $term_id ) );
		}
	}

	/**
	 * "wp_delete_nav_menu" event.
	 *
	 * @since    1.4.0
	 */
	public function wp_delete_nav_menu( $menu_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Menu deleted: menu ID %s.', $menu_id ) );
		}
	}

	/**
	 * "wp_add_nav_menu_item" event.
	 *
	 * @since    1.4.0
	 */
	public function wp_add_nav_menu_item( $menu_id, $menu_item_db_id, $args ) {
		if ( isset( $this->logger ) && is_array( $args ) && array_key_exists( 'menu-item-title', $args ) && '' !== $args['menu-item-title'] ) {
			$this->logger->info( sprintf( 'Menu item created: "%s" (menu item ID %s).', $args['menu-item-title'], $menu_item_db_id ) );
		}
	}

	/**
	 * "wp_update_nav_menu_item" event.
	 *
	 * @since    1.4.0
	 */
	public function wp_update_nav_menu_item( $menu_id, $menu_item_db_id, $args ) {
		if ( isset( $this->logger ) && is_array( $args ) && array_key_exists( 'menu-item-title', $args ) && '' !== $args['menu-item-title'] ) {
			$this->logger->info( sprintf( 'Menu item updated: "%s" (menu item ID %s).', $args['menu-item-title'], $menu_item_db_id ) );
		}
	}

	/**
	 * "added_option" event.
	 *
	 * @since    1.0.0
	 */
	public function added_option( $option, $value ) {
		$word = 'Option';
		if ( 0 === strpos( $option, '_transient' ) ) {
			$word = 'Transient';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( '%s added: "%s".', $word, $option ) );
		}
	}

	/**
	 * "updated_option" event.
	 *
	 * @since    1.0.0
	 */
	public function updated_option( $option, $old_value, $value ) {
		$word = 'Option';
		if ( 0 === strpos( $option, '_transient' ) ) {
			$word = 'Transient';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( '%s updated: "%s".', $word, $option ) );
		}
	}

	/**
	 * "deleted_option" event.
	 *
	 * @since    1.0.0
	 */
	public function deleted_option( $option ) {
		$word = 'Option';
		if ( 0 === strpos( $option, '_transient' ) ) {
			$word = 'Transient';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( '%s deleted: "%s".', $word, $option ) );
		}
	}

	/**
	 * "delete_user" event.
	 *
	 * @since    1.0.0
	 */
	public function delete_user( $id, $reassign ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'User deleted: %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "user_register" and "wpmu_new_user" events.
	 *
	 * @since    1.0.0
	 */
	public function user_register( $id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'User created: %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "lostpassword_post" event.
	 *
	 * @since    1.0.0
	 */
	public function lostpassword_post( $errors ) {
		if ( isset( $this->logger ) ) {
			if ( is_wp_error( $errors ) ) {
				$this->logger->info( sprintf( 'Lost password form submitted with error "%s".', wp_kses( $errors->get_error_message(), [] ) ), $errors->get_error_code() );
			} else {
				$this->logger->info( 'Lost password form submitted.' );
			}
		}
	}

	/**
	 * "password_reset" event.
	 *
	 * @since    1.0.0
	 */
	public function password_reset( $user, $new_pass ) {
		if ( $user instanceof \WP_User ) {
			$id = $user->ID;
		} else {
			$id = 0;
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Password reset for %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "wp_logout" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_logout() {
		if ( isset( $this->logger ) ) {
			$this->logger->info( 'User logged-out.' );
		}
	}

	/**
	 * "wp_login_failed" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_login_failed( $username ) {
		$name = $username;
		if ( Option::network_get( 'pseudonymization' ) ) {
			$name = 'somebody';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Failed login for "%s".', $username ) );
		}
	}

	/**
	 * "wp_login" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_login( $user_login, $user ) {
		if ( $user instanceof \WP_User ) {
			$id = $user->ID;
		} else {
			$id = 0;
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'User logged-in: %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "comment_post" event.
	 *
	 * @since    1.0.0
	 */
	public function comment_post( $comment_ID, $comment_approved, $commentdata ) {
		$status = 'unknown status';
		if ( is_string( $comment_approved ) ) {
			$status = $comment_approved;
		} elseif ( is_numeric( $comment_approved ) ) {
			$status = 1 === $comment_approved ? 'approved' : 'not approved';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment %s: %s.', $status, Comment::get_full_comment_name( $comment_ID) ) );
		}
	}

	/**
	 * "wp_insert_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function wp_insert_comment( $id, $comment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'New comment: %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "edit_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function edit_comment( $id, $comment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment updated: %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "delete_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function delete_comment( $id, $comment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment deleted: %s.', Comment::get_full_comment_name( $comment ) ) );
		}
	}

	/**
	 * "trash_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function trash_comment( $id, $comment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment trashed: %s.', Comment::get_full_comment_name( $comment ) ) );
		}
	}

	/**
	 * "untrash_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function untrash_comment( $id, $comment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment restored from trash: %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "spam_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function spam_comment( $id, $comment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment marked as "spam": %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "unspam_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function unspam_comment( $id, $comment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment marked as "not spam": %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "transition_comment_status" event.
	 *
	 * @since    1.4.0
	 */
	public function transition_comment_status( $new_status, $old_status, $comment ) {
		if ( isset( $this->logger ) ) {
			if ( 'approved' === $new_status ) {
				$this->logger->info( sprintf( 'Comment approved: %s.', Comment::get_full_comment_name( $comment ) ) );
			}
			if ( 'unapproved' === $new_status ) {
				$this->logger->info( sprintf( 'Comment unapproved: %s.', Comment::get_full_comment_name( $comment ) ) );
			}
		}
	}

	/**
	 * "comment_flood_trigger" event.
	 *
	 * @since    1.0.0
	 */
	public function comment_flood_trigger( $time_lastcomment, $time_newcomment ) {
		if ( isset( $this->logger ) ) {
			$this->logger->warning( 'Comment flood triggered.' );
		}
	}

	/**
	 * "comment_duplicate_trigger" event.
	 *
	 * @since    1.4.0
	 */
	public function comment_duplicate_trigger( $data ) {
		if ( isset( $this->logger ) ) {
			$this->logger->warning( 'Duplicate comment triggered.' );
		}
	}

	/**
	 * "after_setup_theme" event.
	 *
	 * @since    1.0.0
	 */
	public function after_setup_theme() {
		if ( isset( $this->logger ) ) {
			$this->logger->debug( 'Theme initialized and set-up.' );
		}
	}

	/**
	 * "switch_theme" event.
	 *
	 * @since    1.0.0
	 */
	public function switch_theme( $new_name, $new_theme, $old_theme ) {
		if ( $old_theme instanceof \WP_Theme && $new_theme instanceof \WP_Theme ) {
			$message = sprintf( 'Theme switched from "%s" to "%s".', $old_theme->name, $new_theme->name );
		} else {
			$message = sprintf( 'Theme activated: "%s".', $new_name );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->notice( $message );
		}
	}


	/**
	 * "phpmailer_init" event.
	 *
	 * @since    1.0.0
	 */
	public function phpmailer_init( $phpmailer ) {
		if ( $phpmailer instanceof \PHPMailer ) {
			$phpmailer->SMTPDebug   = 2;
			$self                   = $this;
			$phpmailer->Debugoutput = function ( $message ) use ( $self ) {
				if ( isset( $self->logger ) ) {
					$self->logger->debug( $message );
				}
			};
		}
	}

	/**
	 * "wp_mail_failed" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_mail_failed( $error ) {
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $error ) ) {
			if ( isset( $this->logger ) ) {
				$this->logger->error( $error->get_error_message(), $error->get_error_code() );
			}
		}
	}

	/**
	 * "auth_cookie_malformed" event.
	 *
	 * @since    1.0.0
	 */
	public function auth_cookie_malformed( $cookie, $scheme ) {
		if ( ! $scheme || ! is_string( $scheme ) ) {
			$scheme = '<none>';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( 'Malformed authentication cookie for "%s" scheme.', $scheme ) );
		}
	}

	/**
	 * "auth_cookie_valid" event.
	 *
	 * @since    1.0.0
	 */
	public function auth_cookie_valid( $cookie, $user ) {
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( 'Validated authentication cookie for %s.', $this->get_user( $user->ID ) ) );
		}
	}

	/**
	 * "plugins_loaded" event.
	 *
	 * @since    1.0.0
	 */
	public function plugins_loaded() {
		if ( isset( $this->logger ) ) {
			$this->logger->debug( 'All plugins are loaded.' );
		}
	}

	/**
	 * "load_textdomain" event.
	 *
	 * @since    1.0.0
	 */
	public function load_textdomain( $domain, $mofile ) {
		$mofile = './' . str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $mofile ) );
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( 'Text domain loaded: "%s" from %s.', $domain, $mofile ) );
		}
	}

	/**
	 * "wp_loaded" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_loaded() {
		if ( isset( $this->logger ) ) {
			$this->logger->debug( 'WordPress core, plugins and theme fully loaded and instantiated.' );
		}
	}

	/**
	 * "activated_plugin" event.
	 *
	 * @since    1.0.0
	 */
	public function activated_plugin( $plugin, $network_activation ) {
		$d = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
		if ( is_array( $d ) && array_key_exists( 'Name', $d ) && array_key_exists( 'Version', $d ) ) {
			$component = $d['Name'] . ' (' . $d['Version'] . ')';
		} else {
			$component = 'unknown plugin';
		}
		if ( isset( $this->logger ) ) {
			if ( $network_activation ) {
				$this->logger->notice( sprintf( 'Plugin network activation: %s.', $component ) );
			} else {
				$this->logger->notice( sprintf( 'Plugin activation: %s.', $component ) );
			}
		}
	}

	/**
	 * "deactivated_plugin" event.
	 *
	 * @since    1.0.0
	 */
	public function deactivated_plugin( $plugin, $network_activation ) {
		$d = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
		if ( is_array( $d ) && array_key_exists( 'Name', $d ) && array_key_exists( 'Version', $d ) ) {
			$component = $d['Name'] . ' (' . $d['Version'] . ')';
		} else {
			$component = 'unknown plugin';
		}
		if ( isset( $this->logger ) ) {
			if ( $network_activation ) {
				$this->logger->notice( sprintf( 'Plugin network deactivation: %s.', $component ) );
			} else {
				$this->logger->notice( sprintf( 'Plugin deactivation: %s.', $component ) );
			}
		}
	}

	/**
	 * "generate_rewrite_rules" event.
	 *
	 * @since    1.0.0
	 */
	public function generate_rewrite_rules( $wp_rewrite ) {
		if ( isset( $this->logger ) && is_array( $wp_rewrite ) ) {
			$this->logger->info( sprintf( '%s rewrite rules generated.', count( $wp_rewrite ) ) );
		}
	}

	/**
	 * "upgrader_process_complete" event.
	 *
	 * @since    1.0.0
	 */
	public function upgrader_process_complete( $upgrader, $data ) {
		$type       = ( array_key_exists( 'type', $data ) ? ucfirst( $data['type'] ) : '' );
		$action     = ( array_key_exists( 'action', $data ) ? $data['action'] : '' );
		$components = [];
		switch ( $type ) {
			case 'Plugin':
				if ( 'install' === $action ) {
					$slug = $upgrader->plugin_info();
					if ( $slug ) {
						$d = get_plugin_data( $upgrader->skin->result['local_destination'] . '/' . $slug, false, false );
						if ( is_array( $d ) && array_key_exists( 'Name', $d ) && array_key_exists( 'Version', $d ) ) {
							$components[] = $d['Name'] . ' (' . $d['Version'] . ')';
						} else {
							$components[] = 'unknown plugin';
						}
					} else {
						$components[] = 'unknown theme';
					}
				}
				if ( 'update' === $action ) {
					$elements = [];
					if ( array_key_exists( 'plugins', $data ) ) {
						if ( is_array( $data['plugins'] ) ) {
							if ( 1 !== count( $data['plugins'] ) ) {
								$type = 'Plugins';
							}
							foreach ( $data['plugins'] as $e ) {
								$elements[] = $e;
							}
						} elseif ( is_string( $data['plugins'] ) ) {
							$elements[] = $data['plugins'];
						}
						foreach ( $elements as $e ) {
							$d = get_plugin_data( WP_PLUGIN_DIR . '/' . $e, false, false );
							if ( is_array( $d ) && array_key_exists( 'Name', $d ) && array_key_exists( 'Version', $d ) ) {
								$components[] = $d['Name'] . ' (' . $d['Version'] . ')';
							} else {
								$components[] = 'unknown plugin';
							}
						}
					}
				}
				if ( 0 === count( $components ) ) {
					$components[] = 'unknown package';
				}
				break;
			case 'Theme':
				if ( 'install' === $action ) {
					$slug = $upgrader->theme_info();
					if ( $slug ) {
						wp_clean_themes_cache();
						$theme        = wp_get_theme( $slug );
						$components[] = $theme->name . ' (' . $theme->version . ')';
					} else {
						$components[] = 'unknown theme';
					}
				}
				if ( 'update' === $action ) {
					$elements = [];
					if ( array_key_exists( 'themes', $data ) ) {
						if ( is_array( $data['themes'] ) ) {
							if ( 1 !== count( $data['themes'] ) ) {
								$type = 'Themes';
							}
							foreach ( $data['themes'] as $e ) {
								$elements[] = $e;
							}
						} elseif ( is_string( $data['themes'] ) ) {
							$elements[] = $data['themes'];
						}
						foreach ( $elements as $e ) {
							$theme = wp_get_theme( $e );
							if ( $theme instanceof \WP_Theme ) {
								$theme_data = get_file_data(
									$theme->stylesheet_dir . '/style.css',
									[
										'Version' => 'Version',
									]
								);
								if ( is_array( $theme_data ) && array_key_exists( 'Version', $theme_data ) ) {
									$components[] = $theme->name . ' (' . $theme_data['Version'] . ')';
								} else {
									$components[] = $theme->name;
								}
							} else {
								$components[] = 'unknown theme';
							}
						}
					}
				}
				if ( 0 === count( $components ) ) {
					$components[] = 'unknown package';
				}
				break;
			case 'Translation':
				$elements = [];
				if ( array_key_exists( 'translations', $data ) ) {
					if ( 1 !== count( $data['translations'] ) ) {
						$type = 'Translations';
					}
					foreach ( $data['translations'] as $translation ) {
						switch ( $translation['type'] ) {
							case 'plugin':
								$d = get_plugin_data( WP_PLUGIN_DIR . '/' . $translation['slug'], false, false );
								if ( is_array( $d ) && array_key_exists( 'Name', $d ) ) {
									$components[] = $d['Name'] . ' (' . $translation['language'] . ')';
								} else {
									$components[] = 'unknown plugin' . ' (' . $translation['language'] . ')';
								}
								break;
							case 'theme':
								$theme = wp_get_theme( $translation['slug'] );
								if ( $theme instanceof \WP_Theme ) {
									$components[] = $theme->name . ' (' . $translation['language'] . ')';
								} else {
									$components[] = 'unknown theme' . ' (' . $translation['language'] . ')';
								}
								break;
							default:
								$components[] = 'WordPress' . ' (' . $translation['language'] . ')';
								break;
						}
					}
				}
				if ( 0 === count( $components ) ) {
					$components[] = 'unknown package';
				}
				break;
			case 'Core':
				if ( isset( $this->logger ) ) {
					$this->logger->notice( 'WordPress core upgrade completed.' );
				}
				return;
			default:
				if ( isset( $this->logger ) ) {
					$this->logger->notice( 'Upgrader process completed.' );
				}
				return;
		}
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( '%s %s: %s.', $type, $action, implode( ', ', $components ) ) );
		}
	}

	/**
	 * "wp_die_*" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_die_handler( $handler ) {
		if ( ! $handler || ! is_callable( $handler ) ) {
			return $handler;
		}
		return function ( $message, $title = '', $args = [] ) use ( $handler ) {
			$msg  = '';
			$code = 0;
			if ( is_string( $title ) && '' !== $title ) {
				$title .= ': ';
			}
			if ( is_numeric( $title ) ) {
				$code  = $title;
				$title = '';
			}
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
				$msg  = $title . $message->get_error_message();
				$code = $message->get_error_code();
			} elseif ( is_string( $message ) ) {
				$msg = $title . $message;
			}
			if ( is_numeric( $msg ) ) {
				$this->logger->debug( 'Malformed wp_die call.', $code );
			} elseif ( '' !== $msg ) {
				if ( 0 === strpos( $msg, '[' ) || 0 === strpos( $msg, '{' ) ) {
					$this->logger->debug( wp_kses( $msg, [] ), $code );
				} else {
					$this->logger->critical( wp_kses( $msg, [] ), $code );
				}
			}
			return $handler( $message, $title, $args );
		};
	}

	/**
	 * "wp" event.
	 *
	 * @since    1.0.0
	 */
	public function wp( $wp ) {
		if ( $wp instanceof \WP ) {
			if ( is_404() ) {
				$this->logger->warning( 'Page not found', 404 );
			} elseif ( isset( $wp->query_vars['error'] ) ) {
				$this->logger->error( $wp->query_vars['error'] );
			}
		}
	}

	/**
	 * "http_api_debug" event.
	 *
	 * @since    1.0.0
	 */
	public function http_api_debug( $response, $context, $class, $request, $url ) {
		$error   = false;
		$code    = 200;
		$message = '';
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
			$error   = true;
			$message = ucfirst( $response->get_error_message() ) . ': ';
			$code    = $response->get_error_code();
		} elseif ( isset( $response['response']['code'] ) ) {
			$code  = (int) $response['response']['code'];
			$error = ! in_array( $code, Http::$http_success_codes, true );
			if ( isset( $response['message'] ) && is_string( $response['message'] ) ) {
				$message = ucfirst( $response['message'] ) . ': ';
			} elseif ( $error ) {
				if ( array_key_exists( $code, Http::$http_status_codes ) ) {
					$message = Http::$http_status_codes[ $code ] . ': ';
				} else {
					$message = 'Unknown error: ';
				}
			}
		} elseif ( array_key_exists( 'blocking', $request ) && ! $request['blocking'] ) {
			$error = false;
			if ( isset( $response['message'] ) && is_string( $response['message'] ) ) {
				$message = ucfirst( $response['message'] ) . ': ';
			}
		} elseif ( ! is_numeric( $response['response']['code'] ) ) {
			$error = false;
			if ( isset( $response['message'] ) && is_string( $response['message'] ) ) {
				$message = ucfirst( $response['message'] ) . ': ';
			}
		}
		if ( is_array( $request ) ) {
			$verb     = array_key_exists( 'method', $request ) ? $request['method'] : '';
			$message .= $verb . ' ' . $url;
		}
		if ( $error ) {
			$this->logger->error( $message, $code );
		} else {
			$this->logger->debug( $message, $code );
		}
	}

}
