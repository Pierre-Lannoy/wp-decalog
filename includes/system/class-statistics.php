<?php
/**
 * Plugin statistics handling
 *
 * Handles all user plugin statistics and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
 */

namespace Decalog\System;

/**
 * Define the plugin statistics functionality.
 *
 * Handles all plugin statistics operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
 */
class Statistics {

	/**
	 * The unique instance of the class.
	 *
	 * @since  1.3.0
	 * @var self $instance The unique instance of the class.
	 */
	private static $instance;

	/**
	 * The number of active installs for this plugin.
	 *
	 * @since  1.3.0
	 * @var    integer    $installs    The number of active installs.
	 */
	public $installs = -1;

	/**
	 * The number of active downloads for this plugin.
	 *
	 * @since  1.3.0
	 * @var    integer    $downloads    The number of downloads.
	 */
	public $downloads = -1;

	/**
	 * The rating of this plugin.
	 *
	 * @since  1.3.0
	 * @var    integer    $rating    The rating.
	 */
	public $rating = -1;

	/**
	 * The number of reviews for this plugin.
	 *
	 * @since  1.3.0
	 * @var    integer    $reviews    The number of reviews.
	 */
	public $reviews = -1;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		$this->get_wp_stats();
	}

	/**
	 * Get the WP stats about active installs, downloads, rating and reviews.
	 *
	 * @since   1.3.0
	 */
	private function get_wp_stats() {
		$stats = Cache::get_global( 'self_wp_stats' );
		if ( ! $stats ) {
			try {
				if ( ! function_exists( 'plugins_api' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				}
				$query = [
					'active_installs' => true,
					'downloaded'      => true,
					'rating'          => true,
					'num_ratings'     => true,
				];
				$api   = plugins_api(
					'plugin_information',
					[
						'slug'   => DECALOG_SLUG,
						'fields' => $query,
					]
				);
				if ( ! is_wp_error( $api ) ) {
					$result = get_object_vars( $api );
					Cache::set_global( 'self_wp_stats', $result, 'plugin-statistics' );
					$stats = $result;
				} else {
					$stats = false;
				}
			} catch ( \Throwable $ex ) {
				$stats = false;
			}
		}
		if ( false !== $stats ) {
			if ( array_key_exists( 'active_installs', $stats ) ) {
				$this->installs = $stats['active_installs'];
			}
			if ( array_key_exists( 'downloaded', $stats ) ) {
				$this->downloads = $stats['downloaded'];
			}
			if ( array_key_exists( 'rating', $stats ) ) {
				$this->rating = $stats['rating'];
			}
			if ( array_key_exists( 'num_ratings', $stats ) ) {
				$this->reviews = $stats['num_ratings'];
			}
		}
	}

	/**
	 * Get the statistics as shortcode.
	 *
	 * @param   array $attributes  Attributes of the shortcode.
	 * @return  int|string  The output of the shortcode, ready to print.
	 * @since   1.3.0
	 */
	public static function sc_get_raw( $attributes ) {
		$_attributes = shortcode_atts(
			[
				'item' => 'rating',
			],
			$attributes
		);
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Statistics();
		}
		switch ( $_attributes['item'] ) {
			case 'installs':
				$result = self::$instance->installs;
				break;
			case 'downloads':
				$result = self::$instance->downloads;
				break;
			case 'rating':
				$result = self::$instance->rating;
				break;
			case 'reviews':
				$result = self::$instance->reviews;
				break;
			default:
				$result = '';
		}
		return $result;
	}
}
