<?php declare(strict_types=1);
/**
 * WordPress records processing
 *
 * Adds WordPress specific records with respect to privacy settings.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Processor;

use Decalog\System\Environment;
use Monolog\Processor\ProcessorInterface;
use Decalog\System\Blog;
use Decalog\System\Hash;
use Decalog\System\User;
use Decalog\System\IP;

/**
 * Define the WordPress processor functionality.
 *
 * Adds WordPress specific records with respect to privacy settings.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class WordpressProcessor implements ProcessorInterface {

	/**
	 * Pseudonymization switch.
	 *
	 * @since  1.0.0
	 * @var    boolean    $pseudonymize    Is pseudonymization activated?
	 */
	private $pseudonymize = false;

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
	 * @since   1.0.0
	 * @param   boolean $pseudonymize Optional. Is pseudonymization activated?
	 * @param   boolean $obfuscation Optional. Is obfuscation activated?
	 */
	public function __construct( $pseudonymize = true, $obfuscation = true ) {
		$this->pseudonymize = $pseudonymize;
		$this->obfuscation  = $obfuscation;
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
		array_walk_recursive(
			$array,
			function ( &$item, $key ) {
				if ( is_string( $item ) ) {
					$item = $this->normalize_string( $item );
				} }
		);
		return $array;
	}

	/**
	 * Invocation of the processor.
	 *
	 * @since   1.0.0
	 * @param   array $record  Array or added records.
	 * @@return array   The modified records.
	 */
	public function __invoke( array $record ): array {
		$record['extra']['siteid']     = Blog::get_current_blog_id( 0 );
		$record['extra']['sitename']   = Blog::get_current_blog_name();
		$record['extra']['sitedomain'] = Blog::get_current_blog_url();
		$record['extra']['userid']     = User::get_current_user_id( 0 );
		$record['extra']['username']   = User::get_current_user_name();
		if ( 0 !== (int) $record['extra']['userid'] ) {
			$record['extra']['usersession'] = Hash::simple_hash( wp_get_session_token(), false );
		}
		$record['extra']['ip'] = IP::get_current();
		if ( $this->obfuscation ) {
			if ( array_key_exists( 'ip', $record['extra'] ) ) {
				$record['extra']['ip'] = Hash::simple_hash( $record['extra']['ip'] );
			}
		}
		if ( $this->pseudonymize ) {
			if ( array_key_exists( 'userid', $record['extra'] ) ) {
				if ( $record['extra']['userid'] > 0 ) {
					$record['extra']['userid'] = Hash::simple_hash( (string) $record['extra']['userid'] );
					if ( array_key_exists( 'username', $record['extra'] ) ) {
						$record['extra']['username'] = Hash::simple_hash( $record['extra']['username'] );
					}
				}
			}
		}
		return $this->normalize_array( $record );
	}
}
