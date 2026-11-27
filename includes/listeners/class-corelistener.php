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

use Decalog\Plugin\Feature\DMonitor;
use Decalog\System\Cache;
use Decalog\System\Environment;
use Decalog\System\Hash;
use Decalog\System\Option;
use Decalog\System\Http;
use Decalog\System\Comment;
use Decalog\System\Post;
use Decalog\System\Database;

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
	 * Post types.
	 *
	 * @since  3.0.0
	 * @var    array    $post_types    Post types.
	 */
	private $post_types = [];

	/**
	 * Comment types.
	 *
	 * @since  3.0.0
	 * @var    array    $comment_types    Comment types.
	 */
	private $comment_types = [];

	/**
	 * Post status.
	 *
	 * @since  3.0.0
	 * @var    array    $post_status    Post status.
	 */
	private $post_status = [];

	/**
	 * Comment status.
	 *
	 * @since  3.0.0
	 * @var    array    $comment_status    Comment status.
	 */
	private $comment_status = [];

	/**
	 * Hooks trace ID.
	 *
	 * @since  3.0.0
	 * @var    array    $hooks    Hooks trace ID.
	 */
	private $hooks = [];

	/**
	 * Don't log activity for these posts.
	 *
	 * @since  3.1.0
	 * @var    array    $posts_nolog    List of posts ID or titles that must not be logged.
	 */
	private $posts_nolog = [];

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		global $wp_version;
		$this->id      = 'wpcore';
		$this->name    = decalog_esc_html__( 'WordPress core', 'decalog' );
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
		add_action( 'wp_loaded', [ $this, 'environment_check' ], 10, 0 );
		// Attachments.
		add_action( 'add_attachment', [ $this, 'add_attachment' ], 10, 1 );
		add_action( 'delete_attachment', [ $this, 'delete_attachment' ], 10, 1 );
		add_action( 'edit_attachment', [ $this, 'edit_attachment' ], 10, 1 );
		// Posts and Pages.
		add_action( 'deleted_post', [ $this, 'deleted_post' ], 10, 1 );
		add_action( 'post_stuck', [ $this, 'post_stuck' ], 10, 1 );
		add_action( 'post_unstuck', [ $this, 'post_unstuck' ], 10, 1 );
		add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
		// Terms.
		add_action( 'edited_terms', [ $this, 'edited_terms' ], 10, 2 );
		add_action( 'created_term', [ $this, 'created_term' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'delete_term' ], 10, 5 );
		// Comments.
		add_action( 'comment_flood_trigger', [ $this, 'comment_flood_trigger' ], 10, 2 );
		add_action( 'comment_duplicate_trigger', [ $this, 'comment_duplicate_trigger' ], 10, 1 );
		add_action( 'wp_insert_comment', [ $this, 'wp_insert_comment' ], 10, 2 );
		add_action( 'edit_comment', [ $this, 'edit_comment' ], 10, 2 );
		add_action( 'delete_comment', [ $this, 'delete_comment' ], 10, 2 );
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
		// Options.
		add_action( 'added_option', [ $this, 'added_option' ], 10, 2 );
		add_action( 'updated_option', [ $this, 'updated_option' ], 10, 3 );
		add_action( 'deleted_option', [ $this, 'deleted_option' ], 10, 1 );
		add_action( 'add_site_option', [ $this, 'add_site_option' ], 10, 3 );
		add_action( 'update_site_option', [ $this, 'update_site_option' ], 10, 3 );
		add_action( 'delete_site_option', [ $this, 'delete_site_option' ], 10, 2 );
		// Users.
		add_action( 'delete_user', [ $this, 'delete_user' ], 10, 2 );
		add_action( 'user_register', [ $this, 'user_register' ], 10, 1 );
		add_action( 'profile_update', [ $this, 'profile_update' ], 10, 2 );
		add_action( 'add_user_role', [ $this, 'add_user_role' ], 10, 2 );
		add_action( 'remove_user_role', [ $this, 'remove_user_role' ], 10, 2 );
		add_action( 'set_user_role', [ $this, 'set_user_role' ], 10, 3 );
		add_action( 'lostpassword_post', [ $this, 'lostpassword_post' ], 10, 1 );
		add_action( 'password_reset', [ $this, 'password_reset' ], 10, 2 );
		add_action( 'wp_logout', [ $this, 'wp_logout' ], 10, 0 );
		add_action( 'wp_login_failed', [ $this, 'wp_login_failed' ], 10, 1 );
		add_action( 'wp_login', [ $this, 'wp_login' ], 10, 2 );
		// Advanced.
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], PHP_INT_MIN );
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
		add_filter( 'wp_die_ajax_handler', [ $this, 'wp_die_ajax_handler' ], 10, 1 );
		add_filter( 'wp_die_xmlrpc_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_json_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_jsonp_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_xml_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp', [ $this, 'wp' ], 10, 1 );
		// Rest API
		add_filter( 'http_api_debug', [ $this, 'http_api_debug' ], 10, 5 );
		// Standard Cron
		if ( ! function_exists( 'HM\Cavalcade\Plugin\Bootstrap' ) ) {
			add_filter( 'schedule_event', [ $this, 'schedule_event' ], PHP_INT_MAX, 1 );
			add_filter( 'pre_clear_scheduled_hook', [ $this, 'pre_clear_scheduled_hook' ], PHP_INT_MAX, 2 );
			add_filter( 'pre_unschedule_hook', [ $this, 'pre_unschedule_hook' ], PHP_INT_MAX, 2 );
		}
		// Applications Passwords
		add_action( 'wp_create_application_password', [ $this, 'wp_create_application_password' ], 10, 4 );
		add_action( 'wp_update_application_password', [ $this, 'wp_update_application_password' ], 10, 3 );
		add_action( 'wp_delete_application_password', [ $this, 'wp_delete_application_password' ], 10, 2 );
		add_action( 'application_password_failed_authentication', [ $this, 'application_password_failed_authentication' ], 10, 1 );
		add_action( 'application_password_did_authenticate', [ $this, 'application_password_did_authenticate' ], 10, 2 );
		// WP (for monitors)
		add_action( 'wp_loaded', [ $this, 'ready' ] );
		// Tracing
		add_action( 'wp_loaded', [ $this, 'trace_loaded_end' ], PHP_INT_MIN, 0 );
		add_action( 'wp', [ $this, 'trace_wp_object_ready' ], PHP_INT_MIN, 0 );
		add_action( 'shutdown', [ $this, 'trace_shutdown_start' ], PHP_INT_MIN, 0 );
		add_action( 'setup_theme', [ $this, 'trace_setup_theme_start' ], PHP_INT_MIN, 0 );
		add_action( 'after_setup_theme', [ $this, 'trace_setup_theme_end' ], PHP_INT_MAX, 0 );
		add_action( 'init', [ $this, 'trace_init_start' ], PHP_INT_MIN, 0 );
		add_action( 'init', [ $this, 'trace_init_end' ], PHP_INT_MAX, 0 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		if ( 1 !== Environment::exec_mode() ) {
			$this->monitor->create_prod_gauge( 'page_latency', 0, 'Execution time for full page rendering - [second]' );
			$this->monitor->create_prod_gauge( 'wp_latency', 0, 'Execution time for WordPress page rendering - [second]' );
			$this->monitor->create_prod_gauge( 'init_latency', 0, 'Execution time for initialization sequence - [second]' );
			$this->monitor->create_prod_gauge( 'shutdown_latency', 0, 'Execution time for shutdown sequence - [second]' );
		}
		$this->monitor->create_prod_counter( 'plugin_active', 'Number of active plugins - [count]' );
		$this->monitor->create_prod_counter( 'plugin_inactive', 'Number of inactive plugins - [count]' );
		$this->monitor->create_prod_counter( 'plugin_updatable', 'Number of plugins needing update - [count]' );
		$this->monitor->create_prod_counter( 'theme_installed', 'Number of installed themes - [count]' );
		$this->monitor->create_prod_counter( 'theme_updatable', 'Number of themes needing update - [count]' );
		$this->monitor->create_prod_counter( 'muplugin_active', 'Number of active must-use plugins - [count]' );
		$this->monitor->create_prod_counter( 'dropin_active', 'Number of active drop-ins - [count]' );
		$this->monitor->create_prod_counter( 'user_active', 'Number of active users - [count]' );
		$this->monitor->create_prod_counter( 'user_inactive', 'Number of inactive users - [count]' );
		$this->monitor->create_prod_counter( 'user_ham', 'Number of ham users - [count]' );
		$this->monitor->create_prod_counter( 'user_spam', 'Number of spam users - [count]' );
		$this->monitor->create_prod_counter( 'user_trash', 'Number of trashed users - [count]' );
	}

	/**
	 * Performs operations after wp is ready if needed.
	 *
	 * @since    3.0.0
	 */
	public function ready() {
		$this->post_types     = Post::get_types();
		$this->comment_types  = Comment::get_types();
		$this->post_status    = Post::get_status();
		$this->comment_status = Comment::get_status();
		foreach ( $this->post_status as $status => $label ) {
			$this->monitor->create_prod_counter( 'content_status_' . $status, 'Number of ' . strtolower( $label ) . ' contents - [count]' );
		}
		foreach ( $this->post_types as $type => $label ) {
			$this->monitor->create_prod_counter( 'content_type_' . $type, 'Number of ' . strtolower( str_replace( '_', ' ', $label ) ) . ' - [count]' );
		}
		foreach ( $this->comment_status as $status => $label ) {
			$this->monitor->create_prod_counter( 'comment_status_' . $status, 'Number of ' . strtolower( $label ) . ' comments - [count]' );
		}
		foreach ( $this->comment_types as $type => $label ) {
			$this->monitor->create_prod_counter( 'comment_type_' . $type, 'Number of ' . strtolower( str_replace( '_', ' ', $label ) ) . ' - [count]' );
		}
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
	 * Check environment modifications.
	 *
	 * @since    1.14.0
	 */
	public function environment_check() {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$wp_env  = wp_get_environment_type();
			$old_env = Option::network_get( 'wp_env', 'x' );
			if ( 'x' === $old_env ) {
				Option::network_set( 'wp_env', $wp_env );
				return;
			}
			if ( $wp_env === $old_env ) {
				return;
			}
			Option::network_set( 'wp_env', $wp_env );
			$this->logger->warning( sprintf( 'WordPress environment type switched from "%s" to "%s".', $old_env, $wp_env ) );
		}
	}

	/**
	 * "add_attachment" event.
	 *
	 * @since    1.0.0
	 */
	public function add_attachment( $post_ID ) {
		$message = 'Attachment added.';
		if ( $att = wp_get_attachment_metadata( $post_ID ) ) {
			$message = sprintf( 'Attachment added: "%s".', $att['file']  ?? 'unknown' );
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
			$message = sprintf( 'Attachment deleted: "%s".', $att['file'] ?? 'unknown' );
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
			$message = sprintf( 'Attachment updated: "%s".', $att['file']  ?? 'unknown' );
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( $message );
		}
	}

	/**
	 * Collects no log posts.
	 *
	 * @since    3.1.0
	 */
	public function get_posts_nolog() {
		if ( 0 < count( $this->posts_nolog ) ) {
			return $this->posts_nolog;
		}
		/**
		 * Filters the posts ids or titles which must not be logged.
		 *
		 * @since 3.1.0
		 *
		 * @param array $posts The posts ids or titles.
		 */
		$this->posts_nolog = apply_filters( 'decalog_no_log_post_activity', [] );
		return $this->posts_nolog;
	}

	/**
	 * "delete_post" event.
	 *
	 * @since    1.0.0
	 */
	public function deleted_post( $post_ID ) {
		if ( isset( $this->logger ) ) {
			$post = get_post( $post_ID );
			if ( $post instanceof \WP_Post ) {
				if ( ! in_array( $post->post_title, $this->get_posts_nolog(), true ) && ! in_array( $post_ID, $this->get_posts_nolog(), true ) ) {
					$this->logger->info( sprintf( 'Post deleted: "%s" (post ID %s) by %s.', $post->post_title, $post_ID, $this->get_user( $post->post_author ) ) );
				}
			} else {
				$this->logger->warning( 'Trying to delete an unknown post.' );
			}
		}
	}

	/**
	 * "post_stuck" event.
	 *
	 * @since    2.4.0
	 */
	public function post_stuck( $post_ID = 0 ) {
		if ( isset( $this->logger ) ) {
			$post = get_post( $post_ID );
			if ( $post instanceof \WP_Post ) {
				if ( ! in_array( $post->post_title, $this->get_posts_nolog(), true ) && ! in_array( $post_ID, $this->get_posts_nolog(), true ) ) {
					$this->logger->info( sprintf( 'Post stuck: "%s" (post ID %s) by %s.', $post->post_title, $post_ID, $this->get_user( $post->post_author ) ) );
				}
			} else {
				$this->logger->warning( 'Trying to make sticky an unknown post.' );
			}
		}
	}

	/**
	 * "post_unstuck" event.
	 *
	 * @since    2.4.0
	 */
	public function post_unstuck( $post_ID = 0 ) {
		if ( isset( $this->logger ) ) {
			$post = get_post( $post_ID );
			if ( $post instanceof \WP_Post ) {
				if ( ! in_array( $post->post_title, $this->get_posts_nolog(), true ) && ! in_array( $post_ID, $this->get_posts_nolog(), true ) ) {
					$this->logger->info( sprintf( 'Post unstuck: "%s" (post ID %s) by %s.', $post->post_title, $post_ID, $this->get_user( $post->post_author ) ) );
				}
			} else {
				$this->logger->warning( 'Trying to make unsticky an unknown post.' );
			}
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
		} elseif ( in_array( $post->post_title, $this->get_posts_nolog(), true ) || in_array( $post->ID, $this->get_posts_nolog(), true ) ) {
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
			$this->logger->debug( sprintf( 'Site %s added: "%s".', $word, $option ) );
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
			$this->logger->debug( sprintf( 'Site %s updated: "%s".', $word, $option ) );
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
			$this->logger->debug( sprintf( 'Site %s deleted: "%s".', $word, $option ) );
		}
	}

	/**
	 * "addsite__option" event.
	 *
	 * @since    1.0.0
	 */
	public function add_site_option( $option, $value, $network_id = null ) {
		$word = 'Option';
		if ( 0 === strpos( $option, '_transient' ) ) {
			$word = 'Transient';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( 'Network %s added: "%s".', $word, $option ) );
		}
	}

	/**
	 * "update_site_option" event.
	 *
	 * @since    1.0.0
	 */
	public function update_site_option( $option, $old_value, $value, $network_id = null ) {
		$word = 'Option';
		if ( 0 === strpos( $option, '_transient' ) ) {
			$word = 'Transient';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( 'Network %s updated: "%s".', $word, $option ) );
		}
	}

	/**
	 * "delete_site_option" event.
	 *
	 * @since    1.0.0
	 */
	public function delete_site_option( $option, $network_id = null ) {
		$word = 'Option';
		if ( 0 === strpos( $option, '_transient' ) ) {
			$word = 'Transient';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->debug( sprintf( 'Network %s deleted: "%s".', $word, $option ) );
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
	 * "uprofile_update" event.
	 *
	 * @since    1.4.0
	 */
	public function profile_update( $user_id, $old_user_data = null ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'User updated: %s.', $this->get_user( $user_id ) ) );
		}
	}

	/**
	 * "add_user_role" event.
	 *
	 * @since    3.5.0
	 */
	public function add_user_role( $user_id, $role ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Role "%s" added for %s.', $role, $this->get_user( $user_id ) ) );
		}
	}

	/**
	 * "remove_user_role" event.
	 *
	 * @since    3.5.0
	 */
	public function remove_user_role( $user_id, $role ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Role "%s" removed for %s.', $role, $this->get_user( $user_id ) ) );
		}
	}

	/**
	 * "set_user_role" event.
	 *
	 * @since    1.4.0
	 */
	public function set_user_role( $user_id, $role, $old_roles = [] ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'New role for %s: %s.', $this->get_user( $user_id ), $role ) );
		}
	}

	/**
	 * "lostpassword_post" event.
	 *
	 * @since    1.0.0
	 */
	public function lostpassword_post( $errors ) {
		if ( isset( $this->logger ) ) {
			if ( is_wp_error( $errors ) && '' !== (string) $errors->get_error_message() ) {
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
			$this->logger->notice( sprintf( 'Failed login for "%s".', $name ) );
		}
	}

	/**
	 * "wp_login" event.
	 *
	 * @since    1.0.0
	 */
	public function wp_login( $user_login, $user = null ) {
		if ( ! $user ) {
			$user = get_user_by( 'login', $user_login );
		}
		if ( $user instanceof \WP_User ) {
			$id = $user->ID;
		} else {
			$id = 0;
		}
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'User logged-in: %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "wp_insert_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function wp_insert_comment( $id, $comment = null ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment created: %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "edit_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function edit_comment( $id, $comment = null ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment updated: %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "delete_comment" event.
	 *
	 * @since    1.4.0
	 */
	public function delete_comment( $id, $comment = null ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment deleted: %s.', Comment::get_full_comment_name( $id ) ) );
		}
	}

	/**
	 * "transition_comment_status" event.
	 *
	 * @since    1.4.0
	 */
	public function transition_comment_status( $new, $old, $comment ) {
		if ( ! $comment instanceof \WP_Comment ) {
			return;
		} elseif ( 'approved' === $new && 'spam' !== $old ) {
			$action = 'approved';
		} elseif ( 'approved' === $new && 'spam' === $old ) {
			$action = 'approved but marked as "spam"';
		} elseif ( 'unapproved' === $new && 'spam' !== $old ) {
			$action = 'unapproved';
		} elseif ( 'unapproved' === $new && 'spam' === $old ) {
			$action = 'unapproved and marked as "spam"';
		} elseif ( 'spam' === $new ) {
			$action = 'marked as "spam"';
		} elseif ( 'unspam' === $new ) {
			$action = 'marked as "not spam"';
		} elseif ( 'trash' === $old && 'trash' !== $new ) {
			$action = 'restored from trash';
		} elseif ( 'pending' === $new ) {
			$action = 'pending review';
		} elseif ( 'trash' === $new ) {
			$action = 'trashed';
		} else {
			$action = 'updated';
		}
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Comment %s: %s.', $action, Comment::get_full_comment_name( $comment ) ) );
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
								$file = '';
								foreach (
									scandir( WP_PLUGIN_DIR . '/' . $translation['slug'] . '/' ) as $item ) {
									if ( strtolower( $translation['slug'] . '.php' ) === strtolower( $item ) ) {
										$file = $item;
									}
								}
								$d = get_plugin_data( WP_PLUGIN_DIR . '/' . $translation['slug'] . '/' . $file, false, false );
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
	 * "wp_die_*" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_die_ajax_handler( $handler ) {
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
				$this->logger->debug( wp_kses( $msg, [] ), $code );
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
		$log_enabled = ! ( array_key_exists( 'headers', $request ) && array_key_exists( 'Decalog-No-Log', $request['headers'] ) );
		$error       = false;
		$code        = 200;
		$message     = '';
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
			$error   = true;
			$message = ucfirst( $response->get_error_message() ) . ': ';
			$code    = $response->get_error_code();
		} elseif ( array_key_exists( 'blocking', $request ) && ! $request['blocking'] ) {
			$error = false;
			if ( isset( $response['message'] ) && is_string( $response['message'] ) ) {
				$message = ucfirst( $response['message'] ) . ': ';
			}
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
		if ( $error && $log_enabled ) {
			if ( $code >= 500 ) {
				$this->logger->error( $message, $code );
			} elseif ( $code >= 400 ) {
				$this->logger->warning( $message, $code );
			} elseif ( $code >= 200 ) {
				$this->logger->notice( $message, $code );
			} elseif ( $code >= 100 ) {
				$this->logger->info( $message, $code );
			} else {
				$this->logger->error( $message, $code );
			}
		} else {
			$this->logger->debug( $message, $code );
		}
	}

	/**
	 * "schedule_event" event.
	 *
	 * @since    3.2.0
	 */
	public function schedule_event( $event = null ) {
		if ( $event && is_object( $event ) && property_exists( $event, 'hook' ) && property_exists( $event, 'schedule' ) && property_exists( $event, 'timestamp' ) ) {
			if ( $event->schedule ) {
				$this->logger->debug( sprintf( 'The recurring event "%s" has been scheduled to "%s".', $event->hook, $event->schedule ) );
			} else {
				$this->logger->debug( sprintf( 'The single event "%s" has been (re)scheduled and will be executed %s.', $event->hook, ( 0 === $event->timestamp - time() ? 'immediately' : sprintf( 'in %d seconds', $event->timestamp - time() ) ) ) );
			}
		} else {
			$this->logger->notice( 'A plugin prevented an event to be scheduled or rescheduled.' );
		}
		return $event;
	}

	/**
	 * "pre_clear_scheduled_hook" event.
	 *
	 * @since    3.2.0
	 */
	public function pre_clear_scheduled_hook( $pre, $hook, $args = null, $wp_error = null ) {
		if ( is_null( $pre ) ) {
			$this->logger->debug( sprintf( 'The "%s" event will be cleared.', $hook ) );
		} else {
			$this->logger->info( sprintf( 'A plugin prevented the "%s" event to be cleared.', $hook ) );
		}
		return $pre;
	}

	/**
	 * "pre_unschedule_hook" event.
	 *
	 * @since    3.2.0
	 */
	public function pre_unschedule_hook( $pre, $hook, $wp_error = null ) {
		if ( is_null( $pre ) ) {
			$this->logger->info( sprintf( 'The "%s" event will be unscheduled.', $hook ) );
		} else {
			$this->logger->notice( sprintf( 'A plugin prevented the "%s" event to be unscheduled.', $hook ) );
		}
		return $pre;
	}

	/**
	 * "wp_create_application_password" event.
	 *
	 * @since    2.3.0
	 */
	public function wp_create_application_password( $user_id, $new_item, $new_password = '', $args = [] ) {
		$this->logger->notice( sprintf( 'Application password "%s" created for %s.', $new_item['name'], $this->get_user( $user_id ) ) );
	}

	/**
	 * "wp_update_application_password" event.
	 *
	 * @since    2.3.0
	 */
	public function wp_update_application_password( $user_id, $item, $update = [] ) {
		$this->logger->debug( sprintf( 'Application password "%s" updated for %s.', $item['name'], $this->get_user( $user_id ) ) );
	}

	/**
	 * "wp_delete_application_password" event.
	 *
	 * @since    2.3.0
	 */
	public function wp_delete_application_password( $user_id, $item ) {
		$this->logger->notice( sprintf( 'Application password "%s" revoked for %s.', $item['name'], $this->get_user( $user_id ) ) );
	}

	/**
	 * "application_password_failed_authentication" event.
	 *
	 * @since    2.3.0
	 */
	public function application_password_failed_authentication( $error ) {
		$this->logger->warning( sprintf( 'Application password authentication failure: "%s".', $error->get_error_message() ), 401 );
	}

	/**
	 * "application_password_did_authenticate" event.
	 *
	 * @since    2.3.0
	 */
	public function application_password_did_authenticate( $user, $item ) {
		$this->logger->debug( sprintf( 'Application password authentication success for %s.', $this->get_user( $user ) ) );
	}

	/**
	 * Monitors execution times.
	 *
	 * @since    3.0.0
	 */
	public function time_close() {
		if ( ! defined( 'POWP_END_TIMESTAMP' ) ) {
			define( 'POWP_END_TIMESTAMP', microtime( true ) );
		}
		if ( defined( 'POWS_START_TIMESTAMP' ) ) {
			$this->monitor->set_prod_gauge( 'page_latency', round( POWP_END_TIMESTAMP - POWS_START_TIMESTAMP, 6 ) );
		}
		if ( defined( 'POWP_START_TIMESTAMP' ) ) {
			$this->monitor->set_prod_gauge( 'wp_latency', round( POWP_END_TIMESTAMP - POWP_START_TIMESTAMP, 6 ) );
		}
		if ( defined( 'POWS_START_TIMESTAMP' ) && defined( 'POWP_START_TIMESTAMP' ) ) {
			$this->monitor->set_prod_gauge( 'init_latency', round( POWP_START_TIMESTAMP - POWS_START_TIMESTAMP, 6 ) );
		}
		$this->monitor->set_prod_gauge( 'shutdown_latency', round( microtime( true ) - POWP_END_TIMESTAMP, 6 ) );
	}

	/**
	 * Monitors plugins.
	 *
	 * @since    3.6.0
	 */
	public function theme_close() {
		$updates = get_site_transient( 'update_themes' );
		if ( ! is_object( $updates ) && function_exists( 'wp_update_themes' ) ) {
			wp_update_themes();
			$updates = get_site_transient( 'update_themes' );
		}
		if ( is_object( $updates ) && property_exists( $updates, 'response' ) ) {
			if ( is_array( $updates->response ) ) {
				$this->monitor->inc_prod_counter( 'theme_updatable', count( $updates->response ) );
			}
		}
		if ( ! Environment::is_wordpress_multisite() && function_exists( 'wp_get_themes' ) ) {
			$installed = wp_get_themes();
			if ( is_array( $installed ) ) {
				$this->monitor->inc_prod_counter( 'theme_installed', count( $installed ) );
			}
		}
	}

	/**
	 * Monitors plugins.
	 *
	 * @since    3.0.0
	 */
	public function plugin_close() {
		$actives = get_option( 'active_plugins' );
		$updates = get_site_transient( 'update_plugins' );
		if ( ! is_object( $updates ) && function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
			$updates = get_site_transient( 'update_plugins' );
		}
		if ( is_object( $updates ) && property_exists( $updates, 'response' ) ) {
			if ( is_array( $updates->response ) ) {
				$this->monitor->inc_prod_counter( 'plugin_updatable', count( $updates->response ) );
			}
		}
		$apl = 0;
		if ( function_exists( 'get_plugins' ) ) {
			$plugins = get_plugins();
			foreach ( $actives as $a ) {
				if ( isset( $plugins[ $a ] ) ) {
					$apl++;
				}
			}
			$this->monitor->inc_prod_counter( 'plugin_active', $apl );
			$this->monitor->inc_prod_counter( 'plugin_inactive', count( $plugins ) - $apl );
		}
		if ( function_exists( 'get_mu_plugins' ) ) {
			$this->monitor->inc_prod_counter( 'muplugin_active', count( get_mu_plugins() ) );
		}
		if ( function_exists( 'get_dropins' ) ) {
			$this->monitor->inc_prod_counter( 'dropin_active', count( get_dropins() ) );
		}
	}

	/**
	 * Monitors users.
	 *
	 * @since    3.0.0
	 */
	public function user_close() {
		global $wpdb;
		$db = new Database();
		// Active users
		$args     = [
			'user_status' => 0,
		];
		$cache_id = Cache::id( $args, 'm-query/' );
		$users    = Cache::get( $cache_id );
		if ( ! isset( $users ) ) {
			$users = $db->count_filtered_lines( $wpdb->users, $args );
			Cache::set( $cache_id, $users, 'm-query' );
		}
		$active = $users;
		// Inactive users
		$args     = [
			'user_status' => 2,
		];
		$cache_id = Cache::id( $args, 'm-query/' );
		$users    = Cache::get( $cache_id );
		if ( ! isset( $users ) ) {
			$users = $db->count_filtered_lines( $wpdb->users, $args );
			Cache::set( $cache_id, $users, 'm-query' );
		}
		$inactive = $users;
		// Spam users
		$args     = [
			'user_status' => 1,
		];
		$cache_id = Cache::id( $args, 'm-query/' );
		$users    = Cache::get( $cache_id );
		if ( ! isset( $users ) ) {
			$users = $db->count_filtered_lines( $wpdb->users, $args );
			Cache::set( $cache_id, $users, 'm-query' );
		}
		$spam = $users;
		if ( Environment::is_wordpress_multisite() ) {
			// Deleted users
			$args     = [
				'deleted' => 1,
			];
			$cache_id = Cache::id( $args, 'm-query/' );
			$users    = Cache::get( $cache_id );
			if ( ! isset( $users ) ) {
				$users = $db->count_filtered_lines( $wpdb->users, $args );
				Cache::set( $cache_id, $users, 'm-query' );
			}
			$trash = $users;
			// Spam users
			$args     = [
				'spam' => 1,
			];
			$cache_id = Cache::id( $args, 'm-query/' );
			$users    = Cache::get( $cache_id );
			if ( ! isset( $users ) ) {
				$users = $db->count_filtered_lines( $wpdb->users, $args );
				Cache::set( $cache_id, $users, 'm-query' );
			}
			$spam += $users;
		} else {
			$trash = 0;
		}
		$this->monitor->inc_prod_counter( 'user_active', $active );
		$this->monitor->inc_prod_counter( 'user_inactive', $inactive );
		$this->monitor->inc_prod_counter( 'user_ham', $active + $inactive - $spam );
		$this->monitor->inc_prod_counter( 'user_spam', $spam );
		$this->monitor->inc_prod_counter( 'user_trash', $trash );
	}

	/**
	 * Monitors posts.
	 *
	 * @since    3.0.0
	 */
	public function post_close() {
		global $wpdb;
		$db = new Database();
		foreach ( $this->post_status as $status => $label ) {
			$args     = [
				'post_status' => $status,
			];
			$cache_id = Cache::id( $args, 'm-query/' );
			$posts    = Cache::get( $cache_id, true );
			if ( ! isset( $posts ) ) {
				$posts = $db->count_filtered_lines( $wpdb->posts, $args );
				Cache::set( $cache_id, $posts, 'm-query', true );
			}
			$this->monitor->inc_prod_counter( 'content_status_' . $status, $posts );
		}
		foreach ( $this->post_types as $type => $label ) {
			if ( 'post' === $type ) {
				$ptype = [ 'post', '' ];
			} else {
				$ptype = $type;
			}
			$args     = [
				'post_type' => $ptype,
			];
			$cache_id = Cache::id( $args, 'm-query/' );
			$posts    = Cache::get( $cache_id, true );
			if ( ! isset( $posts ) ) {
				$posts = $db->count_filtered_lines( $wpdb->posts, $args );
				Cache::set( $cache_id, $posts, 'm-query', true );
			}
			$this->monitor->inc_prod_counter( 'content_type_' . $type, $posts );
		}
	}

	/**
	 * Comments posts.
	 *
	 * @since    3.0.0
	 */
	public function comment_close() {
		global $wpdb;
		$db = new Database();
		foreach ( $this->comment_status as $status => $label ) {
			if ( 'hold' === $status ) {
				$value = 0;
			} elseif ( 'approve' === $status ) {
				$value = 1;
			} else {
				$value = $status;
			}
			$args     = [
				'comment_approved' => $value,
			];
			$cache_id = Cache::id( $args, 'm-query/' );
			$comments = Cache::get( $cache_id, true );
			if ( ! isset( $comments ) ) {
				$comments = $db->count_filtered_lines( $wpdb->comments, $args );
				Cache::set( $cache_id, $comments, 'm-query', true );
			}
			$this->monitor->inc_prod_counter( 'comment_status_' . $status, $comments );
		}
		foreach ( $this->comment_types as $type => $label ) {
			if ( 'comment' === $type ) {
				$ctype = [ 'comment', '' ];
			} else {
				$ctype = $type;
			}
			$args     = [
				'comment_type' => $ctype,
			];
			$cache_id = Cache::id( $args, 'm-query/' );
			$comments = Cache::get( $cache_id, true );
			if ( ! isset( $comments ) ) {
				$comments = $db->count_filtered_lines( $wpdb->comments, $args );
				Cache::set( $cache_id, $comments, 'm-query', true );
			}
			$this->monitor->inc_prod_counter( 'comment_type_' . $type, $comments );
		}
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
		$span = $this->tracer->start_span( 'Metrics collation', DECALOG_SPAN_SHUTDOWN );
		if ( 1 !== Environment::exec_mode() ) {
			$this->time_close();
		}
		$this->user_close();
		$this->post_close();
		$this->comment_close();
		$this->plugin_close();
		$this->theme_close();
		$this->tracer->end_span( $span );
	}

	/**
	 * Trace loaded hooks.
	 *
	 * @since    3.0.0
	 */
	public function trace_loaded_end() {
		$this->tracer->end_span( 'WPFL' );
		$this->hooks['run'] = $this->tracer->start_span_with_id( 'Run', DECALOG_SPAN_MAIN_RUN );
		if ( 8 === Environment::exec_mode() ) {
			$this->hooks['wp_object'] = $this->tracer->start_span( 'WP Object Setup', $this->hooks['run'] );
		}
	}

	/**
	 * Trace wp hook.
	 *
	 * @since    3.0.0
	 */
	public function trace_wp_object_ready() {
		if ( array_key_exists( 'wp_object', $this->hooks ) ) {
			$this->tracer->end_span( $this->hooks['wp_object'] );
		}
		$this->hooks['render'] = $this->tracer->start_span( 'Rendering & Sending', $this->hooks['run'] );
	}

	/**
	 * Trace shutdown hook.
	 *
	 * @since    3.0.0
	 */
	public function trace_shutdown_start() {
		if ( ! isset( $this->tracer ) ) {
			return;
		}
		if ( array_key_exists( 'run', $this->hooks ) ) {
			$this->tracer->end_span( $this->hooks['run'] );
		}
		if ( array_key_exists( 'render', $this->hooks ) ) {
			$this->tracer->end_span( $this->hooks['render'] );
		}
		$this->tracer->start_span_with_id( 'Shutdown', DECALOG_SPAN_SHUTDOWN );
	}

	/**
	 * Trace setup_theme hooks.
	 *
	 * @since    3.0.0
	 */
	public function trace_setup_theme_start() {
		$this->hooks['setup_theme'] = $this->tracer->start_span_with_id( 'Theme Setup', DECALOG_SPAN_THEME_SETUP, 'WPFL' );
	}

	/**
	 * Trace setup_theme hooks.
	 *
	 * @since    3.0.0
	 */
	public function trace_setup_theme_end() {
		$this->hooks['authent'] = $this->tracer->start_span_with_id( 'User Authentication', DECALOG_SPAN_USER_AUTHENTICATION, 'WPFL' );
		if ( array_key_exists( 'setup_theme', $this->hooks ) ) {
			$this->tracer->end_span( $this->hooks['setup_theme'] );
		}
	}

	/**
	 * Trace init hooks.
	 *
	 * @since    3.0.0
	 */
	public function trace_init_start() {
		if ( array_key_exists( 'authent', $this->hooks ) ) {
			$this->tracer->end_span( $this->hooks['authent'] );
		}
		$this->hooks['init'] = $this->tracer->start_span_with_id( 'Plugins Initialization', DECALOG_SPAN_PLUGINS_INITIALIZATION, 'WPFL' );
	}

	/**
	 * Trace init hooks.
	 *
	 * @since    3.0.0
	 */
	public function trace_init_end() {
		if ( array_key_exists( 'init', $this->hooks ) ) {
			$this->tracer->end_span( $this->hooks['init'] );
		}
	}


}
