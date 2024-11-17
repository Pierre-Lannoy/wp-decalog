<?php
/**
 * Site health helper.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

use Decalog\System\Cache;
use Decalog\System\I18n;
use Decalog\System\Option;
use Decalog\Plugin\Feature\LoggerMaintainer;

/**
 * The class responsible to handle cache management.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Sitehealth {

	/**
	 * The slug of the calling plugin.
	 *
	 * @since  1.0.0
	 * @var    string    $slug    The slug.
	 */
	private static $slug = DECALOG_SLUG;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initializes properties.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::perfopsone_init();
		self::plugin_init();
	}

	/**
	 * Sets site health hooks for PerfOps One.
	 *
	 * @since 1.0.0
	 */
	private static function perfopsone_init() {
		self::perfopsone_test_init();
		self::perfopsone_info_init();
	}

	/**
	 * Sets site health hooks specific to this plugin.
	 *
	 * @since 1.0.0
	 */
	private static function plugin_init() {
		self::plugin_test_init();
		self::plugin_info_init();
	}

	/**
	 * Sets site health hooks for PerfOps One tests.
	 *
	 * @since 1.0.0
	 */
	private static function perfopsone_test_init() {
		add_filter( 'site_status_tests', [ self::class, 'perfopsone_test_objectcache' ] );
		add_filter( 'site_status_tests', [ self::class, 'perfopsone_test_opcache' ] );
		add_filter( 'site_status_tests', [ self::class, 'perfopsone_test_shmop' ] );
		if ( 'en_US' !== get_locale() ) {
			add_filter( 'site_status_tests', [ self::class, 'perfopsone_test_i18n' ] );
		}
	}

	/**
	 * Sets site health hooks for PerfOps One infos.
	 *
	 * @since 1.0.0
	 */
	private static function perfopsone_info_init() {
		add_filter( 'debug_information', [ self::class, 'perfopsone_info' ] );
	}

	/**
	 * Sets site health hooks for plugin tests.
	 *
	 * @since 1.0.0
	 */
	private static function plugin_test_init() {

	}

	/**
	 * Sets site health hooks for plugin infos.
	 *
	 * @since 1.0.0
	 */
	private static function plugin_info_init() {
		add_filter( 'debug_information', [ self::class, 'plugin_info' ] );
		add_filter( 'debug_information', [ self::class, 'loggers_info' ] );
	}

	/**
	 * Adds plugin infos section.
	 *
	 * @param array $debug_info The already set infos.
	 * @return array    The extended infos if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_info( $debug_info ) {
		$key = 'perfopsone_objectcache';
		if ( ! array_key_exists( $key, $debug_info ) ) {
			$debug_info[ $key ] = [
				'label'  => 'PerfOps One - ' . decalog_esc_html__( 'Object cache', 'decalog' ),
				'fields' => Cache::debug_info(),
			];
		}
		$key = 'perfopsone_opcache';
		if ( ! array_key_exists( $key, $debug_info ) ) {
			$debug_info[ $key ] = [
				'label'       => 'OPcache',
				'description' => decalog_esc_html__( 'OPcache settings and status', 'decalog' ),
				'fields'      => OPcache::debug_info(),
			];
		}
		return $debug_info;
	}

	/**
	 * Adds plugin infos section.
	 *
	 * @param array $debug_info The already set infos.
	 * @return array    The extended infos if needed.
	 * @since 1.0.0
	 */
	public static function plugin_info( $debug_info ) {
		$debug_info[ self::$slug ] = [
			'label'       => DECALOG_PRODUCT_NAME,
			'description' => decalog_esc_html__( 'Plugin diagnostic information', 'decalog' ),
			'fields'      => Option::debug_info(),
		];
		return $debug_info;
	}

	/**
	 * Adds plugin infos section.
	 *
	 * @param array $debug_info The already set infos.
	 * @return array    The extended infos if needed.
	 * @since 1.0.0
	 */
	public static function loggers_info( $debug_info ) {
		$maintener                              = new LoggerMaintainer();
		$debug_info[ self::$slug . '_loggers' ] = [
			'label'       => decalog_esc_html__( 'Loggers', 'decalog' ),
			'description' => decalog_esc_html__( 'Loggers list', 'decalog' ),
			'show_count'  => true,
			'fields'      => $maintener->debug_info(),
		];
		return $debug_info;
	}

	/**
	 * Adds a test.
	 *
	 * @param array $tests The already set tests.
	 * @return array    The extended tests if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_objectcache( $tests ) {
		$key = 'perfopsone_objectcache';
		if ( ! array_key_exists( $key, $tests['direct'] ) ) {
			$tests['direct'][ $key ] = [
				'label' =>decalog__( 'Object Cache Test', 'decalog' ),
				'test'  => [ self::class, 'perfopsone_test_objectcache_do' ],
			];
		}
		return $tests;
	}

	/**
	 * Does a test.
	 *
	 * @return array    The result of the test.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_objectcache_do() {
		$key       = 'perfopsone_objectcache';
		$analytics = Cache::get_analytics();
		if ( 'apcu' === $analytics['type'] ) {
			return [
				'label'       => decalog_esc_html__( 'You should improve object caching', 'decalog' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => decalog_esc_html__( 'Performance', 'decalog' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s %s</p>', decalog_esc_html__( 'APCu is available on your site, but only PerfOps One suite and some few other plugins know how to take advantage of it.', 'decalog' ), sprintf( decalog_esc_html__( 'You should consider using %s to improve your site\'s speed.', 'decalog' ), '<a href="https://perfops.one/apcu-manager/">APCu Manager</a>' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		if ( 'object_cache' === $analytics['type'] ) {
			return [
				'label'       => decalog_esc_html__( 'Your site uses object caching', 'decalog' ),
				'status'      => 'good',
				'badge'       => [
					'label' => decalog_esc_html__( 'Performance', 'decalog' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', decalog_esc_html__( 'Your site uses a dedicated object caching mechanism. That\'s great.', 'decalog' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		return [
			'label'       => decalog_esc_html__( 'You should use object caching', 'decalog' ),
			'status'      => 'recommended',
			'badge'       => [
				'label' => decalog_esc_html__( 'Performance', 'decalog' ),
				'color' => 'orange',
			],
			'description' => sprintf( '<p>%s %s</p>', decalog_esc_html__( 'Your site uses database transient.', 'decalog' ), decalog_esc_html__( 'You should consider using a dedicated object caching mechanism, like APCu, Memcached or Redis, to improve your site\'s speed.', 'decalog' ) ),
			'actions'     => '',
			'test'        => $key,
		];
	}

	/**
	 * Adds a test.
	 *
	 * @param array $tests The already set tests.
	 * @return array    The extended tests if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_opcache( $tests ) {
		$key = 'perfopsone_opcache';
		if ( ! array_key_exists( $key, $tests['direct'] ) ) {
			$tests['direct'][ $key ] = [
				'label' =>decalog__( 'OPcache Test', 'decalog' ),
				'test'  => [ self::class, 'perfopsone_test_opcache_do' ],
			];
		}
		return $tests;
	}

	/**
	 * Does a test.
	 *
	 * @return array    The result of the test.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_opcache_do() {
		$key = 'perfopsone_opcache';
		if ( function_exists( 'opcache_invalidate' ) && function_exists( 'opcache_compile_file' ) && function_exists( 'opcache_is_script_cached' ) ) {
			$result = [
				'label'       => decalog_esc_html__( 'Your site uses OPcache', 'decalog' ),
				'status'      => 'good',
				'badge'       => [
					'label' => decalog_esc_html__( 'Performance', 'decalog' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', decalog_esc_html__( 'Your site uses OPcache to improve PHP performance. That\'s great.', 'decalog' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		} else {
			$result = [
				'label'       => decalog_esc_html__( 'You should use OPcache', 'decalog' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => decalog_esc_html__( 'Performance', 'decalog' ),
					'color' => 'orange',
				],
				'description' => sprintf( '<p>%s</p>', decalog_esc_html__( 'You should consider using OPcache. It would improve PHP performance of your site.', 'decalog' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		return $result;
	}

	/**
	 * Adds a test.
	 *
	 * @param array $tests The already set tests.
	 * @return array    The extended tests if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_shmop( $tests ) {
		$key = 'perfopsone_shmop';
		if ( ! array_key_exists( $key, $tests['direct'] ) ) {
			$tests['direct'][ $key ] = [
				'label' =>decalog__( 'Shared Memory Test', 'decalog' ),
				'test'  => [ self::class, 'perfopsone_test_shmop_do' ],
			];
		}
		return $tests;
	}

	/**
	 * Does a test.
	 *
	 * @return array    The result of the test.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_shmop_do() {
		$key = 'perfopsone_shmop';
		if ( function_exists( 'shmop_open' ) && function_exists( 'shmop_read' ) && function_exists( 'shmop_write' ) && function_exists( 'shmop_delete' ) ) {
			$result = [
				'label'       => decalog_esc_html__( 'Your site can use shared memory', 'decalog' ),
				'status'      => 'good',
				'badge'       => [
					'label' => decalog_esc_html__( 'Performance', 'decalog' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', decalog_esc_html__( 'Your site can use shared memory to allow inter-process communication. That\'s great.', 'decalog' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		} else {
			$result = [
				'label'       => decalog_esc_html__( 'You should allow inter-process communication', 'decalog' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => decalog_esc_html__( 'Performance', 'decalog' ),
					'color' => 'gray',
				],
				'description' => sprintf( '<p>%s</p>', decalog_esc_html__( 'You should consider using shared memory (PHP shmop) to allow inter-process communication.', 'decalog' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		return $result;
	}

	/**
	 * Adds a test.
	 *
	 * @param array $tests The already set tests.
	 * @return array    The extended tests if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_i18n( $tests ) {
		$key = 'perfopsone_i18n';
		if ( ! array_key_exists( $key, $tests['direct'] ) ) {
			$tests['direct'][ $key ] = [
				'label' =>decalog__( 'I18n Extension Test', 'decalog' ),
				'test'  => [ self::class, 'perfopsone_test_i18n_do' ],
			];
		}
		return $tests;
	}

	/**
	 * Does a test.
	 *
	 * @return array    The result of the test.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_i18n_do() {
		$key = 'perfopsone_i18n';
		if ( I18n::is_extension_loaded() ) {
			$result = [
				'label'       => decalog_esc_html__( 'Your site uses Intl extension', 'decalog' ),
				'status'      => 'good',
				'badge'       => [
					'label' => decalog_esc_html__( 'Internationalization', 'decalog' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', decalog_esc_html__( 'Your site uses PHP Intl extension to improve localization features. That\'s great.', 'decalog' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		} else {
			$result = [
				'label'       => decalog_esc_html__( 'You should use Intl extension', 'decalog' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => decalog_esc_html__( 'Internationalization', 'decalog' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', decalog_esc_html__( 'You should consider using PHP Intl extension. It would improve localization features on your site.', 'decalog' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		return $result;
	}

}
