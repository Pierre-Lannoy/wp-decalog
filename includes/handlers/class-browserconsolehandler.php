<?php
/**
 * BrowserConsole handler for Monolog
 *
 * Handles all features of BrowserConsole handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @author  Olivier Poitrey <rs@dailymotion.com>.
 * @since   3.1.0
 */

namespace Decalog\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Utils;
use Monolog\Handler\AbstractProcessingHandler;
use Decalog\Plugin\Feature\EventTypes;

/**
 * Define the Monolog BrowserConsole handler.
 *
 * Handles all features of BrowserConsole handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @author  Olivier Poitrey <rs@dailymotion.com>.
 * @since   3.1.0
 */
class BrowserConsoleHandler extends AbstractProcessingHandler {

	protected static $initialized = false;
	protected static $records     = [];

	/**
	 * {@inheritDoc}
	 *
	 * Formatted output may contain some formatting markers to be transferred to `console.log` using the %c format.
	 *
	 * Example of formatted string:
	 *
	 *     You can do [[blue text]]{color: blue} or [[green background]]{background-color: green; color: white}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new LineFormatter( '[[%level_name%]]{font-weight: bold} %message%' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function write( array $record ): void {
		static::$records[] = $record;
		if ( ! static::$initialized ) {
			static::$initialized = true;
			$this->registerShutdownFunction();
		}
	}

	/**
	 * Convert records to javascript console commands and send it to the browser.
	 * This method is automatically called on PHP shutdown if output is HTML or Javascript.
	 */
	public static function send(): void {
		$format = static::getResponseFormat();
		if ( $format === 'unknown' ) {
			return;
		}
		if ( count( static::$records ) ) {
			if ( $format === 'html' ) {
				static::writeOutput( '<script>' . static::generateScript() . '</script>' );
			} elseif ( $format === 'js' ) {
				static::writeOutput( static::generateScript() );
			}
		}
	}

	/**
	 * Wrapper for register_shutdown_function to allow overriding
	 */
	protected function registerShutdownFunction(): void {
		if ( PHP_SAPI !== 'cli' ) {
			register_shutdown_function( [ 'Decalog\Handler\BrowserConsoleHandler', 'send' ] );
		}
	}

	/**
	 * Wrapper for echo to allow overriding
	 */
	protected static function writeOutput( string $str ): void {
		echo $str;
	}

	/**
	 * Checks the format of the response
	 *
	 * If Content-Type is set to application/javascript or text/javascript -> js
	 * If Content-Type is set to text/html, or is unset -> html
	 * If Content-Type is anything else -> unknown
	 *
	 * @return string One of 'js', 'html' or 'unknown'
	 */
	protected static function getResponseFormat(): string {
		foreach ( headers_list() as $header ) {
			if ( stripos( $header, 'content-type:' ) === 0 ) {
				if ( stripos( $header, 'application/javascript' ) !== false || stripos( $header, 'text/javascript' ) !== false ) {
					return 'js';
				}
				if ( stripos( $header, 'text/html' ) === false ) {
					return 'unknown';
				}
				break;
			}
		}
		return 'html';
	}

	private static function generateScript(): string {
		$script = [];
		foreach ( static::$records as $record ) {
			$context = static::dump( 'Context', $record['context'] );
			$extra   = static::dump( 'Extra', $record['extra'] );
			if ( empty( $context ) && empty( $extra ) ) {
				$script[] = static::call_array( 'log', static::handleStyles( $record['formatted'] ) );
			} else {
				$script = array_merge(
					$script,
					[ static::call_array( 'groupCollapsed', static::handleStyles( $record['formatted'] ) ) ],
					$context,
					$extra,
					[ static::call( 'groupEnd' ) ]
				);
			}
		}
		return "(function (c) {if (c && c.groupCollapsed) {\n" . implode( "\n", $script ) . "\n}})(console);";
	}

	private static function handleStyles( string $formatted ): array {
		$args   = [];
		$format = '%c' . $formatted;
		preg_match_all( '/\[\[(.*?)\]\]\{([^}]*)\}/s', $format, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );
		foreach ( array_reverse( $matches ) as $match ) {
			$args[] = '"font-weight: normal"';
			$args[] = static::quote( static::handleCustomStyles( $formatted ) );
			$pos    = $match[0][1];
			$format = Utils::substr( $format, 0, $pos ) . '%c' . $match[1][0] . '%c' . Utils::substr( $format, $pos + strlen( $match[0][0] ) );
		}
		$args[] = static::quote( 'font-weight: normal' );
		$args[] = static::quote( $format );
		return array_reverse( $args );
	}

	private static function handleCustomStyles( string $style ): string {
		return preg_replace_callback(
			'/^\[\[(.*)\]\]/',
			function ( array $m ) {
				$level = strtolower( $m[1] );
				if ( ! array_key_exists( $level, EventTypes::$levels_colors ) ) {
					$level = 'unknown';
				}
				return 'background-color:' . EventTypes::$levels_colors[ $level ][1] . ';color:' . EventTypes::$levels_colors[ $level ][0] . ';border-radius: 3px;padding:1px 6px;';
			},
			$style
		);
	}

	private static function dump( string $title, array $dict ): array {
		$script = [];
		$dict   = array_filter( $dict );
		if ( empty( $dict ) ) {
			return $script;
		}
		$script[] = static::call( 'log', static::quote( '%c%s' ), static::quote( 'font-weight: bold' ), static::quote( $title ) );
		foreach ( $dict as $key => $value ) {
			$value = wp_json_encode( $value );
			if ( empty( $value ) ) {
				$value = static::quote( '' );
			}
			$script[] = static::call( 'log', static::quote( '%s: %o' ), static::quote( (string) $key ), $value );
		}

		return $script;
	}

	private static function quote( string $arg ): string {
		return '"' . addcslashes( $arg, "\"\n\\" ) . '"';
	}

	private static function call( ...$args ): string {
		$method = array_shift( $args );
		return static::call_array( $method, $args );
	}

	private static function call_array( string $method, array $args ): string {
		return 'c.' . $method . '(' . implode( ', ', $args ) . ');';
	}
}
