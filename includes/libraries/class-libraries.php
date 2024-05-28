<?php
/**
 * Libraries handling
 *
 * Handles all libraries (vendor) operations and versioning.
 *
 * @package Libraries
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Library;

use Decalog\System\L10n;

/**
 * Define the libraries functionality.
 *
 * Handles all libraries (vendor) operations and versioning.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Libraries {

	/**
	 * The array of PSR-4 libraries used by the plugin.
	 *
	 * @since  1.0.0
	 * @var    array    $libraries    The PSR-4 libraries used by the plugin.
	 */
	private static $psr4_libraries;

	/**
	 * The array of mono libraries used by the plugin.
	 *
	 * @since  1.0.0
	 * @var    array    $libraries    The mono libraries used by the plugin.
	 */
	private static $mono_libraries;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Defines all needed libraries.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$psr4_libraries              = [];
		self::$psr4_libraries['monolog']   = [
			'name'    => 'Monolog',
			'prefix'  => 'DLMonolog',
			'base'    => DECALOG_VENDOR_DIR . 'monolog/',
			'version' => DECALOG_MONOLOG_VERSION,
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Jordi Boggiano' ),
			'url'     => 'https://github.com/Seldaek/monolog',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['tracy']   = [
			'name'    => 'Tracy',
			'prefix'  => 'Tracy',
			'base'    => DECALOG_VENDOR_DIR . 'tracy/',
			'version' => '2.10.7',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'David Grudl' ),
			'url'     => 'https://github.com/nette/tracy',
			'license' => 'gpl3',
			'langs'   => 'en',
		];
		self::$psr4_libraries['prometheus']   = [
			'name'    => 'Prometheus PHP',
			'prefix'  => 'Prometheus',
			'base'    => DECALOG_VENDOR_DIR . 'prometheus/',
			'version' => '2.2.1',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s, %s & contributors', 'decalog' ), 'Lukas Kämmerling', 'Rafael Dohms' ),
			'url'     => 'https://github.com/PromPHP',
			'license' => 'apl2',
			'langs'   => 'en',
		];
		self::$psr4_libraries['thrift']   = [
			'name'    => 'Apache Thrift',
			'prefix'  => 'Thrift',
			'base'    => DECALOG_VENDOR_DIR . 'thrift/',
			'version' => '0.13.01',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'The Apache Software Foundation' ),
			'url'     => 'https://github.com/apache/thrift',
			'license' => 'apl2',
			'langs'   => 'en',
		];
		self::$psr4_libraries['jaeger']   = [
			'name'    => 'Jaeger Bindings for PHP OpenTracing API',
			'prefix'  => 'Jaeger',
			'base'    => DECALOG_VENDOR_DIR . 'jaeger/',
			'version' => '1.2.2',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Jonah George' ),
			'url'     => 'https://github.com/jonahgeorge/jaeger-client-php',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['influxdb']   = [
			'name'    => 'InfluxDB 2 client',
			'prefix'  => 'InfluxDB2',
			'base'    => DECALOG_VENDOR_DIR . 'influxdb/',
			'version' => '1.12.0',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'InfluxData' ),
			'url'     => 'https://github.com/influxdata/influxdb-client-php',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['lock']   = [
			'name'    => 'Lock',
			'prefix'  => 'malkusch\lock',
			'base'    => DECALOG_VENDOR_DIR . 'lock/',
			'version' => '2.2',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Willem Stuursma-Ruwen' ),
			'url'     => 'https://github.com/php-lock/lock',
			'license' => 'wtpfl',
			'langs'   => 'en',
		];
		self::$psr4_libraries['elasticsearch']   = [
			'name'    => 'Elasticsearch',
			'prefix'  => 'Elastic\\Elasticsearch',
			'base'    => DECALOG_VENDOR_DIR . 'elastic/elasticsearch/',
			'version' => '8.13.0',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Elastic' ),
			'url'     => 'https://github.com/elastic/elasticsearch-php',
			'license' => 'apl2',
			'langs'   => 'en',
		];
		self::$psr4_libraries['elastictransport']   = [
			'name'    => 'Elastic Transport',
			'prefix'  => 'Elastic\\Transport',
			'base'    => DECALOG_VENDOR_DIR . 'elastic/transport/',
			'version' => '8.8.0',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Elastic' ),
			'url'     => 'https://github.com/elastic/elasticsearch-php',
			'license' => 'apl2',
			'langs'   => 'en',
		];
		self::$psr4_libraries['guzzlehttp']   = [
			'name'    => 'GuzzleHttp',
			'prefix'  => 'DLGuzzleHttp',
			'base'    => DECALOG_VENDOR_DIR . 'guzzlehttp/',
			'version' => '7.8.1',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Michael Dowling' ),
			'url'     => 'https://github.com/elastic/elasticsearch-php',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['httpdiscovery']   = [
			'name'    => 'HTTPlug Discovery',
			'prefix'  => 'Http\\Discovery',
			'base'    => DECALOG_VENDOR_DIR . 'http/discovery/',
			'version' => '1.19.4',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'The PHP HTTP group' ),
			'url'     => 'https://github.com/php-http',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['httpclient']   = [
			'name'    => 'HTTPlug',
			'prefix'  => 'Http\\Client',
			'base'    => DECALOG_VENDOR_DIR . 'http/client/',
			'version' => '2.4.0',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'The PHP HTTP group' ),
			'url'     => 'https://github.com/php-http',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['httpclientcommon']   = [
			'name'    => 'HTTP Client Common',
			'prefix'  => 'Http\\Client\\Common',
			'base'    => DECALOG_VENDOR_DIR . 'http/client-common/',
			'version' => '2.7.1',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'The PHP HTTP group' ),
			'url'     => 'https://github.com/php-http',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['httppromise']   = [
			'name'    => 'HTTP Promise',
			'prefix'  => 'Http\\Promise',
			'base'    => DECALOG_VENDOR_DIR . 'http/promise/',
			'version' => '1.3.1',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'The PHP HTTP group' ),
			'url'     => 'https://github.com/php-http',
			'license' => 'mit',
			'langs'   => 'en',
		];

		self::$psr4_libraries['symfonyoptionsresolver']   = [
			'name'    => 'Symfony Options Resolver',
			'prefix'  => 'Symfony\\Component\\OptionsResolver',
			'base'    => DECALOG_VENDOR_DIR . 'symfony/OptionsResolver/',
			'version' => '6.4',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Fabien Potencier' ),
			'url'     => 'https://github.com/symfony/symfony',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['react']   = [
			'name'    => 'ReactPHP Promise',
			'prefix'  => 'React',
			'base'    => DECALOG_VENDOR_DIR . 'react/',
			'version' => '2.7.1',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'ReactPHP' ),
			'url'     => 'https://github.com/elastic/elasticsearch-php',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['sentry']   = [
			'name'    => 'Sentry SDK',
			'prefix'  => 'Sentry',
			'base'    => DECALOG_VENDOR_DIR . 'sentry/',
			'version' => '3.1.5',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Michael Van Tellingen' ),
			'url'     => 'https://github.com/getsentry/sentry-php',
			'license' => 'ukn',
			'langs'   => 'en',
		];
		self::$psr4_libraries['feather']   = [
			'name'    => 'Feather',
			'prefix'  => 'Feather',
			'base'    => DECALOG_VENDOR_DIR . 'feather/',
			'version' => '4.24.1',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Cole Bemis' ),
			'url'     => 'https://feathericons.com',
			'license' => 'mit',
			'langs'   => 'en',
		];
		if ( function_exists( 'decalog_get_psr_log_version' ) ) {
			$psrlog_version = decalog_get_psr_log_version();
		} else {
			$psrlog_version = 1;
		}
		self::$psr4_libraries['psr-03']      = [
			'name'    => 'PSR-3',
			'prefix'  => 'Psr\\Log',
			'base'    => DECALOG_VENDOR_DIR . 'psr/log-v' . $psrlog_version . '/',
			'version' => $psrlog_version . '.0.0',
			'author'  => 'PHP Framework Interop Group',
			'url'     => 'https://www.php-fig.org/',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['psr-07']     = [
			'name'    => 'PSR-7',
			'prefix'  => 'Psr\\Http\\Message',
			'base'    => DECALOG_VENDOR_DIR . 'psr/http-message/',
			'version' => '2.0',
			'author'  => 'PHP Framework Interop Group',
			'url'     => 'https://www.php-fig.org/',
			'license' => 'mit',
			'langs'   => 'en',
		];

		self::$psr4_libraries['psr-11']     = [
			'name'    => 'PSR-11',
			'prefix'  => 'Psr\\Container',
			'base'    => DECALOG_VENDOR_DIR . 'psr/container/',
			'version' => '',
			'author'  => 'PHP Framework Interop Group',
			'url'     => 'https://www.php-fig.org/',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['psr-18']     = [
			'name'    => 'PSR-18',
			'prefix'  => 'Psr\\Http\\Client',
			'base'    => DECALOG_VENDOR_DIR . 'psr/http-client/',
			'version' => '1.0.3',
			'author'  => 'PHP Framework Interop Group',
			'url'     => 'https://www.php-fig.org/',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['markdown'] = [
			'name'    => 'Markdown Parser',
			'prefix'  => 'cebe\markdownparser',
			'base'    => DECALOG_VENDOR_DIR . 'markdown/',
			'version' => '1.2.1',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Carsten Brandt' ),
			'url'     => 'https://github.com/cebe/markdown',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['spatie-ray']     = [
			'name'    => 'Ray',
			'prefix'  => 'DLSpatie\\Ray',
			'base'    => DECALOG_VENDOR_DIR . 'spatie/ray/src/',
			'version' => '1.33.0',
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Spatie' ),
			'url'     => 'https://github.com/spatie/ray',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['spatie-macroable']     = [
			'name'    => 'Macroable',
			'prefix'  => 'DLSpatie\\Macroable',
			'base'    => DECALOG_VENDOR_DIR . 'spatie/macroable/src/',
			'version' => '2.0.0',
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Spatie' ),
			'url'     => 'https://github.com/spatie/macroable',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$psr4_libraries['spatie-backtrace']     = [
			'name'    => 'Backtrace',
			'prefix'  => 'DLSpatie\\Backtrace',
			'base'    => DECALOG_VENDOR_DIR . 'spatie/backtrace/src/',
			'version' => '1.2.1',
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Spatie' ),
			'url'     => 'https://github.com/spatie/backtrace',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$mono_libraries             = [];
		self::$mono_libraries['spyc']     = [
			'name'    => 'Spyc',
			'detect'  => 'Spyc',
			'base'    => DECALOG_VENDOR_DIR . 'spyc/',
			'version' => '0.6.2',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s, %s & contributors', 'decalog' ), 'Vlad Andersen', 'Chris Wanstrath' ),
			'url'     => 'https://github.com/mustangostang/spyc/',
			'license' => 'mit',
			'langs'   => 'en',
		];
	}

	/**
	 * Get PSR-4 libraries.
	 *
	 * @return  array   The list of defined PSR-4 libraries.
	 * @since 1.0.0
	 */
	public static function get_psr4() {
		return self::$psr4_libraries;
	}

	/**
	 * Get mono libraries.
	 *
	 * @return  array   The list of defined mono libraries.
	 * @since 1.0.0
	 */
	public static function get_mono() {
		return self::$mono_libraries;
	}

	/**
	 * Compare two items based on name field.
	 *
	 * @param  array $a     The first element.
	 * @param  array $b     The second element.
	 * @return  boolean     True if $a>$b, false otherwise.
	 * @since 1.0.0
	 */
	public function reorder_list( $a, $b ) {
		return strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
	}

	/**
	 * Get the full license name.
	 *
	 * @param  string $license     The license id.
	 * @return  string     The full license name.
	 * @since 1.0.0
	 */
	private function license_name( $license ) {
		switch ( $license ) {
			case 'mit':
				$result = esc_html__( 'MIT license', 'decalog' );
				break;
			case 'wtpfl':
				$result = esc_html__( 'WTFPL license', 'decalog' );
				break;
			case 'apl2':
				$result = esc_html__( 'Apache license, version 2.0', 'decalog' );
				break;
			case 'gpl2':
				$result = esc_html__( 'GPL-2.0 license', 'decalog' );
				break;
			case 'gpl3':
				$result = esc_html__( 'GPL-3.0 license', 'decalog' );
				break;
			default:
				$result = esc_html__( 'unknown license', 'decalog' );
				break;
		}
		return $result;
	}

	/**
	 * Get the libraries list.
	 *
	 * @param   array $attributes  'style' => 'html'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_list( $attributes ) {
		$_attributes = shortcode_atts(
			[
				'style' => 'html',
			],
			$attributes
		);
		$style       = $_attributes['style'];
		$result      = '';
		$list        = [];
		foreach ( array_merge( self::get_psr4(), self::get_mono() ) as $library ) {
			$item            = [];
			$item['name']    = $library['name'];
			$item['version'] = $library['version'];
			$item['author']  = $library['author'];
			$item['url']     = $library['url'];
			$item['license'] = $this->license_name( $library['license'] );
			$item['langs']   = L10n::get_language_markup( explode( ',', $library['langs'] ) );
			$list[]          = $item;
		}
		$item            = [];
		$item['name']    = 'Plugin Boilerplate';
		$item['version'] = '';
		$item['author']  = 'Pierre Lannoy';
		// phpcs:ignore
		$item['url']     = 'https://github.com/Pierre-Lannoy/wp-' . 'plugin-' . 'boilerplate';
		$item['license'] = $this->license_name( 'gpl3' );
		$item['langs']   = L10n::get_language_markup( [ 'en' ] );
		$list[]          = $item;
		usort(
			$list,
			function ( $a, $b ) {
				return strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
			}
		);
		if ( 'html' === $style ) {
			$items = [];
			foreach ( $list as $library ) {
				/* translators: as in the sentence "Product W version X by author Y (license Z)" */
				$items[] = sprintf( __( '<a href="%1$s">%2$s %3$s</a>%4$s by %5$s (%6$s)', 'decalog' ), $library['url'], $library['name'], 'v' . $library['version'], $library['langs'], $library['author'], $library['license'] );
			}
			$result = implode( ', ', $items );
		}
		return $result;
	}

}

Libraries::init();
