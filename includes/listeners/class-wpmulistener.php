<?php
/**
 * WP MU listener for DecaLog.
 *
 * Defines class for WP MU listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Blog;

/**
 * WP MU listener for DecaLog.
 *
 * Defines methods and properties for WP MU listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
 */
class WpmuListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.3.0
	 */
	protected function init() {
		global $wp_version;
		$this->id      = 'wpmu';
		$this->name    = esc_html__( 'WordPress MU', 'decalog' );
		$this->class   = 'core';
		$this->product = 'WordPress MU';
		$this->version = $wp_version;
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.3.0
	 */
	protected function is_available() {
		return Environment::is_wordpress_multisite();
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.3.0
	 */
	protected function launch() {
		// Users.
		add_action( 'wpmu_new_user', [ $this, 'new_user' ], 10, 1 );
		add_action( 'network_site_new_created_user', [ $this, 'new_user' ], 10, 1 );
		add_action( 'network_site_users_created_user', [ $this, 'new_user' ], 10, 1 );
		add_action( 'wpmu_activate_user', [ $this, 'wpmu_activate_user' ], 10, 3 );
		add_action( 'wpmu_delete_user', [ $this, 'wpmu_delete_user' ], 10, 1 );
		add_action( 'make_spam_user', [ $this, 'make_spam_user' ], 10, 1 );
		add_action( 'make_ham_user', [ $this, 'make_ham_user' ], 10, 1 );

		// Blogs.
		add_action( 'wpmu_activate_blog', [ $this, 'wpmu_activate_blog' ], 10, 5 );
		add_action( 'wp_insert_site', [ $this, 'wp_insert_site' ], 10, 1 );
		add_action( 'wp_update_site', [ $this, 'wp_update_site' ], 10, 2 );
		add_action( 'wp_delete_site', [ $this, 'wp_delete_site' ], 10, 1 );

		// Roles.
		add_action( 'add_user_to_blog', [ $this, 'add_user_to_blog' ], 10, 3 );
		add_action( 'remove_user_from_blog', [ $this, 'remove_user_from_blog' ], 10, 2 );

		// Modes & reports.
		add_action( 'update_blog_public', [ $this, 'update_blog_public' ], 10, 2 );
		add_action( 'make_spam_blog', [ $this, 'make_spam_blog' ], 10, 1 );
		add_action( 'make_ham_blog', [ $this, 'make_ham_blog' ], 10, 1 );
		add_action( 'mature_blog', [ $this, 'mature_blog' ], 10, 1 );
		add_action( 'unmature_blog', [ $this, 'unmature_blog' ], 10, 1 );
		add_action( 'archive_blog', [ $this, 'archive_blog' ], 10, 1 );
		add_action( 'unarchive_blog', [ $this, 'unarchive_blog' ], 10, 1 );
		add_action( 'make_delete_blog', [ $this, 'make_delete_blog' ], 10, 1 );
		add_action( 'make_undelete_blog', [ $this, 'make_undelete_blog' ], 10, 1 );
		return true;
	}

	/**
	 * "wpmu_new_user", "network_site_users_created_user" and "network_site_new_created_user" events.
	 *
	 * @since    1.0.0
	 */
	public function new_user( $id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'User created: %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "wpmu_new_user" events.
	 *
	 * @since    1.0.0
	 */
	public function wpmu_activate_user( $id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'User activated: %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "wpmu_delete_user" event.
	 *
	 * @since    1.0.0
	 */
	public function wpmu_delete_user( $id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'User deleted: %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "make_spam_user" events.
	 *
	 * @since    1.0.0
	 */
	public function make_spam_user( $id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->warning( sprintf( 'User marked as "spam": %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "make_ham_user" events.
	 *
	 * @since    1.0.0
	 */
	public function make_ham_user( $id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'User marked as "not spam": %s.', $this->get_user( $id ) ) );
		}
	}

	/**
	 * "wpmu_activate_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function wpmu_activate_blog( $blog_id, $user_id, $password, $title, $meta ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Site activated: %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "wp_insert_site" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_insert_site( $blog ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Site created: %s.', Blog::get_full_blog_name( $blog ) ) );
		}
	}

	/**
	 * "wp_update_site" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_update_site( $to_id, $from_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Site updated: %s.', Blog::get_full_blog_name( $to_id ) ) );
		}
	}

	/**
	 * "wp_delete_site" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_delete_site( $blog ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Site deleted: %s.', Blog::get_full_blog_name( $blog ) ) );
		}
	}

	/**
	 * "add_user_to_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function add_user_to_blog( $user_id, $role, $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( '%s added to %s with "%s" role.', $this->get_user( $user_id ), Blog::get_full_blog_name( $blog_id ), $role ) );
		}
	}

	/**
	 * "remove_user_from_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function remove_user_from_blog( $user_id, $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( '%s removed from %s.', $this->get_user( $user_id ), Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "update_blog_public" events.
	 *
	 * @since    1.0.0
	 */
	public function update_blog_public( $site_id, $public ) {
		if ( isset( $this->logger ) ) {
			if ( $public ) {
				$this->logger->info( sprintf( 'Site became public: %s.', Blog::get_full_blog_name( $site_id ) ) );
			} else {
				$this->logger->info( sprintf( 'Site became private: %s.', Blog::get_full_blog_name( $site_id ) ) );
			}

		}
	}

	/**
	 * "make_spam_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function make_spam_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->warning( sprintf( 'Site marked as "spam": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "make_ham_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function make_ham_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Site marked as "not spam": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "mature_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function mature_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->warning( sprintf( 'Site marked as "mature": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "unmature_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function unmature_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Site marked as "not mature": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "archive_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function archive_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Site marked as "archived": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "unarchive_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function unarchive_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Site marked as "unarchived": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "make_delete_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function make_delete_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Site marked as "deleted": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "make_undelete_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function make_undelete_blog( $blog_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->info( sprintf( 'Site marked as "undeleted": %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

}
