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
	 * Invocation of the processor.
	 *
	 * @since 1.0.0
	 * @param   array $record  Array or added records.
	 * @@return array   The modified records.
	 */
	public function __invoke( array $record ): array {
		$record['extra']['ip'] = Environment::current_ip();
		if ( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) ) {
			$record['extra']['ua'] = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' );
		}
		if ( $this->obfuscation ) {
			if ( array_key_exists( 'ip', $record['extra'] ) ) {
				$record['extra']['ip'] = Hash::simple_hash( $record['extra']['ip'] );
			}
		}
		return $record;
	}
}
