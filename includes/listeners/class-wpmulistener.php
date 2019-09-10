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
		$this->product = 'WordPress';
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


		// Users
		add_action( 'wpmu_new_user', [ $this, 'new_user' ], 10, 1 );
		add_action( 'network_site_new_created_user', [ $this, 'new_user' ], 10, 1 );
		add_action( 'network_site_users_created_user', [ $this, 'new_user' ], 10, 1 );
		add_action( 'wpmu_activate_user', [ $this, 'wpmu_activate_user' ], 10, 3 );

		// Blogs
		add_action( 'switch_blog', [ $this, 'switch_blog' ], 10, 2 );
		add_action( 'wpmu_activate_blog', [ $this, 'wpmu_activate_blog' ], 10, 5 );
		add_action( 'wp_insert_site', [ $this, 'wp_insert_site' ], 10, 1 );
		add_action( 'wp_update_site', [ $this, 'wp_update_site' ], 10, 2 );
		add_action( 'wp_delete_site', [ $this, 'wp_delete_site' ], 10, 1 );




		return true;



		/*'wpmu_new_blog',
		'wpmu_activate_blog',
		'wpmu_new_user',
		'add_user_to_blog',
		'remove_user_from_blog',



		'make_spam_blog',
		'make_ham_blog',
		'mature_blog',
		'unmature_blog',
		'archive_blog',
		'unarchive_blog',
		'make_delete_blog',
		'make_undelete_blog',


		'update_blog_public',*/
	}

	/**
	 * "wpmu_new_user", "network_site_users_created_user" and "network_site_new_created_user" events.
	 *
	 * @since    1.0.0
	 */
	public function wpmu_new_user( $id ) {
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
	 * "switch_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function switch_blog( $to_id, $from_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Blog switched from %s to %s.', Blog::get_full_blog_name( $from_id ), Blog::get_full_blog_name( $to_id ) ) );
		}
	}

	/**
	 * "wpmu_activate_blog" events.
	 *
	 * @since    1.0.0
	 */
	public function wpmu_activate_blog( $blog_id, $user_id, $password, $title, $meta ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Blog activated: %s.', Blog::get_full_blog_name( $blog_id ) ) );
		}
	}

	/**
	 * "wp_insert_site" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_insert_site( $blog ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Blog created: %s.', Blog::get_full_blog_name( $blog ) ) );
		}
	}

	/**
	 * "wp_update_site" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_update_site( $to_id, $from_id ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Blog updated: %s.', Blog::get_full_blog_name( $to_id ) ) );
		}
	}

	/**
	 * "wp_delete_site" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_delete_site( $blog ) {
		if ( isset( $this->logger ) ) {
			$this->logger->notice( sprintf( 'Blog deleted: %s.', Blog::get_full_blog_name( $blog ) ) );
		}
	}

}
