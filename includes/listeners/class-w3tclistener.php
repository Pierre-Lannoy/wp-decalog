<?php
/**
 * W3 Total Cache listener for DecaLog.
 *
 * Defines class for W3 Total Cache listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */

namespace Decalog\Listener;

use Decalog\Logger;
use Decalog\System\Post;

/**
 * W3 Total Cache listener for DecaLog.
 *
 * Defines methods and properties for W3 Total Cache listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.6.0
 */
class W3tcListener extends AbstractListener {

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'w3tc';
		$this->class   = 'plugin';
		$this->product = 'W3 Total Cache';
		$this->name    = 'W3 Total Cache';
		if ( defined( 'W3TC_VERSION' ) ) {
			$this->version = W3TC_VERSION;
		} else {
			$this->version = 'x';
		}
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.6.0
	 */
	protected function is_available() {
		return ( defined( 'W3TC' ) && W3TC );
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.6.0
	 */
	protected function launch() {
		add_action( 'w3tc_flush_dbcache', [ $this, 'w3tc_flush_dbcache' ], 10, 0 );
		add_action( 'w3tc_flush_objectcache', [ $this, 'w3tc_flush_objectcache' ], 10, 0 );
		add_action( 'w3tc_flush_after_objectcache', [ $this, 'w3tc_flush_after_objectcache' ], 10, 0 );
		add_action( 'w3tc_flush_fragmentcache', [ $this, 'w3tc_flush_fragmentcache' ], 10, 0 );
		add_action( 'w3tc_flush_after_fragmentcache', [ $this, 'w3tc_flush_after_fragmentcache' ], 10, 0 );
		add_action( 'w3tc_flush_fragmentcache_group', [ $this, 'w3tc_flush_fragmentcache_group' ], 10, 1 );
		add_action( 'w3tc_flush_after_fragmentcache_group', [ $this, 'w3tc_flush_after_fragmentcache_group' ], 10, 1 );
		add_action( 'w3tc_flush_minify', [ $this, 'w3tc_flush_minify' ], 10, 0 );
		add_action( 'w3tc_flush_after_minify', [ $this, 'w3tc_flush_after_minify' ], 10, 0 );
		add_action( 'w3tc_flush_browsercache', [ $this, 'w3tc_flush_browsercache' ], 10, 0 );
		add_action( 'w3tc_cdn_purge_all', [ $this, 'w3tc_cdn_purge_all' ], 10, 0 );
		add_action( 'w3tc_cdn_purge_all_after', [ $this, 'w3tc_cdn_purge_all_after' ], 10, 0 );
		add_action( 'w3tc_cdn_purge_files', [ $this, 'w3tc_cdn_purge_files' ], 10, 1 );
		add_action( 'w3tc_cdn_purge_files_after', [ $this, 'w3tc_cdn_purge_files_after' ], 10, 1 );
		add_action( 'w3tc_flush_all', [ $this, 'w3tc_flush_all' ], 10, 0 );
		add_action( 'w3tc_flush_url', [ $this, 'w3tc_flush_url' ], 10, 1 );
		add_action( 'w3tc_flush_group', [ $this, 'w3tc_flush_group' ], 10, 1 );
		add_action( 'w3tc_config_save', [ $this, 'w3tc_config_save' ], 10, 0 );
		add_action( 'w3tc_saved_options', [ $this, 'w3tc_config_save' ], 10, 0 );
		add_action( 'w3tc_redirect', [ $this, 'w3tc_redirect' ], 10, 0 );
		add_action( 'w3tc_register_fragment_groups', [ $this, 'w3tc_register_fragment_groups' ], 10, 0 );
		add_action( 'w3tc_flush_post', [ $this, 'w3tc_flush_post' ], 10, 1 );
		add_action( 'w3tc_flush_posts', [ $this, 'w3tc_flush_posts' ], 10, 0 );
		//add_filter( 'w3tc_usage_statistics_metric_values', [ $this, 'w3tc_usage_statistics_metric_values' ], PHP_INT_MAX - 2000, 1 );
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
	 * "w3tc_flush_dbcache" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_dbcache() {
		$this->logger->info( 'Flushing database cache.' );
	}

	/**
	 * "w3tc_flush_objectcache" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_objectcache() {
		$this->logger->info( 'Flushing object cache.' );
	}

	/**
	 * "w3tc_flush_after_objectcache" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_after_objectcache() {
		$this->logger->info( 'Object cache flushed.' );
	}

	/**
	 * "w3tc_flush_fragmentcache" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_fragmentcache() {
		$this->logger->info( 'Flushing fragment cache.' );
	}

	/**
	 * "w3tc_flush_after_fragmentcache" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_after_fragmentcache() {
		$this->logger->info( 'Fragment cache flushed.' );
	}

	/**
	 * "w3tc_flush_fragmentcache_group" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_fragmentcache_group( $group ) {
		if ( ! is_string( $group ) ) {
			$group = 'unknown';
		}
		$this->logger->info( sprintf( 'Flushing fragment cache group: %s.', $group ) );
	}

	/**
	 * "w3tc_flush_after_fragmentcache_group" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_after_fragmentcache_group( $group ) {
		if ( ! is_string( $group ) ) {
			$group = 'unknown';
		}
		$this->logger->info( sprintf( 'Fragment cache group flushed: %s.', $group ) );
	}

	/**
	 * "w3tc_flush_minify" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_minify() {
		$this->logger->info( 'Flushing minified files.' );
	}

	/**
	 * "w3tc_flush_after_minify" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_after_minify() {
		$this->logger->info( 'Minified files flushed.' );
	}

	/**
	 * "w3tc_flush_browsercache" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_browsercache() {
		$this->logger->info( 'Browser cache flushing initiated.' );
	}

	/**
	 * "w3tc_cdn_purge_all" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_cdn_purge_all() {
		$this->logger->info( 'Purging CDN.' );
	}

	/**
	 * "w3tc_cdn_purge_all_after" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_cdn_purge_all_after() {
		$this->logger->info( 'CDN purged.' );
	}

	/**
	 * "w3tc_cdn_purge_files" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_cdn_purge_files( $files ) {
		$files = implode( ', ', $files );
		if ( ! is_string( $files ) ) {
			$files = 'unknown files';
		}
		$this->logger->info( sprintf( 'Purging CDN files: %s.', $files ) );
	}

	/**
	 * "w3tc_cdn_purge_files_after" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_cdn_purge_files_after( $files ) {
		$files = implode( ', ', $files );
		if ( ! is_string( $files ) ) {
			$files = 'unknown files';
		}
		$this->logger->info( sprintf( 'CDN files purged: %s.', $files ) );
	}

	/**
	 * "w3tc_flush_group" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_group( $group ) {
		if ( ! is_string( $group ) ) {
			$group = 'unknown';
		}
		$this->logger->info( sprintf( 'Flushing group: %s.', $group ) );
	}

	/**
	 * "w3tc_flush_url" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_url( $url ) {
		if ( ! is_string( $url ) ) {
			$group = 'unknown';
		}
		$this->logger->info( sprintf( 'Flushing url: %s.', $url ) );
	}

	/**
	 * "w3tc_flush_all" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_all() {
		$this->logger->notice( 'Full cache flush.' );
	}

	/**
	 * "w3tc_config_save" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_config_save() {
		$this->logger->info( 'Settings saved.' );
	}

	/**
	 * "w3tc_redirect" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_redirect() {
		$this->logger->debug( 'Redirecting.' );
	}

	/**
	 * "w3tc_register_fragment_groups" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_register_fragment_groups() {
		$this->logger->debug( 'Registering fragment groups.' );
	}

	/**
	 * "w3tc_flush_post" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_post( $postid ) {
		if ( is_array( $postid ) ) {
			foreach ( $postid as $id ) {
				$this->logger->info( sprintf( 'File flushed: %s.', Post::get_post_string( $id ) ) );
			}
		}
		if ( is_numeric( $postid ) ) {
			$this->logger->info( sprintf( 'File flushed: %s.', Post::get_post_string( $postid ) ) );
		}
	}

	/**
	 * "w3tc_flush_posts" filter.
	 *
	 * @since    1.6.0
	 */
	public function w3tc_flush_posts() {
		$this->logger->info( 'All files flushed.' );
	}

	/**
	 * "w3tc_usage_statistics_metric_values" filter.
	 *
	 * @since    3.0.0
	 */
	public function w3tc_usage_statistics_metric_values( $metrics ) {
		return $metrics;
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
