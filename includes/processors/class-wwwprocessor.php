<?php declare(strict_types=1);
/**
 * Web records processing
 *
 * Extends Decalog\Processor\WebProcessor to respect privacy settings.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Processor;

use Decalog\System\Hash;
use Monolog\Processor\WebProcessor;
use Decalog\System\Environment;
use Decalog\System\IP;

/**
 * Define the WWW processor functionality.
 *
 * Extends Decalog\Processor\WebProcessor to respect privacy settings.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class WWWProcessor extends WebProcessor {

	/**
	 * Obfuscation switch.
	 *
	 * @since  1.0.0
	 * @var    boolean    $obfuscation    Is obfuscation activated?
	 */
	private $obfuscation = false;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param   array|ArrayAccess|null $serverData  Array or object w/ ArrayAccess that provides access to the $_SERVER data.
	 * @param   array|null             $extraFields Field names and the related key inside $serverData to be added. If not provided it defaults to: url, ip, http_method, server, referrer.
	 * @param   boolean                $obfuscation Optional. Is obfuscation activated?
	 */
	public function __construct( $serverData = null, array $extraFields = null, $obfuscation = false ) {
		parent::__construct( $serverData, $extraFields );
		$this->obfuscation = $obfuscation;
	}

	/**
	 * Normalize a string.
	 *
	 * @param string  $string The string.
	 * @return string   The normalized string.
	 * @since 1.10.0+
	 */
	private function normalize_string( $string ) {
		$string = str_replace( '"', '“', $string );
		$string = str_replace( '\'', '`', $string );
		return filter_var( $string, FILTER_SANITIZE_STRING );
	}

	/**
	 * Normalize an array.
	 *
	 * @param mixed  $array The array.
	 * @return mixed   The normalized array.
	 * @since 1.10.0+
	 */
	private function normalize_array( $array ) {
		array_walk_recursive( $array, function ( &$item, $key ) { if ( is_string( $item ) ) { $item = $this->normalize_string( $item ); } } );
		return $array;
	}

	/**
	 * Invocation of the processor.
	 *
	 * @since 1.0.0
	 * @param   array $record  Array or added records.
	 * @@return array   The modified records.
	 */
	public function __invoke( array $record ): array {
		$record['extra']['ip'] = IP::get_current();
		if ( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) ) {
			$record['extra']['ua'] = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' );
		}
		if ( array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
			$record['extra']['url'] = filter_input( INPUT_SERVER, 'REQUEST_URI' );
		}
		if ( array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
			$record['extra']['http_method'] = filter_input( INPUT_SERVER, 'REQUEST_METHOD' );
		}
		if ( array_key_exists( 'SERVER_NAME', $_SERVER ) ) {
			$record['extra']['server'] = filter_input( INPUT_SERVER, 'SERVER_NAME' );
		}
		if ( array_key_exists( 'HTTP_REFERER', $_SERVER ) ) {
			$record['extra']['referrer'] = filter_input( INPUT_SERVER, 'HTTP_REFERER' );
		}
		if ( $this->obfuscation ) {
			if ( array_key_exists( 'ip', $record['extra'] ) ) {
				$record['extra']['ip'] = Hash::simple_hash( $record['extra']['ip'] );
			}
		}
		return $this->normalize_array( $record );
	}
}
