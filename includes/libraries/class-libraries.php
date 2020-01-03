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
			'prefix'  => 'Monolog',
			'base'    => DECALOG_VENDOR_DIR . 'monolog/',
			'version' => '2.0.2',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Jordi Boggiano' ),
			'url'     => 'https://github.com/Seldaek/monolog',
			'license' => 'mit',
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
		self::$psr4_libraries['psr-3']     = [
			'name'    => 'PSR-3',
			'prefix'  => 'Psr\\Log',
			'base'    => DECALOG_VENDOR_DIR . 'psr/log/',
			'version' => '',
			'author'  => 'PHP Framework Interop Group',
			'url'     => 'https://www.php-fig.org/',
			'license' => 'mit',
			'langs'   => 'en',
		];
		self::$mono_libraries              = [];
		self::$mono_libraries['parsedown'] = [
			'name'    => 'Parsedown',
			'detect'  => 'Parsedown',
			'base'    => DECALOG_VENDOR_DIR . 'parsedown/',
			'version' => '1.8.0-beta-7',
			// phpcs:ignore
			'author'  => sprintf( esc_html__( '%s & contributors', 'decalog' ), 'Emanuil Rusev' ),
			'url'     => 'https://parsedown.org',
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
				$items[] = sprintf( __( '<a href="%1$s">%2$s %3$s</a>%4$s by %5$s (%6$s)', 'decalog' ), $library['url'], $library['name'], $library['version'], $library['langs'], $library['author'], $library['license'] );
			}
			$result = implode( ', ', $items );
		}
		return $result;
	}

}

Libraries::init();
